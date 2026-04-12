<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Research extends Model
{
    protected $table = 'knowledge';
    public $timestamps = false;
    protected $fillable = [
        'id', 'purpose', 'name', 'required_building_id', 'required_building_level',
        'row', 'column', 'ap_for_levelup', 'max_status_points',
    ];
}
