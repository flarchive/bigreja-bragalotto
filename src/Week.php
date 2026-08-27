<?php

namespace Bigreja\Bragalotto;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;

/**
 * @property int $id
 * @property string $name
 * @property int|null $competition_id
 * @property int|null $week_number
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Week extends AbstractModel
{
    use ScopeVisibilityTrait;

    // YENİ EKLENDİ: Zaman damgalarını otomatik yönet
    public $timestamps = true;

    protected $table = 'bragalotto_weeks';

    protected $fillable = ['name', 'competition_id', 'week_number', 'start_date', 'end_date', 'external_id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the season this week belongs to
     */
    public function competition()
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Get events in this week
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }
}