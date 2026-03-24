<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColonyBuilding extends Model
{
    protected $table = 'colony_buildings';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['colony_id', 'building_id', 'level', 'status_points', 'ap_spend'];
}
