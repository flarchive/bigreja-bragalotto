<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecalculateAllScoresController implements RequestHandlerInterface
{
    /**
     * @var BusDispatcher
     */
    protected $bus;

    public function __construct(BusDispatcher $bus)
    {
        $this->bus = $bus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        // Chunking kullanarak belleği koruyoruz. 
        // Her seferinde 100 kullanıcı ID'si çekip işliyoruz.
        $totalJobs = 0;

        Pick::query()
            ->distinct()
            ->select('user_id')
            ->chunk(100, function ($picks) use (&$totalJobs) {
                foreach ($picks as $pick) {
                    $this->bus->dispatch(new RecalculateUserScoreJob($pick->user_id));
                    $totalJobs++;
                }
            });
        
        return new JsonResponse([
            'status' => 'dispatched',
            'jobCount' => $totalJobs,
            'message' => $totalJobs . ' users queued for recalculation.'
        ]);
    }
}