<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\UrlGenerator;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Api\Serializer\EventSerializer;
use Bigreja\Bragalotto\Event;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListEventsController extends AbstractListController
{
    public $serializer = EventSerializer::class;
    public $include = ['homeTeam', 'awayTeam', 'week'];
    
    public $sortFields = ['matchDate'];

    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.view'); 

        $query = Event::query();
        $query->with($this->include);

        $filters = $this->extractFilter($request);

        if ($weekIdString = Arr::get($filters, 'week')) {
            $weekIds = explode(',', $weekIdString);
            
            $query->where(function ($q) use ($weekIds) {
                $ids = array_diff($weekIds, ['null']);
                
                if (!empty($ids)) {
                    $q->whereIn('week_id', $ids);
                } 
                elseif (!in_array('null', $weekIds)) {
                    $q->whereRaw('1=0');
                }

                if (in_array('null', $weekIds)) {
                    $q->orWhereNull('week_id');
                }
            });
        }

        if ($status = Arr::get($filters, 'status')) {
            $query->where('status', $status);
        }

        if ($teamId = Arr::get($filters, 'team')) {
            $query->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                      ->orWhere('away_team_id', $teamId);
            });
        }

        if ($competitionFilter = Arr::get($filters, 'competition')) {
            $ids = array_filter(explode(',', $competitionFilter));
            $query->whereHas('week', function ($q) use ($ids) {
                $q->whereIn('competition_id', $ids);
            });
        }
        
        $sort = $this->extractSort($request);
        $sortOrder = 'desc'; 

        if (is_array($sort) && !empty($sort)) {
            $sortField = ltrim(key($sort), '-');
            if ($sortField === 'matchDate') {
                $sortOrder = $sort[key($sort)];
            }
        }
        
        $query->orderBy('match_date', $sortOrder); 
        
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        
        $total = $query->count();
        
        $results = $query->limit($limit)->offset($offset)->get();
        
        $document->addPaginationLinks(
            $this->url->to('api')->route('bragalotto.events.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $total
        );

        $document->setMeta(['total' => $total]);

        return $results;
    }
}