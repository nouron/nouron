<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the runs table.
 *
 * A Run ties a User to a Colony for one Roguelike session. It tracks how far
 * the run has progressed (current_tick) and whether it is still ongoing.
 *
 * Status values: 'active' | 'completed' | 'failed'
 */
class Run extends Model
{
    protected $table = 'runs';

    protected $fillable = [
        'user_id',
        'colony_id',
        'current_tick',
        'status',
        'started_at',
        'ended_at',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'current_tick' => 'integer',
            'started_at'   => 'datetime',
            'ended_at'     => 'datetime',
            'settings'     => 'array',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * The user who owns this run.
     * Binds via user_id → user.user_id (non-default PK name).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * The colony this run is played on.
     */
    public function colony()
    {
        return $this->belongsTo(Colony::class, 'colony_id', 'id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Limit query to active runs only.
     *
     * Usage: Run::active()->where('user_id', $userId)->first()
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
