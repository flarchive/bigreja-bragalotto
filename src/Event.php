<?php

namespace Bigreja\Bragalotto;

use Carbon\Carbon;
use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;

/**
 * @property int $id
 * @property int|null $week_id
 * @property int $home_team_id
 * @property int $away_team_id
 * @property \Carbon\Carbon $match_date
 * @property \Carbon\Carbon $cutoff_date
 * @property bool $allow_draw
 * @property string $status
 * @property int|null $home_score
 * @property int|null $away_score
 * @property string|null $result
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Week|null $week
 * @property-read Team $homeTeam
 * @property-read Team $awayTeam
 * @property-read \Illuminate\Database\Eloquent\Collection|Pick[] $picks
 */
class Event extends AbstractModel
{
    use ScopeVisibilityTrait;

    public $timestamps = true;

    protected $table = 'bragalotto_events';

    protected $fillable = [
        'week_id',
        'home_team_id',
        'away_team_id',
        'match_date',
        'cutoff_date',
        'allow_draw',
        'status',
        'home_score',
        'away_score',
        'result',
        'external_id',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'cutoff_date' => 'datetime',
        'allow_draw' => 'boolean',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CLOSED = 'closed';
    const STATUS_FINISHED = 'finished';

    // Result constants
    const RESULT_HOME = 'home';
    const RESULT_AWAY = 'away';
    const RESULT_DRAW = 'draw';

    /**
     * Boot the model - register event listeners
     * Modern Flarum 1.8 way using booted() instead of boot()
     */
    protected static function booted()
    {
        // Auto-calculate result when scores are set
        static::saving(function (Event $event) {
            // Skorlar set edildiyse result'ı hesapla
            if ($event->isDirty(['home_score', 'away_score']) && 
                $event->home_score !== null && 
                $event->away_score !== null) {
                $event->result = $event->calculateResult();
                
                // Otomatik olarak finished'a çek
                if ($event->result !== null && $event->status === self::STATUS_SCHEDULED) {
                    $event->status = self::STATUS_FINISHED;
                }
            }
            
            // Cutoff geçtiyse otomatik kapat
            if ($event->status === self::STATUS_SCHEDULED && 
                Carbon::now()->isAfter($event->cutoff_date)) {
                $event->status = self::STATUS_CLOSED;
            }
        });
    }

    /**
     * Relationships
     */
    public function week()
    {
        return $this->belongsTo(Week::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function picks()
    {
        return $this->hasMany(Pick::class);
    }

    /**
     * Query Scopes
     */
    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopeFinished($query)
    {
        return $query->where('status', self::STATUS_FINISHED);
    }

    public function scopePickable($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
                     ->where('cutoff_date', '>', Carbon::now());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('match_date', '>', Carbon::now())
                     ->orderBy('match_date', 'asc');
    }

    public function scopePast($query)
    {
        return $query->where('match_date', '<', Carbon::now())
                     ->orderBy('match_date', 'desc');
    }

    /**
     * Helper Methods
     */
    public function canPick(): bool
    {
        return $this->status === self::STATUS_SCHEDULED && 
               Carbon::now()->isBefore($this->cutoff_date);
    }

    public function isFinished(): bool
    {
        return $this->status === self::STATUS_FINISHED;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function hasResult(): bool
    {
        return $this->result !== null;
    }

    public function hasScores(): bool
    {
        return $this->home_score !== null && $this->away_score !== null;
    }

    /**
     * Calculate the result based on scores
     */
    public function calculateResult(): ?string
    {
        if (!$this->hasScores()) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return self::RESULT_HOME;
        } elseif ($this->away_score > $this->home_score) {
            return self::RESULT_AWAY;
        } else {
            return self::RESULT_DRAW;
        }
    }

    /**
     * Get pick for specific user
     */
    public function getPickForUser(int $userId): ?Pick
    {
        return $this->picks()->where('user_id', $userId)->first();
    }

    /**
     * Check if user has made a pick for this event
     */
    public function hasPickFromUser(int $userId): bool
    {
        return $this->picks()->where('user_id', $userId)->exists();
    }
}