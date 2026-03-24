<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetResource extends Model
{
    protected $table = 'fleet_resources';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['fleet_id', 'resource_id', 'amount'];
}
