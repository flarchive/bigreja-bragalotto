<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Bigreja\Bragalotto\Api\Serializer\UserScoreSerializer;
use Bigreja\Bragalotto\UserScore;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListLeaderboardController extends AbstractListController
{
    public $serializer = UserScoreSerializer::class;

    public $include = ['user'];
    
    public $limit = 20;

    protected $url;

    public function __construct(UrlGenerator $url)
    {
        $this->url = $url;
    }

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.view');
        
        $query = UserScore::query();
        $query->whereNull('season_id');
        
        $filters = $this->extractFilter($request);
        $filterUser = Arr::get($filters, 'user');

        if ($filterUser) {
            $query->where('user_id', $filterUser);
        }

        $query->orderBy('total_points', 'desc')
              ->orderBy('correct_picks', 'desc');
        
        $query->with(['user']);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $total = $query->count();

        $results = $query->limit($limit)
                         ->offset($offset)
                         ->get();

        // N+1 Çözümü: Eğer belirli bir kullanıcı filtrelenmiyorsa (Genel Liste),
        // sıralamayı veritabanı yerine burada hesapla.
        if (!$filterUser) {
            $startRank = $offset + 1;
            $results->each(function ($score, $index) use ($startRank) {
                $score->calculatedRank = $startRank + $index;
            });
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('bragalotto.leaderboard.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $total
        );
        
        $document->setMeta(['total' => $total]);

        return $results;
    }
}