<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent Colony model — reads from v_glx_colonies.
 *
 * Each player has exactly one colony on an implicit home site; there is no
 * navigable galaxy/system map (removed 2026-06). The view exposes the columns of
 * glx_colonies directly.
 *
 * READ-ONLY: v_glx_colonies is a SQLite view. All writes must target the
 * underlying glx_colonies table via DB::table('glx_colonies') or ColonyRecord.
 */
class Colony extends Model
{
    protected $table = 'v_glx_colonies';

    // Prevent accidental writes through this model (v_glx_colonies is a SQLite view).
    protected $guarded = ['*'];

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
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
}
