<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Read-only Eloquent model for the v_innn_messages view.
 *
 * The view joins innn_messages with the user table to expose sender and
 * recipient usernames as `sender` and `recipient` columns. All writes must
 * target InnnMessage (the underlying table), not this model.
 */
class InnnMessageView extends Model
{
    protected $table      = 'v_innn_messages';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'is_read'     => 'integer',
            'is_archived' => 'integer',
            'is_deleted'  => 'integer',
            'tick'        => 'integer',
            'type'        => 'integer',
        ];
    }
}
