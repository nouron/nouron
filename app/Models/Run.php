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
        'nexus_debt',
        'phase2_start_tick',
    ];

    protected function casts(): array
    {
        return [
            'current_tick' => 'integer',
            'phase' => 'integer',
            'nexus_debt' => 'integer',
            'phase2_start_tick' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'settings' => 'array',
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
     * Number of sols elapsed since Phase-2 started.
     *
     * Returns 0 when phase2_start_tick is not yet set (i.e. still in Phase 1).
     */
    public function getPhase2Sol(): int
    {
        return $this->current_tick - ($this->phase2_start_tick ?? $this->current_tick);
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
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Limit query to completed runs only.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    /**
     * Limit query to failed runs only.
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    /**
     * Limit query to runs belonging to a specific user.
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
