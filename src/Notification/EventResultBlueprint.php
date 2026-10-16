<?php

namespace Bigreja\Bragalotto\Notification;

use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Database\AbstractModel;
use Flarum\User\User;
use Bigreja\Bragalotto\Event;

class EventResultBlueprint implements BlueprintInterface, AlertableInterface
{
    public $event;

    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    public function getSubject(): ?AbstractModel
    {
        return $this->event;
    }

    public function getSender(): ?User
    {
        return null;
    }

    public function getFromUser(): ?User
    {
        return null;
    }

    public function getData(): mixed
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

    public static function getType(): string
    {
        return 'bragalotto_event_result';
    }

    public static function getSubjectModel(): string
    {
        return Event::class;
    }
}