<?php

namespace Bigreja\Bragalotto\Job;

use Flarum\Queue\AbstractJob;
use Flarum\Notification\NotificationSyncer;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\Notification\EventResultBlueprint;
use Bigreja\Bragalotto\PickemScoringService;

class ProcessEventResultsJob extends AbstractJob
{
    /**
     * @var int
     */
    protected $eventId;

    public function __construct(int $eventId)
    {
        $this->eventId = $eventId;
    }

    public function handle(PickemScoringService $scoringService, NotificationSyncer $notifications)
    {
        $event = Event::find($this->eventId);

        if (!$event || !$event->isFinished()) {
            return;
        }

        // 1. Puanları yeniden hesapla (Bu, PickemScoringService'ten gelen mantık)
        $scoringService->updateScoresForEvent($event);

        // 2. Bildirimleri gönder (Bu, EnterEventResultController'dan taşınan mantık)
        $picks = Pick::where('event_id', $event->id)
            ->with('user')
            ->get();

        $users = $picks->pluck('user')->filter();

        if ($users->isEmpty()) {
            return;
        }

        $notifications->sync(
            new EventResultBlueprint($event),
            $users->all()
        );
    }
}