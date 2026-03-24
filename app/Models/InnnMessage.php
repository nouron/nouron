<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the innn_messages table (write operations).
 *
 * For read operations with sender/recipient usernames use InnnMessageView
 * which queries the v_innn_messages view.
 */
class InnnMessage extends Model
{
    protected $table      = 'innn_messages';
    protected $primaryKey = 'id';
    public    $timestamps = false;

    protected $fillable = [
        'sender_id',
        'attitude',
        'recipient_id',
        'tick',
        'type',
        'subject',
        'text',
        'is_read',
        'is_archived',
        'is_deleted',
    ];

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
