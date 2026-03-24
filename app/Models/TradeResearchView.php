<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model backed by the `v_trade_researches` database view.
 *
 * The view joins trade_researches + glx_colonies + user, adding:
 * colony name, username, user_id, race_id, faction_id.
 *
 * This model is used exclusively for querying; all writes go through
 * the underlying trade_researches table via TradeResearch or DB::table().
 */
class TradeResearchView extends Model
{
    protected $table = 'v_trade_researches';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [];
}
