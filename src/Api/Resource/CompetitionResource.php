<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Competition;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends AbstractDatabaseResource<Competition>
 */
class CompetitionResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-competitions';
    }

    public function model(): string
    {
        return Competition::class;
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('externalId')
                ->property('external_id')
                ->writable()
                ->nullable(),

            Schema\Integer::make('seasonId')
                ->property('season_id')
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

            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),

            Schema\Relationship\ToOne::make('season')
                ->type('bragalotto-seasons')
                ->includable(),

            Schema\Relationship\ToMany::make('weeks')
                ->type('bragalotto-weeks')
                ->includable(),
        ];
    }

    public function saving(object $model, Context $context): void
    {
        if (empty($model->slug) && !empty($model->name)) {
            $model->slug = Str::slug($model->name);
        }
    }

    public function deleting(object $model, Context $context): void
    {
        if ($model->weeks()->exists()) {
            throw new ValidationException([
                'message' => resolve('translator')->trans('bigreja-bragalotto.lib.messages.in_use')
            ]);
        }
    }

    public function scope(Builder $query, Context $context): void
    {
        $params = $context->request->getQueryParams();
        if ($seasonId = Arr::get($params, 'filter.season')) {
            $query->where('season_id', $seasonId);
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
            SortColumn::make('createdAt'),
        ];
    }
}

