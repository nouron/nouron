<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    protected $table = 'fleets';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['fleet', 'user_id', 'x', 'y', 'spot', 'artefact'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function getCoords(): array
    {
        return [(int) $this->x, (int) $this->y, (int) $this->spot];
    }
}
