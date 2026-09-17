<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for bar_concerns (Charakter-Anliegen, GDD §12 Kanal 1, A41).
 */
class BarConcern extends Model
{
    protected $table = 'bar_concerns';

    protected $fillable = [
        'colony_id',
        'character_slug',
        'created_tick',
        'expires_tick',
        'is_resolved',
        'success',
        'outcome',
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'success' => 'boolean',
        'outcome' => 'array',
    ];
}
