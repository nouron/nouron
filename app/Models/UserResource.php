<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eloquent model for `user_resources` — tracks Credits and Supply per user.
 * These two resources are user-level (not per-colony), unlike the 7 colony resources.
 *
 * @property int $user_id
 * @property int $credits
 * @property int $supply
 * @property-read User $user
 */
class UserResource extends Model
{
    protected $table = 'user_resources';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['user_id', 'credits', 'supply'];

    protected function casts(): array
    {
        return [
            'credits' => 'integer',
            'supply' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
