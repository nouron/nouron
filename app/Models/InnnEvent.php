<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the innn_events table.
 *
 * Events are game-generated notifications (e.g. tech level-up, trade route
 * completed) stored per user per tick. They differ from messages in that they
 * are created by game services rather than by other players.
 */
class InnnEvent extends Model
{
    protected $table      = 'innn_events';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'user',
        'tick',
        'event',
        'area',
        'parameters',
    ];

    protected function casts(): array
    {
        return [
            'tick' => 'integer',
            'user' => 'integer',
        ];
    }
}
