<?php

namespace Bigreja\Bragalotto\Listener;

use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\PickemScoringService;
use Bigreja\Bragalotto\Job\ProcessEventResultsJob; // EKLENDİ (Yeni Job'umuz)
use Illuminate\Database\Eloquent\Events\Saved;
use Illuminate\Contracts\Bus\Dispatcher; // EKLENDİ (Kuyruk için)

class UpdateUserScoresListener
{
    protected $scoringService; // Bu artık kullanılmayacak ama kalsa da olur
    protected $bus; // EKLENDİ

    public function __construct(PickemScoringService $scoringService, Dispatcher $bus) // Dispatcher eklendi
    {
        $this->scoringService = $scoringService;
        $this->bus = $bus; // EKLENDİ
    }

    public function handle(Saved $event)
    {
        if (!($event->model instanceof Event)) {
            return;
        }

        $eventModel = $event->model;

        if (!$eventModel->wasChanged(['result', 'home_score', 'away_score'])) {
            return;
        }

        if ($eventModel->result === null) {
            return;
        }
        
        // ---- AĞIR İŞİ ÇAĞIRMAK YERİNE ----
        // $this->scoringService->updateScoresForEvent($eventModel);

        // ---- YENİ: İŞİ KUYRUĞA EKLE ----
        $this->bus->dispatch(
            new ProcessEventResultsJob($eventModel->id)
        );
    }
}