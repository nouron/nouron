<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for `trade_resources`.
 *
 * Composite PK: (colony_id, direction, resource_id).
 * Direction: 0 = buy offer, 1 = sell offer.
 * No timestamps — this is a game-state table managed directly via DB::table().
 */
class TradeResource extends Model
{
    protected $table = 'trade_resources';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'colony_id',
        'direction',
        'resource_id',
        'amount',
        'price',
        'restriction',
    ];

    protected function casts(): array
    {
        return [
            'colony_id'   => 'integer',
            'direction'   => 'integer',
            'resource_id' => 'integer',
            'amount'      => 'integer',
            'price'       => 'integer',
            'restriction' => 'integer',
        ];
    }
}
