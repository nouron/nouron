<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for bar_encounters (Cantina-Begegnungspool, GDD §12 Kanal 1).
 */
class BarEncounter extends Model
{
    protected $table = 'bar_encounters';

    protected $fillable = [
        'colony_id',
        'type',
        'give_resource_id',
        'give_amount',
        'win_chance',
        'credits_amount',
        'duration_ticks',
        'expires_tick',
        'is_accepted',
        'resolved',
        'won',
        'ends_tick',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
        'resolved' => 'boolean',
        'won' => 'boolean',
    ];
}
