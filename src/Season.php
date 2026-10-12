<?php

namespace Bigreja\Bragalotto;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Season extends AbstractModel
{
    use ScopeVisibilityTrait;

    // YENİ EKLENDİ: Zaman damgalarını otomatik yönet
    public $timestamps = true;

    protected $table = 'bragalotto_seasons';

    protected $fillable = ['name', 'slug', 'start_date', 'end_date', 'external_id'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get competitions in this season
     */
    public function competitions()
    {
        return $this->hasMany(Competition::class);
    }

    /**
     * Get weeks in this season
     */
    public function weeks()
    {
        return $this->hasMany(Week::class);
    }

    /**
     * Get user scores for this season
     */
    public function userScores()
    {
        return $this->hasMany(UserScore::class);
    }
}