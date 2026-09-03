<?php

namespace Bigreja\Bragalotto\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Job\RecalculateUserScoreJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteEventController extends AbstractDeleteController
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @param Dispatcher $bus
     */
    public function __construct(Dispatcher $bus)
    {
        $this->bus = $bus;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('bragalotto.manage');

        $id = Arr::get($request->getQueryParams(), 'id');
        $event = Event::findOrFail($id);

        $affectedUserIds = Pick::where('event_id', $id)
            ->distinct()
            ->pluck('user_id')
            ->all();

        $event->delete();

        foreach ($affectedUserIds as $userId) {
            $this->bus->dispatch(
                new RecalculateUserScoreJob($userId)
            );
        }
    }
}