<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Week;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Week>
 */
class WeekResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-weeks';
    }

    public function model(): string
    {
        return Week::class;
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('externalId')
                ->property('external_id')
                ->writable()
                ->nullable(),

            Schema\Str::make('name')
                ->requiredOnCreate()
                ->writable()
                ->maxLength(100),

            Schema\Integer::make('competitionId')
                ->property('competition_id')
                ->writable()
                ->nullable(),

            Schema\Integer::make('weekNumber')
                ->property('week_number')
                ->writable()
                ->nullable(),

            Schema\DateTime::make('startDate')
                ->property('start_date')
                ->writable()
                ->nullable(),

            Schema\DateTime::make('endDate')
                ->property('end_date')
                ->writable()
                ->nullable(),

            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),

            Schema\Relationship\ToOne::make('competition')
                ->type('bragalotto-competitions')
                ->includable(),

            Schema\Relationship\ToMany::make('events')
                ->type('bragalotto-events')
                ->includable(),
        ];
    }

    public function deleting(object $model, Context $context): void
    {
        if ($model->events()->exists()) {
            throw new ValidationException([
                'message' => resolve('translator')->trans('bigreja-bragalotto.lib.messages.in_use')
            ]);
        }

    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->can('bragalotto.view')
                ->defaultSort('weekNumber'),
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
            SortColumn::make('weekNumber')->column('week_number'),
            SortColumn::make('startDate')->column('start_date'),
            SortColumn::make('name'),
            SortColumn::make('createdAt'),
        ];
    }
}

