<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for `colony_resources` (composite PK: resource_id + colony_id).
 * Tracks how many units of each resource a colony currently holds.
 */
class ColonyResource extends Model
{
    protected $table = 'colony_resources';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['resource_id', 'colony_id', 'amount'];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function colony()
    {
        return $this->belongsTo(Colony::class, 'colony_id', 'id');
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class, 'resource_id', 'id');
    }
}
