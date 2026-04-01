<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetShip extends Model
{
    protected $table = 'fleet_ships';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['fleet_id', 'ship_id', 'count', 'is_cargo', 'status_points'];
}
