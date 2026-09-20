<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for bar_information_encounters (Deva & Lenn Vier-Ausgänge-Pool,
 * GDD §12 "Deva & Lenn — taktische Information", A42).
 */
class BarInformationEncounter extends Model
{
    protected $table = 'bar_information_encounters';

    protected $fillable = [
        'colony_id',
        'character_slug',
        'outcome_key',
        'created_tick',
        'expires_tick',
        'is_resolved',
        'outcome',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'outcome' => 'array',
    ];
}
