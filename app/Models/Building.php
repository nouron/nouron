<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Building extends Model
{
    protected $table = 'buildings';
    public $timestamps = false;
    protected $fillable = [
        'id', 'purpose', 'name', 'required_building_id', 'required_building_level',
        'prime_colony_only', 'row', 'column', 'max_level', 'ap_for_levelup', 'max_status_points',
    ];
}
