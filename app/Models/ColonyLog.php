<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColonyLog extends Model
{
    protected $table = 'colony_log';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'user',
        'tick',
        'event',
        'area',
        'parameters',
        'is_read',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'tick' => 'integer',
            'user' => 'integer',
            'is_read' => 'boolean',
        ];
    }
}
