<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColonyPersonell extends Model
{
    protected $table = 'colony_personell';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['colony_id', 'personell_id', 'level', 'status_points'];
}
