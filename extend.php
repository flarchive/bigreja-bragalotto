<?php

namespace Bigreja\Bragalotto;

use Flarum\Extend;
use Flarum\User\User;
use Bigreja\Bragalotto\Api\Controller;
use Bigreja\Bragalotto\Api\Resource;
use Bigreja\Bragalotto\Access\EventPolicy;
use Bigreja\Bragalotto\Access\PickPolicy;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\UserScore;
use Bigreja\Bragalotto\Listener;
use Bigreja\Bragalotto\Notification;
use Bigreja\Bragalotto\Command\ImportLigaPortugalCommand;
use Bigreja\Bragalotto\Search\CompetitionSearcher;
use Bigreja\Bragalotto\Search\EventSearcher;
use Bigreja\Bragalotto\Search\PickSearcher;
use Bigreja\Bragalotto\Search\UserScoreSearcher;
use Bigreja\Bragalotto\Search\WeekSearcher;
use Flarum\Api\Context;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;

$filterValues = static function (array $filters, string $key): array {
    $values = $filters[$key] ?? [];
    $values = is_array($values) ? $values : [$values];

    if ($values === []) {
        return [];
    }

    $values = array_merge(...array_map(static fn ($value) => explode(',', (string) $value), $values));

    return array_values(array_filter($values, static fn ($value) => $value !== ''));
};

return [
    // Console Commands
    (new Extend\Console())
        ->command(ImportLigaPortugalCommand::class),

    // Frontend Assets
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/resources/less/forum.less')
        ->route('/bragalotto', 'bragalotto'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/resources/less/admin.less'),

    // Localization
    new Extend\Locales(__DIR__.'/resources/locale'),

    // API Resources
    new Extend\ApiResource(Resource\TeamResource::class),
    new Extend\ApiResource(Resource\CompetitionResource::class),
    new Extend\ApiResource(Resource\SeasonResource::class),
    new Extend\ApiResource(Resource\WeekResource::class),
    new Extend\ApiResource(Resource\EventResource::class),
    new Extend\ApiResource(Resource\PickResource::class),
    new Extend\ApiResource(Resource\UserScoreResource::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(Event::class, EventSearcher::class)
        ->addMutator(EventSearcher::class, static function (DatabaseSearchState $state, SearchCriteria $criteria) use ($filterValues): void {
            $query = $state->getQuery();
            $filters = $criteria->filters;

            $weekIds = $filterValues($filters, 'week');
            if ($weekIds !== []) {
                $includeUnassigned = in_array('null', $weekIds, true);
                $weekIds = array_values(array_filter($weekIds, static fn ($id) => $id !== 'null'));

                $query->where(function ($query) use ($weekIds, $includeUnassigned) {
                    if ($weekIds !== []) {
                        $query->whereIn('week_id', $weekIds);
                    } elseif (!$includeUnassigned) {
                        $query->whereRaw('1 = 0');
                    }

                    if ($includeUnassigned) {
                        $weekIds === [] ? $query->whereNull('week_id') : $query->orWhereNull('week_id');
                    }
                });
            }

            $statusValues = $filterValues($filters, 'status');
            if (count($statusValues) === 1) {
                $query->where('status', $statusValues[0]);
            } elseif ($statusValues !== []) {
                $query->whereIn('status', $statusValues);
            }

            $teamIds = $filterValues($filters, 'team');
            if ($teamIds !== []) {
                $query->where(function ($query) use ($teamIds) {
                    $query->whereIn('home_team_id', $teamIds)
                        ->orWhereIn('away_team_id', $teamIds);
                });
            }

            $competitionIds = $filterValues($filters, 'competition');
            if ($competitionIds !== []) {
                $query->whereHas('week', fn ($query) => $query->whereIn('competition_id', $competitionIds));
            }

            if (!empty($filters['upcoming'])) {
                $query->where('match_date', '>', \Carbon\Carbon::now());
            }
        })
        ->addSearcher(Competition::class, CompetitionSearcher::class)
        ->addMutator(CompetitionSearcher::class, static function (DatabaseSearchState $state, SearchCriteria $criteria) use ($filterValues): void {
            $seasonIds = $filterValues($criteria->filters, 'season');
            if (count($seasonIds) === 1) {
                $state->getQuery()->where('season_id', $seasonIds[0]);
            } elseif ($seasonIds !== []) {
                $state->getQuery()->whereIn('season_id', $seasonIds);
            }
        })
        ->addSearcher(Pick::class, PickSearcher::class)
        ->addMutator(PickSearcher::class, static function (DatabaseSearchState $state, SearchCriteria $criteria) use ($filterValues): void {
            $query = $state->getQuery();
            $actor = $state->getActor();
            $userIds = $filterValues($criteria->filters, 'user');
            $userId = $userIds[0] ?? null;

            if ($userId !== null && ($actor->can('bragalotto.manage') || (string) $actor->id === $userId)) {
                $query->where('user_id', $userId);
            } else {
                $query->where('user_id', $actor->id);
            }

            $eventIds = $filterValues($criteria->filters, 'event');
            if ($eventIds !== []) {
                $query->whereIn('event_id', $eventIds);
            }
        })
        ->addSearcher(UserScore::class, UserScoreSearcher::class)
        ->addMutator(UserScoreSearcher::class, static function (DatabaseSearchState $state, SearchCriteria $criteria) use ($filterValues): void {
            $query = $state->getQuery();
            $query->whereNull('season_id');

            $userIds = $filterValues($criteria->filters, 'user');
            if ($userIds !== []) {
                $query->where('user_id', $userIds[0]);
            }
        })
        ->addSearcher(Week::class, WeekSearcher::class)
        ->addMutator(WeekSearcher::class, static function (DatabaseSearchState $state, SearchCriteria $criteria) use ($filterValues): void {
            $competitionIds = $filterValues($criteria->filters, 'competition');
            if (count($competitionIds) === 1) {
                $state->getQuery()->where('competition_id', $competitionIds[0]);
            } elseif ($competitionIds !== []) {
                $state->getQuery()->whereIn('competition_id', $competitionIds);
            }
        }),

    // API Routes
    (new Extend\Routes('api'))
        ->post('/bragalotto-events/{id}/result', 'bragalotto.events.result', Controller\EnterEventResultController::class)
        ->post('/bragalotto/recalculate-all-scores', 'bragalotto.recalculate_scores', Controller\RecalculateAllScoresController::class),

    // Forum Attributes
    (new Extend\ApiResource(ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('bragalotto.canManage')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('bragalotto.manage')),
            Schema\Boolean::make('bragalotto.canView')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('bragalotto.view')),
            Schema\Boolean::make('bragalotto.makePicks')
                ->get(fn (object $forum, Context $context) => $context->getActor()->can('bragalotto.makePicks')),
        ]),

    (new Extend\Settings())
        ->serializeToForum('bragalotto.reverseDisplay', 'bigreja-bragalotto.reverse_display', 'boolval'),

    // Policies
    (new Extend\Policy())
        ->modelPolicy(Event::class, EventPolicy::class)
        ->modelPolicy(Pick::class, PickPolicy::class),

    // User Model Relationships
    (new Extend\Model(User::class))
        ->relationship('bragalottoPicks', function ($model) {
            return $model->hasMany(Pick::class, 'user_id');
        })
        ->relationship('bragalottoScores', function ($model) {
            return $model->hasMany(UserScore::class, 'user_id');
        }),

    // Event Listeners
    (new Extend\Event())
        ->listen(\Illuminate\Database\Eloquent\Events\Saved::class, Listener\UpdateUserScoresListener::class)
        ->listen(\Illuminate\Database\Eloquent\Events\Saved::class, Listener\SendResultNotificationsListener::class),

    // Notifications
    (new Extend\Notification())
        ->type(Notification\EventResultBlueprint::class, ['alert']),
];