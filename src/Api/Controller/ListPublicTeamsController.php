<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\TeamSerializer;
use Bigreja\Bragalotto\Team;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPublicTeamsController extends AbstractListController
{
    public $serializer = TeamSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // Use 'bragalotto.view' permission instead of 'bragalotto.manage'
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.view');

        return Team::all();
    }
}