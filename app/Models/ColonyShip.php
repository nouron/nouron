<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColonyShip extends Model
{
    protected $table = 'colony_ships';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['colony_id', 'ship_id', 'level', 'status_points', 'ap_spend'];
}
