<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuildingCost extends Model
{
    protected $table = 'building_costs';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['building_id', 'resource_id', 'amount'];
}
