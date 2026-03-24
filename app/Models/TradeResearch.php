<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for `trade_researches`.
 *
 * Composite PK: (colony_id, direction, research_id).
 * Direction: 0 = buy offer, 1 = sell offer.
 * No timestamps — this is a game-state table managed directly via DB::table().
 */
class TradeResearch extends Model
{
    protected $table = 'trade_researches';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'colony_id',
        'direction',
        'research_id',
        'amount',
        'price',
        'restriction',
    ];

    protected function casts(): array
    {
        return [
            'colony_id'   => 'integer',
            'direction'   => 'integer',
            'research_id' => 'integer',
            'amount'      => 'integer',
            'price'       => 'integer',
            'restriction' => 'integer',
        ];
    }
}
