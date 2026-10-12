<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Carbon\Carbon;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * @extends AbstractDatabaseResource<Event>
 */
class EventResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-events';
    }

    public function model(): string
    {
        return Event::class;
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('externalId')
                ->property('external_id')
                ->writable()
                ->nullable(),

            Schema\Integer::make('weekId')
                ->property('week_id')
                ->writable()
                ->nullable(),

            Schema\Integer::make('homeTeamId')
                ->property('home_team_id')
                ->writable(),

            Schema\Integer::make('awayTeamId')
                ->property('away_team_id')
                ->writable(),

            Schema\DateTime::make('matchDate')
                ->property('match_date')
                ->writable(),

            Schema\DateTime::make('cutoffDate')
                ->property('cutoff_date')
                ->writable(),

            Schema\Boolean::make('allowDraw')
                ->property('allow_draw')
                ->writable(),

            Schema\Str::make('status')
                ->writable(),

            Schema\Integer::make('homeScore')
                ->property('home_score')
                ->writable()
                ->nullable(),

            Schema\Integer::make('awayScore')
                ->property('away_score')
                ->writable()
                ->nullable(),

            Schema\Str::make('result')
                ->writable()
                ->nullable(),

            Schema\Boolean::make('canPick')
                ->get(fn (Event $event) => $event->canPick()),

            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),

            Schema\Relationship\ToOne::make('week')
                ->type('bragalotto-weeks')
                ->includable(),

            Schema\Relationship\ToOne::make('homeTeam')
                ->type('bragalotto-teams')
                ->includable(),

            Schema\Relationship\ToOne::make('awayTeam')
                ->type('bragalotto-teams')
                ->includable(),

            Schema\Relationship\ToMany::make('picks')
                ->type('bragalotto-picks')
                ->includable(),
        ];
    }

    public function deleted(object $model, Context $context): void
    {
        $affectedUserIds = Pick::where('event_id', $model->id)
            ->distinct()
            ->pluck('user_id')
            ->all();

        $bus = resolve(Dispatcher::class);
        foreach ($affectedUserIds as $userId) {
            $bus->dispatch(new RecalculateUserScoreJob($userId));
        }
    }

    public function scope(Builder $query, Context $context): void
    {
        $params = $context->request->getQueryParams();
        $filters = Arr::get($params, 'filter', []);

        if ($weekIdString = Arr::get($filters, 'week')) {
            $weekIds = explode(',', $weekIdString);

            $query->where(function ($q) use ($weekIds) {
                $ids = array_diff($weekIds, ['null']);

                if (!empty($ids)) {
                    $q->whereIn('week_id', $ids);
                } elseif (!in_array('null', $weekIds)) {
                    $q->whereRaw('1=0');
                }

                if (in_array('null', $weekIds)) {
                    $q->orWhereNull('week_id');
                }
            });
        }

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($teamId = Arr::get($filters, 'team')) {
            $query->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)
                  ->orWhere('away_team_id', $teamId);
            });
        }

        if ($competitionFilter = Arr::get($filters, 'competition')) {
            $ids = array_filter(explode(',', $competitionFilter));
            $query->whereHas('week', function ($q) use ($ids) {
                $q->whereIn('competition_id', $ids);
            });
        }

        if (Arr::get($filters, 'upcoming')) {
            $query->where('match_date', '>', Carbon::now());
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->can('bragalotto.view')
                ->defaultSort('matchDate')
                ->paginate(20, 50),
            Endpoint\Show::make()
                ->can('bragalotto.view'),
            Endpoint\Create::make()
                ->can('bragalotto.manage'),
            Endpoint\Update::make()
                ->can('bragalotto.manage'),
            Endpoint\Delete::make()
                ->can('bragalotto.manage'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('matchDate')->property('match_date'),
        ];
    }
}

