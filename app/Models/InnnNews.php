<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the innn_news table.
 *
 * INNN News are editorial/system-generated news items visible to all players.
 * Valid topic values: economy, politics, diplomacy, culture, sports, misc.
 * (Enforced by a SQLite trigger on the table — see migration.)
 */
class InnnNews extends Model
{
    protected $table      = 'innn_news';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'tick',
        'icon',
        'topic',
        'headline',
        'text',
    ];

    protected function casts(): array
    {
        return [
            'tick' => 'integer',
        ];
    }
}
