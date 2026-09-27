<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for the run_objectives table.
 *
 * Each RunObjective represents one mission task drawn for a Phase-2 run.
 * Progress is tracked via current_value / streak_value, completion via completed_at.
 *
 * @property int $id
 * @property int $run_id
 * @property string $task_key
 * @property int $target_value
 * @property int $current_value
 * @property int $streak_value
 * @property int $best_streak_value
 * @property int|null $completed_at
 * @property-read Run $run
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
        'best_streak_value',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'run_id' => 'integer',
            'target_value' => 'integer',
            'current_value' => 'integer',
            'streak_value' => 'integer',
            'best_streak_value' => 'integer',
            'completed_at' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<Run, $this>
     */
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
     * Whether progress toward the target reaches at least $pct percent.
     *
     * Used by the Nexus checkpoints (GDD §15 "2 von 3"): a completed objective
     * counts as 100 %, streak objectives count with their best streak so far
     * (best_streak_value stays 0 for counter objectives). Integer comparison —
     * no rounding at the threshold.
     */
    public function reachesProgressPct(int $pct): bool
    {
        if ($this->completed_at !== null) {
            return true;
        }

        $value = max($this->current_value, $this->best_streak_value ?? 0);

        return $value * 100 >= $pct * max(1, $this->target_value);
    }

    /**
     * Progress as an integer percentage (0–100).
     */
    public function progressPct(): int
    {
        return min(100, (int) round($this->current_value / max(1, $this->target_value) * 100));
    }
}
