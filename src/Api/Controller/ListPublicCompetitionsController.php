<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\CompetitionSerializer;
use Bigreja\Bragalotto\Competition;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPublicCompetitionsController extends AbstractListController
{
    public $serializer = CompetitionSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.view');

        $query = Competition::orderBy('name');

        $filters = $this->extractFilter($request);
        if ($seasonId = Arr::get($filters, 'season')) {
            $query->where('season_id', $seasonId);
        }

        return $query->get();
    }
}
