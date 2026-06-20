<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Write model for the glx_colonies table.
 *
 * Use this model (or DB::table('glx_colonies')) for any INSERT/UPDATE/DELETE
 * on colony data. For reads use Colony (v_glx_colonies passthrough).
 */
class ColonyRecord extends Model
{
    protected $table = 'glx_colonies';

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'user_id',
        'since_tick',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'since_tick' => 'integer',
        ];
    }
}
