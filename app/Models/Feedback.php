<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * In-game feedback from beta testers (R15). Context (user, run, Sol, page,
 * user agent) is always set server-side by FeedbackController.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $run_id
 * @property int|null $sol
 * @property string $category
 * @property string $message
 * @property string|null $page
 * @property string|null $user_agent
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property-read User $user
 */
class Feedback extends Model
{
    public const CATEGORIES = ['bug', 'balance', 'idea', 'other'];

    protected $table = 'feedback';

    protected $fillable = ['user_id', 'run_id', 'sol', 'category', 'message', 'page', 'user_agent'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}
