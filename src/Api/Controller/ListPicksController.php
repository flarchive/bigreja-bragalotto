<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Bigreja\Bragalotto\Api\Serializer\PickSerializer;
use Bigreja\Bragalotto\Pick;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListPicksController extends AbstractListController
{
    public $serializer = PickSerializer::class;
    public $include = ['event', 'event.homeTeam', 'event.awayTeam', 'event.week', 'user'];
    
    public $limit = 20;
    public $sort = ['createdAt' => 'desc'];
    public $sortFields = ['createdAt', 'updatedAt'];

    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.makePicks');

        $query = Pick::query();

        if ($userId = Arr::get($request->getQueryParams(), 'filter.user')) {
            if ($actor->hasPermission('bragalotto.manage') || $actor->id == $userId) {
                $query->where('user_id', $userId);
            } else {
                $query->where('user_id', $actor->id);
            }
        } else {
            $query->where('user_id', $actor->id);
        }

        // GÜNCELLENDİ: Virgülle ayrılmış çoklu ID desteği eklendi (1,2,3)
        if ($eventId = Arr::get($request->getQueryParams(), 'filter.event')) {
            $eventIds = explode(',', $eventId);
            $query->whereIn('event_id', $eventIds);
        }

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $sort = $this->extractSort($request);

        foreach ($sort as $field => $order) {
            $query->orderBy(Str::snake($field), $order);
        }

        $total = $query->count();

        $results = $query->with($this->include)
                         ->limit($limit)
                         ->offset($offset)
                         ->get();

        $document->addPaginationLinks(
            $this->url->to('api')->route('bragalotto.picks.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $total
        );
        
        $document->setMeta(['total' => $total]);

        return $results;
    }
}