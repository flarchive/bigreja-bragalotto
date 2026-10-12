<?php

namespace Bigreja\Bragalotto;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;

/**
 * @property int         $id
 * @property string|null $external_id
 * @property int         $season_id
 * @property string      $name
 * @property string      $slug
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Season                                             $season
 * @property-read \Illuminate\Database\Eloquent\Collection|Week[]   $weeks
 */
class Competition extends AbstractModel
{
    use ScopeVisibilityTrait;

    public $timestamps = true;

    protected $table = 'bragalotto_competitions';

    protected $fillable = ['external_id', 'season_id', 'name', 'slug'];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function weeks()
    {
        return $this->hasMany(Week::class);
    }
}
