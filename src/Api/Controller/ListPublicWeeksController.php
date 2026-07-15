<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\WeekSerializer;
use Bigreja\Bragalotto\Week;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPublicWeeksController extends AbstractListController
{
    public $serializer = WeekSerializer::class;
    
    // Public olduğu için 'season' ilişkisini dahil etmeye gerek yok
    // public $include = ['season']; 

    protected function data(ServerRequestInterface $request, Document $document)
    {
        // Use 'bragalotto.view' permission instead of 'bragalotto.manage'
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.view');

        $query = Week::query();

        // Admin panelinin aksine, burada SADECE sezon ID'si olan
        // haftaları listelemek daha mantıklı olabilir.
        // $query->whereNotNull('season_id');

        return $query->get();
    }
}