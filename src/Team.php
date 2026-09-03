<?php

namespace Bigreja\Bragalotto;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Settings\SettingsRepositoryInterface; // URL oluşturmak için eklendi
use Illuminate\Support\Str; // URL kontrolü için eklendi

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $logo_path
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read string|null $logo_url
 */
class Team extends AbstractModel
{
    use ScopeVisibilityTrait;

    // YENİ EKLENDİ: Zaman damgalarını otomatik yönet
    public $timestamps = true;

    protected $table = 'bragalotto_teams';

    protected $fillable = ['name', 'slug', 'logo_path', 'external_id'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get home events for this team
     */
    public function homeEvents()
    {
        return $this->hasMany(Event::class, 'home_team_id');
    }

    /**
     * Get away events for this team
     */
    public function awayEvents()
    {
        return $this->hasMany(Event::class, 'away_team_id');
    }

    /**
     * YENİ: Logo URL'si için Accessor.
     * Bu metot, logo_path'in tam bir URL mi yoksa göreceli bir yol mu 
     * olduğunu kontrol ederek doğru URL'yi döndürür.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        // Eğer logo_path zaten tam bir URL ise (http:// veya https:// ile başlıyorsa)
        // doğrudan onu döndür.
        if (Str::startsWith($this->logo_path, ['http://', 'https://'])) {
            return $this->logo_path;
        }

        // Eğer göreceli bir yol ise (örn: assets/teams/logo.png),
        // Flarum'un temel URL'si ile birleştir.
        // Flarum 1.8+ için Ayarlar deposunu (SettingsRepository) kullanmak standart yoldur.
        $baseUrl = resolve(SettingsRepositoryInterface::class)->get('base_url');
        
        return rtrim($baseUrl, '/') . '/' . ltrim($this->logo_path, '/');
    }
}