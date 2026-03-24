<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model backed by the `v_trade_resources` database view.
 *
 * The view joins trade_resources + glx_colonies + user, adding:
 * colony name, username, user_id, race_id, faction_id.
 *
 * This model is used exclusively for querying; all writes go through
 * the underlying trade_resources table via TradeResource or DB::table().
 */
class TradeResourceView extends Model
{
    protected $table = 'v_trade_resources';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [];
}
