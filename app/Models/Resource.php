<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `resources` table — resource type definitions.
 * (9 types: credits, supply, water, ferum, silicates, ena, lho, aku, moral)
 */
class Resource extends Model
{
    protected $table = 'resources';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = ['name', 'abbreviation', 'trigger', 'is_tradeable', 'start_amount', 'icon'];

    protected function casts(): array
    {
        return [
            'is_tradeable' => 'boolean',
        ];
    }
}
