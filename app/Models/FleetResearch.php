<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetResearch extends Model
{
    protected $table = 'fleet_knowledge';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['fleet_id', 'research_id', 'count', 'is_cargo'];
}
