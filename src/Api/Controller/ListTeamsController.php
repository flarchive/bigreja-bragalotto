<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil; // YENİ: Import et
use Bigreja\Bragalotto\Api\Serializer\TeamSerializer;
use Bigreja\Bragalotto\Team;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListTeamsController extends AbstractListController
{
    public $serializer = TeamSerializer::class;

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // YENİ: Admin yetkisi kontrolü eklendi
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        return Team::all();
    }
}