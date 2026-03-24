<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GlxSystemObject — Eloquent model for planetary/spatial objects.
 *
 * Reads from v_glx_system_objects, a view joining glx_system_objects with
 * glx_system_object_types. The view adds type string and image_url, which
 * were previously populated by SystemObjectFactory/SystemObjectTable in Laminas.
 *
 * Objects are spatially associated with a system via proximity (x/y within
 * system_view_config range). There is no system_id FK column.
 */
class GlxSystemObject extends Model
{
    protected $table = 'v_glx_system_objects';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'x',
        'y',
        'name',
        'type_id',
        'sight',
        'density',
        'radiation',
    ];

    protected function casts(): array
    {
        return [
            'x'         => 'integer',
            'y'         => 'integer',
            'type_id'   => 'integer',
            'sight'     => 'integer',
            'density'   => 'integer',
            'radiation' => 'integer',
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Returns [x, y, 0] matching the Laminas MapEntityInterface::getCoords() convention.
     */
    public function getCoords(): array
    {
        return [$this->x, $this->y, 0];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * Colonies built on this system object.
     * Reads from glx_colonies (underlying table, not the view).
     */
    public function colonies(): HasMany
    {
        return $this->hasMany(Colony::class, 'system_object_id', 'id');
    }
}
