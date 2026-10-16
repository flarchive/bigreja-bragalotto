<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobyz\JsonApiServer\Context;

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
            SortColumn::make('matchDate')->column('match_date'),
        ];
    }
}

