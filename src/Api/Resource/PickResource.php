<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Illuminate\Contracts\Bus\Dispatcher;
use Tobyz\JsonApiServer\Context;

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

    public function saving(object $model, Context $context): ?object
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

        return null;
    }

    public function saved(object $model, Context $context): ?object
    {
        $event = $model->event ?: Event::find($model->event_id);
        if ($event && ($event->isFinished() || $event->isClosed())) {
            resolve(Dispatcher::class)->dispatch(new RecalculateUserScoreJob($model->user_id));
        }

        return null;

    }

    public function deleted(object $model, Context $context): void
    {
        $event = $model->event ?: Event::find($model->event_id);
        if ($event && ($event->isFinished() || $event->isClosed())) {
            resolve(Dispatcher::class)->dispatch(new RecalculateUserScoreJob($model->user_id));
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

