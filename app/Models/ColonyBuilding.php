<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $instance_id
 */
class ColonyBuilding extends Model
{
    protected $table = 'colony_buildings';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['colony_id', 'building_id', 'instance_id', 'level', 'status_points', 'ap_spend'];
}
