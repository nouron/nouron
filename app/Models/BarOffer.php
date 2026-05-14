<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for bar_offers.
 *
 * NPC traders leave offers at the Bar/Cantina. Each offer specifies a give/get
 * resource pair and is valid until expires_tick. The player can accept or decline.
 */
class BarOffer extends Model
{
    protected $table = 'bar_offers';

    protected $fillable = [
        'colony_id',
        'give_resource_id',
        'give_amount',
        'get_resource_id',
        'get_amount',
        'expires_tick',
        'is_accepted',
    ];

    protected $casts = [
        'is_accepted' => 'boolean',
    ];
}
