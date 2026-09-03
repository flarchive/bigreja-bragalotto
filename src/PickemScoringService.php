<?php

namespace Bigreja\Bragalotto;

use Bigreja\Bragalotto\Event;
use Bigreja\Bragalotto\Pick;
use Bigreja\Bragalotto\UserScore;
use Illuminate\Database\ConnectionInterface; // DÜZELTME: Facade yerine Interface

/**
 * Skorlama mantığını merkezileştiren servis sınıfı
 */
class PickemScoringService
{
    /**
     * @var ConnectionInterface
     */
    protected $db;

    /**
     * Veritabanı bağlantısını enjekte ediyoruz
     * @param ConnectionInterface $db
     */
    public function __construct(ConnectionInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Bir maçın sonucu girildiğinde tüm tahminleri ve skorları günceller
     */
    public function updateScoresForEvent(Event $event): void
    {
        // Transaction kullanarak veri bütünlüğünü sağlayalım
        // DÜZELTME: DB::transaction yerine $this->db->transaction
        $this->db->transaction(function () use ($event) {
            $this->updatePicksForEvent($event);
            $this->updateUserScoresForEvent($event);
        });
    }

    /**
     * Event için tüm pick'lerin doğruluğunu güncelle
     * Toplu güncelleme (mass update) kullanarak performansı artırıyoruz.
     */
    protected function updatePicksForEvent(Event $event): void
    {
        // Sonuç null ise (örn: iptal edildi veya geri alındı), is_correct null olmalı
        if ($event->result === null) {
            Pick::where('event_id', $event->id)->update(['is_correct' => null]);
            return;
        }

        // Doğru tahminleri işaretle
        Pick::where('event_id', $event->id)
            ->where('selected_outcome', $event->result)
            ->update(['is_correct' => true]);

        // Yanlış tahminleri işaretle
        Pick::where('event_id', $event->id)
            ->where('selected_outcome', '!=', $event->result)
            ->update(['is_correct' => false]);
    }

    /**
     * Bu event için pick yapan kullanıcıların score'larını güncelle
     */
    protected function updateUserScoresForEvent(Event $event): void
    {
        // Sadece bu maç için tahminde bulunan kullanıcıların ID'lerini al
        $userIds = Pick::where('event_id', $event->id)
            ->distinct()
            ->pluck('user_id');

        // Her kullanıcı için skoru yeniden hesapla
        foreach ($userIds as $userId) {
            $this->recalculateUserScore($userId);
        }
    }

    /**
     * Kullanıcının score'unu yeniden hesapla (Global Leaderboard - Sezon ID her zaman NULL)
     */
    public function recalculateUserScore(int $userId): void
    {
        // 1. İstatistikleri veritabanı seviyesinde hesapla (Daha performanslı)
        $stats = Pick::where('user_id', $userId)
            ->selectRaw('count(*) as total, sum(case when is_correct = 1 then 1 else 0 end) as correct')
            ->whereNotNull('is_correct') // Sadece sonuçlanmış maçlar
            ->first();

        $totalPicks = $stats ? (int)$stats->total : 0;
        $correctPicks = $stats ? (int)$stats->correct : 0;
        
        // Puanlama sistemi: Her doğru tahmin 1 puan
        $totalPoints = $correctPicks; 
        
        // Accuracy hesaplama
        $accuracy = $totalPicks > 0 ? round(($correctPicks / $totalPicks) * 100, 2) : 0;

        // 2. Skor kaydını güncelle veya oluştur (Sezon ID her zaman NULL)
        UserScore::updateOrCreate(
            [
                'user_id' => $userId,
                'season_id' => null, 
            ],
            [
                'total_points' => $totalPoints,
                'total_picks' => $totalPicks,
                'correct_picks' => $correctPicks,
                'accuracy' => $accuracy
            ]
        );
    }
}