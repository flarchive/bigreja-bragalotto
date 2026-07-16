<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\WeekSerializer;
use Bigreja\Bragalotto\Week;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListWeeksController extends AbstractListController
{
    public $serializer = WeekSerializer::class;

    public $include = ['competition'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $query = Week::query()->with('competition');

        if ($competitionId = Arr::get($request->getQueryParams(), 'filter.competition')) {
            $query->where('competition_id', $competitionId);
        }

        return $query->orderBy('week_number')->get();
    }
}