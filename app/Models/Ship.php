<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ship extends Model
{
    protected $table = 'ships';
    public $timestamps = false;
    protected $fillable = [
        'id', 'purpose', 'name', 'required_building_id', 'required_building_level',
        'required_research_id', 'required_research_level', 'prime_colony_only',
        'row', 'column', 'ap_for_levelup', 'max_status_points', 'moving_speed',
    ];
}
