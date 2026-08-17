<?php

namespace Bigreja\Bragalotto\Command;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Str;
use Bigreja\Bragalotto\Service\LigaPortugalApiService;
use Bigreja\Bragalotto\Season;
use Bigreja\Bragalotto\Competition;
use Bigreja\Bragalotto\Team;
use Bigreja\Bragalotto\Week;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Job\ProcessEventResultsJob;

class ImportLigaPortugalCommand extends Command
{
    protected $signature = 'bragalotto:import
        {--season=            : Liga Portugal season ID (e.g. 20262027). Defaults to current.}
        {--competition=ligaportugalbetclic : Competition slug (e.g. ligaportugalbetclic). Can repeat.}
        {--round=             : Import only this specific round number.}
        {--results-only       : Only update scores/status for events already in DB.}';

    protected $description = 'Import seasons, competitions, teams, rounds and matches from the Liga Portugal API.';

    private LigaPortugalApiService $api;
    private Dispatcher $bus;

    public function __construct(LigaPortugalApiService $api, Dispatcher $bus)
    {
        parent::__construct();
        $this->api = $api;
        $this->bus = $bus;
    }

    public function handle(): int
    {
        $competitionSlugs = (array) $this->option('competition');
        $roundFilter      = $this->option('round') !== null ? (int) $this->option('round') : null;
        $resultsOnly      = (bool) $this->option('results-only');

        // ── 1. Resolve & upsert Season ─────────────────────────────────────
        $seasonExternalId = $this->resolveSeasonId();
        if (!$seasonExternalId) {
            $this->error('Could not resolve a season. Use --season=YYYYYYY (e.g. 20262027).');
            return 1;
        }

        $season = $this->upsertSeason($seasonExternalId);
        $this->info("► Season: {$season->name} (id={$season->id})");

        // ── 2. Teams (once per season, not per competition) ────────────────
        if (!$resultsOnly) {
            $this->importTeams($competitionSlugs[0], $seasonExternalId);
        }

        // ── 3. Loop over each competition slug ─────────────────────────────
        foreach ($competitionSlugs as $slug) {
            $this->info("  ► Competition: {$slug}");

            $competition = $this->upsertCompetition($slug, $season, $seasonExternalId);

            if (!$resultsOnly) {
                $this->importRounds($slug, $seasonExternalId, $season, $competition);
            }

            $this->importMatches($slug, $seasonExternalId, $season, $competition, $roundFilter, $resultsOnly);
        }

        $this->info('✓ Import complete.');
        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────────

    private function resolveSeasonId(): ?int
    {
        if ($this->option('season') !== null) {
            return (int) $this->option('season');
        }

        $this->line('  Fetching current season from API…');
        try {
            $seasons = $this->api->getSeasons();
        } catch (\Exception $e) {
            $this->error('  Seasons API failed: ' . $e->getMessage());
            return null;
        }

        foreach ($seasons as $s) {
            if (!empty($s['current'])) {
                return (int) $s['id'];
            }
        }

        return !empty($seasons[0]['id']) ? (int) $seasons[0]['id'] : null;
    }

    private function upsertSeason(int $externalId): Season
    {
        try {
            $seasons = $this->api->getSeasons();
        } catch (\Exception $e) {
            $seasons = [];
        }

        $label = (string) $externalId;
        foreach ($seasons as $s) {
            if ((int) $s['id'] === $externalId) {
                $label = $s['label'] ?? $label;
                break;
            }
        }

        return Season::updateOrCreate(
            ['external_id' => (string) $externalId],
            ['name' => $label, 'slug' => Str::slug($label)]
        );
    }

    private function upsertCompetition(string $slug, Season $season, int $seasonExternalId): Competition
    {
        // Try to get the label from the seasons API competitions array
        $label = $slug;
        try {
            $seasons = $this->api->getSeasons();
            foreach ($seasons as $s) {
                if ((int) $s['id'] === $seasonExternalId && !empty($s['competitions'])) {
                    foreach ($s['competitions'] as $c) {
                        $extId = $c['externalId'] ?? $c['slug'] ?? null;
                        if ($extId === $slug || ($c['id'] ?? null) == $slug) {
                            $label = $c['label'] ?? $c['name'] ?? $slug;
                            break 2;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Non-fatal; use slug as label
        }

        $competition = Competition::updateOrCreate(
            ['external_id' => $slug, 'season_id' => $season->id],
            ['name' => $label, 'slug' => Str::slug($label), 'season_id' => $season->id]
        );

        $this->line("    Competition upserted: {$competition->name} (id={$competition->id})");
        return $competition;
    }

    private function importTeams(string $competition, int $seasonId): void
    {
        $this->line('  Importing teams…');
        try {
            $teams = $this->api->getTeams($competition, $seasonId);
        } catch (\Exception $e) {
            $this->warn('  Teams API failed: ' . $e->getMessage());
            return;
        }

        $count = 0;
        foreach ($teams as $t) {
            if (empty($t['id'])) continue;
            $name = $t['name'] ?? ('Team ' . $t['id']);
            $logo = $t['logoUrl'] ?? $t['logo'] ?? $t['crest'] ?? $t['image'] ?? null;

            Team::updateOrCreate(
                ['external_id' => (string) $t['id']],
                ['name' => $name, 'slug' => Str::slug($name), 'logo_path' => $logo]
            );
            $count++;
        }
        $this->line("  Teams upserted: {$count}");
    }

    private function importRounds(string $competitionSlug, int $seasonId, Season $season, Competition $competition): void
    {
        $this->line("    Importing rounds for competition={$competitionSlug} season={$seasonId}…");
        try {
            $rounds = $this->api->getRounds($competitionSlug, $seasonId);
        } catch (\Exception $e) {
            $this->error("    Rounds API failed: " . $e->getMessage());
            return;
        }

        if (empty($rounds)) {
            $this->warn("    Rounds API returned 0 items. Check competition slug and season ID.");
            return;
        }

        $this->line("    API returned " . count($rounds) . " rounds. First item keys: " . implode(', ', array_keys($rounds[0] ?? [])));

        $count = 0;
        foreach ($rounds as $r) {
            // Handle actual API field names: round_number, description
            $rid  = $r['round_number'] ?? $r['id'] ?? $r['roundId'] ?? $r['round'] ?? null;
            $name = $r['description'] ?? $r['label'] ?? $r['name'] ?? ('Jornada ' . $rid);

            if (empty($rid)) {
                $this->warn('    Skipping round with no id: ' . json_encode($r));
                continue;
            }

            Week::updateOrCreate(
                ['external_id' => (string) $rid, 'competition_id' => $competition->id],
                [
                    'name'           => $name,
                    'week_number'    => (int) $rid,
                    'competition_id' => $competition->id,
                ]
            );
            $count++;
        }
        $this->line("    Rounds upserted: {$count}");
    }

    private function importMatches(
        string      $competitionSlug,
        int         $seasonId,
        Season      $season,
        Competition $competition,
        ?int        $roundFilter,
        bool        $resultsOnly
    ): void {
        if ($roundFilter !== null) {
            $roundIds = [$roundFilter];
        } else {
            try {
                $rounds   = $this->api->getRounds($competitionSlug, $seasonId);
                // Support actual API field names and fallbacks
                $roundIds = array_values(array_filter(array_map(
                    fn($r) => $r['round_number'] ?? $r['id'] ?? $r['roundId'] ?? $r['round'] ?? null,
                    $rounds
                )));
            } catch (\Exception $e) {
                $this->error("    Could not fetch rounds for match import: " . $e->getMessage());
                return;
            }

            if (empty($roundIds)) {
                $this->warn('    No round IDs found — cannot import matches.');
                return;
            }
        }

        // Lookup maps: external_id → local id
        $teamMap = Team::whereNotNull('external_id')->pluck('id', 'external_id')->all();
        $weekMap = Week::where('competition_id', $competition->id)
            ->whereNotNull('external_id')
            ->pluck('id', 'external_id')
            ->all();

        $matchCount  = 0;
        $resultCount = 0;
        $skipCount   = 0;

        foreach ($roundIds as $roundId) {
            $roundId = (int) $roundId;
            $this->line("    Round {$roundId}…");

            try {
                $matches = $this->api->getMatches($competitionSlug, $seasonId, $roundId);
            } catch (\Exception $e) {
                $this->warn("    Matches API failed for round {$roundId}: " . $e->getMessage());
                continue;
            }

            if (empty($matches)) {
                $this->warn("    Round {$roundId}: API returned 0 matches.");
                continue;
            }

            // Show first item keys once (only for first round)
            if ($matchCount === 0 && $skipCount === 0 && $resultCount === 0) {
                $first = $matches[0];
                $this->line("    First match keys: " . implode(', ', array_keys($first)));
                $this->line("    First match sample: " . json_encode(array_slice($first, 0, 8)));
            }

            foreach ($matches as $m) {
                $mid        = $m['fixtureCode'] ?? $m['fixture_code'] ?? $m['id'] ?? $m['fixture_id'] ?? $m['fixtureId'] ?? $m['matchId'] ?? $m['game_id'] ?? null;
                if (empty($mid)) {
                    $this->warn("    Skipping match with no id: " . json_encode(array_slice($m, 0, 5)));
                    $skipCount++;
                    continue;
                }

                $externalId = (string) $mid;
                $homeExtId  = (string) ($m['homeTeam']['id'] ?? $m['home_team']['id'] ?? $m['home']['id'] ?? $m['homeTeamId'] ?? $m['home_team_id'] ?? '');
                $awayExtId  = (string) ($m['awayTeam']['id'] ?? $m['away_team']['id'] ?? $m['away']['id'] ?? $m['awayTeamId'] ?? $m['away_team_id'] ?? '');
                $homeTeamId = $teamMap[$homeExtId] ?? null;
                $awayTeamId = $teamMap[$awayExtId] ?? null;
                $weekId     = $weekMap[(string) $roundId] ?? null;

                if (!$homeTeamId || !$awayTeamId) {
                    $this->warn("    Match {$externalId}: teams not found (home={$homeExtId}, away={$awayExtId}).");
                    $skipCount++;
                    continue;
                }

                if ($resultsOnly) {
                    $event = Event::where('external_id', $externalId)->first();
                    if (!$event) { $skipCount++; continue; }
                } else {
                    $matchDate = !empty($m['matchDate']) ? Carbon::parse($m['matchDate']) : Carbon::now();

                    $event = Event::updateOrCreate(
                        ['external_id' => $externalId],
                        [
                            'week_id'      => $weekId,
                            'home_team_id' => $homeTeamId,
                            'away_team_id' => $awayTeamId,
                            'match_date'   => $matchDate,
                            'cutoff_date'  => $matchDate,
                            'allow_draw'   => true,
                            'status'       => Event::STATUS_SCHEDULED,
                        ]
                    );
                    $matchCount++;
                }

                // Resolve status / scores (handle multiple possible field names)
                $homeGoals = $m['homeTeamGoals'] ?? $m['homeScore'] ?? $m['goalsHome'] ?? null;
                $awayGoals = $m['awayTeamGoals'] ?? $m['awayScore'] ?? $m['goalsAway'] ?? null;
                $status    = $m['status'] ?? null;

                if ($status === null || $status === 'FINISHED') {
                    try {
                        $details   = $this->api->getMatchDetails($competitionSlug, $seasonId, $roundId, (int) $mid);
                        $status    = $details['status'] ?? $status;
                        $homeGoals = $details['homeTeamGoals'] ?? $details['homeScore'] ?? $homeGoals;
                        $awayGoals = $details['awayTeamGoals'] ?? $details['awayScore'] ?? $awayGoals;
                    } catch (\Exception $e) {
                        // Non-fatal
                    }
                }

                if ($status === 'FINISHED' && $homeGoals !== null && $awayGoals !== null) {
                    if ($this->applyResult($event, (int) $homeGoals, (int) $awayGoals)) {
                        $resultCount++;
                    }
                }
            }
        }

        $this->line("    Matches upserted: {$matchCount}");
        if ($resultCount > 0) $this->line("    Results applied:  {$resultCount}");
        if ($skipCount   > 0) $this->warn("    Skipped:          {$skipCount}");
    }

    private function applyResult(Event $event, int $homeGoals, int $awayGoals): bool
    {
        if (
            $event->status === Event::STATUS_FINISHED &&
            (int) $event->home_score === $homeGoals &&
            (int) $event->away_score === $awayGoals
        ) {
            return false;
        }

        $event->home_score = $homeGoals;
        $event->away_score = $awayGoals;
        $event->status     = Event::STATUS_FINISHED;
        $event->save(); // observer auto-calculates result

        $this->bus->dispatch(new ProcessEventResultsJob($event->id));
        return true;
    }
}
