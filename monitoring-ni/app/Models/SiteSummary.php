<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteSummary extends Model
{
    use HasFactory;

    // $timestamps est true par défaut, ce qui est correct car nous avons
    // inclus $table->timestamps() dans la migration des site_summaries.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'pending_update_count',
        'last_backup_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_backup_at' => 'datetime',
            'pending_update_count' => 'integer',
        ];
    }

    /**
     * Get the site that this summary belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}