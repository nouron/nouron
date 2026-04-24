<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColonyTile extends Model
{
    protected $table = 'colony_tiles';
    public $timestamps = true;

    protected $fillable = [
        'colony_id',
        'q',
        'r',
        'ring',
        'tile_type',
        'event_type',
        'is_ring_unlocked',
        'is_explored',
        'is_deep_scanned',
        'resource_amount',
        'resource_max',
    ];

    protected function casts(): array
    {
        return [
            'q'               => 'integer',
            'r'               => 'integer',
            'ring'            => 'integer',
            'is_ring_unlocked' => 'boolean',
            'is_explored'     => 'boolean',
            'is_deep_scanned' => 'boolean',
            'resource_amount' => 'integer',
            'resource_max'    => 'integer',
        ];
    }

    public function colony()
    {
        return $this->belongsTo(Colony::class, 'colony_id');
    }
}
