<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Advisor extends Model
{
    protected $table      = 'advisors';
    public    $timestamps = false;

    protected $fillable = [
        'user_id', 'personell_id', 'colony_id',
        'rank', 'active_ticks', 'unavailable_until_tick',
    ];

    protected $casts = [
        'rank'         => 'integer',
        'active_ticks' => 'integer',
    ];

    public function personell(): BelongsTo
    {
        return $this->belongsTo(Personell::class);
    }

    public function colony(): BelongsTo
    {
        return $this->belongsTo(Colony::class);
    }

    public function getApPerTick(): int
    {
        $map = config('game.advisor.ap_per_rank', [1 => 4, 2 => 7, 3 => 12]);
        return $map[$this->rank] ?? 4;
    }

    public function isUnemployed(): bool
    {
        return $this->colony_id === null;
    }

    public function isAvailable(?int $currentTick = null): bool
    {
        if ($this->unavailable_until_tick !== null) {
            return $currentTick !== null && $currentTick > $this->unavailable_until_tick;
        }
        return true;
    }
}
