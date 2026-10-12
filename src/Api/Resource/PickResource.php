<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

/**
 * @extends AbstractDatabaseResource<Pick>
 */
class PickResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-picks';
    }

    public function model(): string
    {
        return Pick::class;
    }

    public function fields(): array
    {
        return [
            Schema\Integer::make('userId')
                ->property('user_id'),

            Schema\Integer::make('eventId')
                ->property('event_id')
                ->writable()
                ->requiredOnCreate(),

            Schema\Str::make('selectedOutcome')
                ->property('selected_outcome')
                ->writable()
                ->required(),

            Schema\Boolean::make('isCorrect')
                ->property('is_correct')
                ->nullable(),

            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),

            Schema\Relationship\ToOne::make('event')
                ->type('bragalotto-events')
                ->includable(),
        ];
    }

    public function saving(object $model, Context $context): void
    {
        $actor = $context->getActor();

        if (!$model->exists) {
            $model->user_id = $actor->id;
        }

        $event = $model->event ?: Event::find($model->event_id);
        if (!$event || !$event->canPick()) {
            throw new ValidationException([
                'message' => resolve('translator')->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }

        if ($model->selected_outcome === Event::RESULT_DRAW && !$event->allow_draw) {
            throw new ValidationException([
                'message' => resolve('translator')->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }

        $validOutcomes = [Event::RESULT_HOME, Event::RESULT_AWAY, Event::RESULT_DRAW];
        if (!in_array($model->selected_outcome, $validOutcomes)) {
            throw new ValidationException([
                'message' => resolve('translator')->trans('bigreja-bragalotto.lib.messages.invalid_outcome')
            ]);
        }

        if ($model->exists && $model->isDirty('selected_outcome')) {
            $model->is_correct = null;
        }
    }

    public function saved(object $model, Context $context): void
    {
        $event = $model->event ?: Event::find($model->event_id);
        if ($event && ($event->isFinished() || $event->isClosed())) {
            resolve(Dispatcher::class)->dispatch(new RecalculateUserScoreJob($model->user_id));
        }
    }

    public function deleted(object $model, Context $context): void
    {
        $event = $model->event ?: Event::find($model->event_id);
        if ($event && ($event->isFinished() || $event->isClosed())) {
            resolve(Dispatcher::class)->dispatch(new RecalculateUserScoreJob($model->user_id));
        }
    }

    public function scope(Builder $query, Context $context): void
    {
        $actor = $context->getActor();
        $params = $context->request->getQueryParams();
        $filters = Arr::get($params, 'filter', []);

        $userId = Arr::get($filters, 'user');
        if ($userId && ($actor->can('bragalotto.manage') || $actor->id == $userId)) {
            $query->where('user_id', $userId);
        } else {
            $query->where('user_id', $actor->id);
        }

        if ($eventId = Arr::get($filters, 'event')) {
            $eventIds = explode(',', $eventId);
            $query->whereIn('event_id', $eventIds);
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->can('bragalotto.makePicks')
                ->defaultSort('-createdAt')
                ->paginate(20, 50),
            Endpoint\Show::make()
                ->can('view'),
            Endpoint\Create::make()
                ->can('bragalotto.makePicks'),
            Endpoint\Update::make()
                ->can('edit'),
            Endpoint\Delete::make()
                ->can('delete'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
            SortColumn::make('updatedAt'),
        ];
    }
}

