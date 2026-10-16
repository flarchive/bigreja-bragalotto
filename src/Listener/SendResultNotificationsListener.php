<?php

namespace Bigreja\Bragalotto\Listener;

use Flarum\Notification\NotificationSyncer;
use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Notification\EventResultBlueprint;
use Bigreja\Bragalotto\Pick;
use Illuminate\Database\Eloquent\Events\Saved;

/**
 * Event result set edildiğinde kullanıcılara bildirim gönderir
 */
class SendResultNotificationsListener
{
    protected $notifications;

    public function __construct(NotificationSyncer $notifications)
    {
        $this->notifications = $notifications;
    }

    public function handle(Saved $event)
    {
        // Sadece Event modellerini işle
        if (!($event->model instanceof Event)) {
            return;
        }

        $eventModel = $event->model;

        // Sadece result set edildiğinde ve finished durumunda
        if ($eventModel->result === null || $eventModel->status !== Event::STATUS_FINISHED) {
            return;
        }

        // Result veya status değişmediyse bildirim gönderme
        if (!$eventModel->wasChanged(['result', 'status'])) {
            return;
        }

        $this->sendNotifications($eventModel);
    }

    protected function sendNotifications(Event $event): void
    {
        // Bu event için pick yapan kullanıcıları getir
        $picks = Pick::where('event_id', $event->id)
            ->with('user')
            ->get();

        $users = $picks->pluck('user')->filter();

        if ($users->isEmpty()) {
            return;
        }

        // Hepsine bildirim gönder
        $this->notifications->sync(
            new EventResultBlueprint($event),
            $users->all()
        );
    }
}