<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Backup extends Model
{
    use HasFactory;

    // $timestamps est true par défaut, ce qui est correct car nous avons
    // inclus $table->timestamps() dans la migration des backups.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'backup_time',
        'source',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'backup_time' => 'datetime',
        ];
    }

    /**
     * Get the site that this backup belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}