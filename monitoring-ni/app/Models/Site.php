<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'adminUrl',
        'last_http_code',
        'total_pings',
        'failed_pings',
        'total_downtime_seconds',
        'avg_ping',
        'last_success',
    ];

    protected function casts(): array
    {
        return [
            'last_success' => 'datetime',
            'total_pings' => 'integer',
            'failed_pings' => 'integer',
            'total_downtime_seconds' => 'integer',
            'avg_ping' => 'integer',
            'last_http_code' => 'integer',
        ];
    }

    public function pings(): HasMany
    {
        // Important : on retourne la relation triée par pinged_at DESC
        return $this->hasMany(Ping::class)->orderByDesc('pinged_at');
    }

    public function latestPing(): HasOne
    {
        // Permet d'accéder directement au dernier ping
        return $this->hasOne(Ping::class)->latestOfMany('pinged_at');
    }

    public function siteUpdateItems(): HasMany
    {
        return $this->hasMany(SiteUpdateItem::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_sites');
    }

    public function summary(): HasOne
    {
        return $this->hasOne(SiteSummary::class);
    }
}
