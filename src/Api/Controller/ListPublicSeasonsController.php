<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\SeasonSerializer;
use Bigreja\Bragalotto\Season;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPublicSeasonsController extends AbstractListController
{
    public $serializer = SeasonSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // Use 'bragalotto.view' permission instead of 'bragalotto.manage'
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.view');

        return Season::orderBy('start_date', 'desc')->get();
    }
}