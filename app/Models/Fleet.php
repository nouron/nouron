<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    protected $table = 'fleets';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = ['fleet', 'user_id', 'x', 'y', 'grid_x', 'grid_y', 'artefact'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public function getCoords(): array
    {
        // Returns [x, y] galaxy coordinates. The legacy third element (spot)
        // has been removed — callers that relied on [x, y, spot] must be updated.
        return [(int) $this->x, (int) $this->y];
    }
}
