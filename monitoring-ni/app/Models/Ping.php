<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     * Laravel essaie de deviner "pings", mais on peut être explicite.
     *
     * @var string
     */
    // protected $table = 'pings'; // Généralement pas nécessaire si le nom suit les conventions

    /**
     * Indicates if the model should be timestamped.
     * Nous avons 'pinged_at' au lieu de created_at/updated_at gérés par Laravel.
     *
     * @var bool
     */
    public $timestamps = false; // Important car nous n'avons pas created_at/updated_at

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'ping_time',
        'http_status',
        'curl_error_code',
        'pinged_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pinged_at' => 'datetime',
            'ping_time' => 'integer',
            'http_status' => 'integer',
            'curl_error_code' => 'integer',
        ];
    }

    /**
     * Get the site that owns the ping.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}