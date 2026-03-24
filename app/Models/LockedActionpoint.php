<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LockedActionpoint extends Model
{
    protected $table = 'locked_actionpoints';
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['tick', 'colony_id', 'personell_id', 'spend_ap'];
}
