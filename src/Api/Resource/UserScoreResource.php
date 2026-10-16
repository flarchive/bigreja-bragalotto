<?php

namespace Bigreja\Bragalotto\Api\Resource;

use Bigreja\Bragalotto\UserScore;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Illuminate\Support\Arr;
use Tobyz\JsonApiServer\Context;

/**
 * @extends AbstractDatabaseResource<UserScore>
 */
class UserScoreResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'bragalotto-user-scores';
    }

    public function model(): string
    {
        return UserScore::class;
    }

    public function fields(): array
    {
        return [
            Schema\Integer::make('userId')
                ->property('user_id'),

            Schema\Integer::make('seasonId')
                ->property('season_id')
                ->nullable(),

            Schema\Integer::make('totalPoints')
                ->property('total_points'),

            Schema\Integer::make('totalPicks')
                ->property('total_picks'),

            Schema\Integer::make('correctPicks')
                ->property('correct_picks'),

            Schema\Number::make('accuracy')
                ->get(fn (UserScore $score) => $score->getAccuracyPercentage()),

            Schema\Integer::make('rank')
                ->get(fn (UserScore $score) => $score->calculatedRank ?? (int) $score->calculateRank()),

            Schema\DateTime::make('createdAt'),
            Schema\DateTime::make('updatedAt'),

            Schema\Relationship\ToOne::make('user')
                ->type('users')
                ->includable(),

            Schema\Relationship\ToOne::make('season')
                ->type('bragalotto-seasons')
                ->includable(),
        ];
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->can('bragalotto.view')
                ->defaultSort('-totalPoints,-correctPicks,totalPicks,id')
                ->paginate(20, 50)
                ->beforeSerialization(function (Context $context, mixed $results) {
                    $params = $context->request->getQueryParams();
                    if (!Arr::get($params, 'filter.user') && is_iterable($results)) {
                        $offset = (int) Arr::get($params, 'page.offset', 0);
                        $i = 0;
                        foreach ($results as $score) {
                            if ($score instanceof UserScore) {
                                $score->calculatedRank = $offset + 1 + ($i++);
                            }
                        }
                    }
                }),
            Endpoint\Show::make()
                ->can('bragalotto.view'),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('totalPoints')->column('total_points'),
            SortColumn::make('correctPicks')->column('correct_picks'),
            SortColumn::make('totalPicks')->column('total_picks'),
            SortColumn::make('id'),
        ];
    }
}

