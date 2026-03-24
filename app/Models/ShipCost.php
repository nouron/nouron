<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipCost extends Model
{
    protected $table = 'ship_costs';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['ship_id', 'resource_id', 'amount'];
}
