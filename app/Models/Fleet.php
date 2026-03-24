<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    protected $table = 'fleets';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['fleet', 'user_id', 'x', 'y', 'spot', 'artefact'];

    public function getCoords(): array
    {
        return [(int) $this->x, (int) $this->y, (int) $this->spot];
    }
}
