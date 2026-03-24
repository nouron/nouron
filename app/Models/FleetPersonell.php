<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetPersonell extends Model
{
    protected $table = 'fleet_personell';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['fleet_id', 'personell_id', 'count', 'is_cargo'];
}
