<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\Team;
use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Foundation\ValidationException;
use Illuminate\Support\Str;

/**
 * @extends AbstractDatabaseResource<Team>
 */
class TeamResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-teams';
    }

    public function model(): string
    {
        return Team::class;
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('name')
                ->requiredOnCreate()
                ->writable()
                ->maxLength(100),

            Schema\Str::make('slug')
                ->writable()
                ->nullable()
                ->maxLength(100),

            Schema\Str::make('logoPath')
                ->property('logo_path')
                ->writable()
                ->nullable()
                ->maxLength(255),

            Schema\Str::make('logoUrl')
                ->get(fn (Team $team) => $team->logo_url),

            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),
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
        if ($model->homeEvents()->exists() || $model->awayEvents()->exists()) {
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
            SortColumn::make('createdAt'),
        ];
    }
}

