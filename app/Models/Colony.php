<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Colony model — reads from v_glx_colonies (a view joining glx_colonies
 * and glx_system_objects). Includes coordinates (x, y) and planetary properties
 * (type_id, sight, density, radiation) from the joined system object.
 *
 * Writes go through the underlying glx_colonies table (no write ops yet).
 */
class Colony extends Model
{
    protected $table = 'v_glx_colonies';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'system_object_id',
        'spot',
        'user_id',
        'since_tick',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'spot'       => 'integer',
            'since_tick' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function resources()
    {
        return $this->hasMany(ColonyResource::class, 'colony_id', 'id');
    }

    public function getCoords(): array
    {
        return [(int) $this->x, (int) $this->y, (int) $this->spot];
    }
}
