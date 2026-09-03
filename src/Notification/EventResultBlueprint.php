<?php

namespace Bigreja\Bragalotto\Notification;

use Flarum\Notification\Blueprint\BlueprintInterface;
use Bigreja\Bragalotto\Event;

class EventResultBlueprint implements BlueprintInterface
{
    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getSubject()
    {
        return $this->event;
    }

    public function getSender()
    {
        return null;
    }

    public function getFromUser()
    {
        return null;
    }

    public function getData()
    {
        // GÜNCELLENDİ: Sabit 'Home'/'Away' yerine translator kullanımı.
        // Not: Bu metot email template'lerinde de kullanıldığı için burada çevirmek mantıklıdır.
        $translator = resolve('translator');

        return [
            'eventId' => $this->event->id,
            'homeTeam' => $this->event->homeTeam ? $this->event->homeTeam->name : $translator->trans('bigreja-bragalotto.lib.common.home'),
            'awayTeam' => $this->event->awayTeam ? $this->event->awayTeam->name : $translator->trans('bigreja-bragalotto.lib.common.away'),
            'result' => $this->event->result,
            'homeScore' => $this->event->home_score,
            'awayScore' => $this->event->away_score,
        ];
    }

    public static function getType()
    {
        return 'bragalotto_event_result';
    }

    public static function getSubjectModel()
    {
        return Event::class;
    }
}