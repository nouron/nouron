<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for the run_objectives table.
 *
 * Each RunObjective represents one mission task drawn for a Phase-2 run.
 * Progress is tracked via current_value / streak_value, completion via completed_at.
 */
class RunObjective extends Model
{
    protected $table = 'run_objectives';

    public $timestamps = false;

    protected $fillable = [
        'run_id',
        'task_key',
        'target_value',
        'current_value',
        'streak_value',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'run_id' => 'integer',
            'target_value' => 'integer',
            'current_value' => 'integer',
            'streak_value' => 'integer',
            'completed_at' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function run(): BelongsTo
    {
        return $this->belongsTo(Run::class);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Whether this objective has been completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Progress as an integer percentage (0–100).
     */
    public function progressPct(): int
    {
        return min(100, (int) round($this->current_value / max(1, $this->target_value) * 100));
    }
}
