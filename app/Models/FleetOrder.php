<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetOrder extends Model
{
    protected $table = 'fleet_orders';
    // Composite PK (tick, fleet_id) — Eloquent cannot handle this natively.
    // Setting $primaryKey to null and $incrementing = false disables PK-based lookups.
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['tick', 'fleet_id', 'order', 'coordinates', 'data', 'was_processed', 'has_notified'];
}
