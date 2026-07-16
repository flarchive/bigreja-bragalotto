<?php

namespace Bigreja\Bragalotto;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\User\User;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $season_id
 * @property int $total_points
 * @property int $total_picks
 * @property int $correct_picks
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read User $user
 * @property-read Season|null $season
 */
class UserScore extends AbstractModel
{
    use ScopeVisibilityTrait;

    protected $table = 'bragalotto_user_scores';

    public $timestamps = true; 
	
    /**
     * N+1 sorununu çözmek için runtime sırasında hesaplanan sıralamayı tutar.
     * Veritabanında böyle bir kolon yoktur.
     * @var int|null
     */
    public $calculatedRank = null;

    protected $fillable = [
        'user_id',
        'season_id',
        'total_points',
        'total_picks',
        'correct_picks',
    ];

    protected $casts = [
        'total_points' => 'integer',
        'total_picks' => 'integer',
        'correct_picks' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    /**
     * Query Scopes
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForSeason($query, ?int $seasonId)
    {
        return $query->where('season_id', $seasonId);
    }

    public function scopeTopScorers($query, int $limit = 10)
    {
        return $query->orderBy('total_points', 'desc')
                     ->orderBy('correct_picks', 'desc')
                     ->orderBy('total_picks', 'asc')
                     ->limit($limit);
    }

    /**
     * Helper Methods
     */
    public function getIncorrectPicks(): int
    {
        return $this->total_picks - $this->correct_picks;
    }

    public function getAccuracyPercentage(): float
    {
        if ($this->total_picks > 0) {
            return round(($this->correct_picks / $this->total_picks) * 100, 2);
        }
        return 0;
    }

    public function hasAnyPicks(): bool
    {
        return $this->total_picks > 0;
    }

    /**
     * YENİ: Kullanıcının sıralamasını hesaplar.
     * Mantık: Benden yüksek puanı olanlar + (Puanı eşit olup daha çok doğrusu olanlar) + 1
     */
    public function calculateRank(): int
    {
        // Sadece global sıralama (season_id IS NULL)
        $query = self::query()->whereNull('season_id');

        $higherPoints = $query->clone()
            ->where('total_points', '>', $this->total_points)
            ->count();

        $equalPointsBetterTieBreaker = $query->clone()
            ->where('total_points', '=', $this->total_points)
            ->where('correct_picks', '>', $this->correct_picks)
            ->count();
            
        return $higherPoints + $equalPointsBetterTieBreaker + 1;
    }
}