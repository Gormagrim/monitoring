<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteUpdateItem extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped with created_at/updated_at.
     * Nous utilisons 'item_detected_at' à la place.
     *
     * @var bool
     */
    public $timestamps = false; // Si vous n'avez pas inclus $table->timestamps() dans la migration

    // Si vous *avez* inclus $table->timestamps() dans la migration de site_update_items
    // alors laissez $timestamps à true (ou supprimez cette ligne car true est la valeur par défaut).
    // Et ajoutez 'item_detected_at' aux $fillable mais pas aux $casts car il ne sera pas géré par Laravel
    // comme un timestamp automatique.

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'site_id',
        'item_name',
        'version',
        'type',
        'item_detected_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_detected_at' => 'datetime',
        ];
    }

    /**
     * Get the site that this update item belongs to.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}