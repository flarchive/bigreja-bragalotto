<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Season;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<Season>
 */
class SeasonResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-seasons';
    }

    public function model(): string
    {
        return Season::class;
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

            Schema\Str::make('slug')
                ->writable()
                ->nullable()
                ->maxLength(100),

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

            Schema\Relationship\ToMany::make('competitions')
                ->type('bragalotto-competitions')
                ->includable(),

            Schema\Relationship\ToMany::make('weeks')
                ->type('bragalotto-weeks')
                ->includable(),
        ];
    }

    public function saving(object $model, Context $context): ?object
    {
        if (empty($model->slug) && !empty($model->name)) {
            $model->slug = Str::slug($model->name);
        }

        return null;
    }

    public function deleting(object $model, Context $context): void
    {
        if ($model->competitions()->exists() || $model->weeks()->exists()) {
            throw new ValidationException([
                'message' => resolve('translator')->trans('bigreja-bragalotto.lib.messages.in_use')
            ]);
        }

    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->can('bragalotto.view'),
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
            SortColumn::make('name'),
            SortColumn::make('startDate'),
            SortColumn::make('createdAt'),
        ];
    }
}

