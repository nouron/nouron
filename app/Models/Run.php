<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'phase',
        'fail_reason',
    ];

    protected function casts(): array
    {
        return [
            'current_tick' => 'integer',
            'phase'        => 'integer',
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

    /**
     * The Phase-2 objectives assigned to this run.
     */
    public function objectives(): HasMany
    {
        return $this->hasMany(RunObjective::class, 'run_id');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isPhase1(): bool
    {
        return $this->phase === 1;
    }

    public function isPhase2(): bool
    {
        return $this->phase === 2;
    }

    /**
     * Maximum number of ticks allowed for this run.
     * Reads from the run's own settings JSON first, falls back to game config.
     */
    public function getTickLimit(): int
    {
        $settings = $this->settings ?? [];
        return (int) ($settings['tick_limit'] ?? config('game.run.tick_limit', 100));
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
