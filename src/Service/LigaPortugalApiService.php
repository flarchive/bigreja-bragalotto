<?php

namespace Bigreja\Bragalotto\Service;

class LigaPortugalApiService
{
    private const BASE_V1 = 'https://www.ligaportugal.pt/api/v1';
    private const BASE_V2 = 'https://www.ligaportugal.pt/api/v2';
    private const TIMEOUT = 15;

    private function get(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => self::TIMEOUT,
                'header'  => implode("\r\n", [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0 (compatible; Bragalotto/1.0)',
                ]),
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer'      => true,
                'verify_peer_name' => true,
            ],
        ]);

        $json = @file_get_contents($url, false, $context);

        if ($json === false) {
            throw new \RuntimeException("HTTP request failed: {$url}");
        }

        // Check HTTP status
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0] ?? '';
            if (preg_match('/HTTP\/\S+\s+(\d+)/', $statusLine, $m) && (int) $m[1] >= 400) {
                throw new \RuntimeException("HTTP {$m[1]} from: {$url} — body: " . substr($json, 0, 300));
            }
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Invalid JSON from: ' . $url . ' — ' . json_last_error_msg() . ' — raw: ' . substr($json, 0, 200));
        }

        // Normalise: if the API wraps its list in an object, extract the array
        if (is_array($data) && !array_is_list($data)) {
            // Look for a key that holds the actual list (rounds, matches, teams, data, etc.)
            foreach (['rounds', 'matches', 'teams', 'data', 'items', 'results'] as $key) {
                if (isset($data[$key]) && is_array($data[$key])) {
                    return $data[$key];
                }
            }
            // Return single-item wrapper as one-element array
            return [$data];
        }

        return $data ?? [];
    }

    /**
     * GET /api/v2/seasons
     * Returns: [{id, label, current, ...}]
     */
    public function getSeasons(): array
    {
        return $this->get(self::BASE_V2 . '/seasons');
    }

    /**
     * GET /api/v1/competition/teams?competition=&season=
     * Returns: [{id, name, logoUrl?, logo?, ...}]
     */
    public function getTeams(string $competition, int $season): array
    {
        return $this->get(self::BASE_V1 . '/competition/teams?' . http_build_query([
            'competition' => $competition,
            'season'      => $season,
        ]));
    }

    /**
     * GET /api/v1/competition/season/rounds?competition=&season=
     * Returns: [{id, label}]
     */
    public function getRounds(string $competition, int $season): array
    {
        return $this->get(self::BASE_V1 . '/competition/season/rounds?' . http_build_query([
            'competition' => $competition,
            'season'      => $season,
        ]));
    }

    /**
     * GET /api/v1/competition/matches?competition=&season=&round=
     * Returns: [{id, homeTeam:{id,name}, awayTeam:{id,name}, homeTeamGoals, awayTeamGoals, date?, status?}]
     */
    public function getMatches(string $competition, int $season, ?int $round = null): array
    {
        $params = [
            'competition' => $competition,
            'season'      => $season,
        ];
        if ($round !== null) {
            $params['round'] = $round;
        }

        return $this->get(self::BASE_V1 . '/competition/matches?' . http_build_query($params));
    }

    /**
     * GET /api/v1/match/details?competition=&season=&round=&fixture=
     * Returns: {id, status, minute, homeTeamGoals, awayTeamGoals}
     */
    public function getMatchDetails(string $competition, int $season, int $round, int $fixture): array
    {
        return $this->get(self::BASE_V1 . '/match/details?' . http_build_query([
            'competition' => $competition,
            'season'      => $season,
            'round'       => $round,
            'fixture'     => $fixture,
        ]));
    }
}
