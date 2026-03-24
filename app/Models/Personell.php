<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personell extends Model
{
    protected $table = 'personell';
    public $timestamps = false;
    protected $fillable = [
        'id', 'purpose', 'name', 'required_building_id', 'required_building_level',
        'row', 'column', 'max_status_points',
    ];
}
