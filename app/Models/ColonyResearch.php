<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColonyResearch extends Model
{
    protected $table = 'colony_researches';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['colony_id', 'research_id', 'level', 'status_points', 'ap_spend'];
}
