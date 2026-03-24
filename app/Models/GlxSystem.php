<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * GlxSystem — Eloquent model for star systems.
 *
 * Reads from v_glx_systems, a view joining glx_systems with glx_system_types.
 * The view adds type attributes (class, size, icon_url, image_url) that were
 * previously populated by the SystemFactory/SystemTable in Laminas.
 */
class GlxSystem extends Model
{
    protected $table = 'v_glx_systems';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'x',
        'y',
        'name',
        'type_id',
        'background_image_url',
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
            'size'      => 'integer',
        ];
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Returns [x, y, 0] matching the Laminas MapEntityInterface::getCoords() convention.
     */
    public function getCoords(): array
    {
        return [$this->x, $this->y, 0];
    }

    // ── Relationships ─────────────────────────────────────────────────────────

    /**
     * System objects (planets, moons, asteroid fields…) within ~50 units of
     * this system's coordinates are not stored with an explicit system_id FK;
     * the proximity-based lookup stays in GalaxyService.
     */
}
