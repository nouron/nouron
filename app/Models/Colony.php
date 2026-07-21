<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Eloquent Colony model — reads from v_glx_colonies.
 *
 * Each player has exactly one colony on an implicit home site; there is no
 * navigable galaxy/system map (removed 2026-06). The view exposes the columns of
 * glx_colonies directly.
 *
 * READ-ONLY: v_glx_colonies is a SQLite view. All writes must target the
 * underlying glx_colonies table via DB::table('glx_colonies') or ColonyRecord.
 *
 * The view/table is created via raw `DB::statement()` migrations, which
 * Larastan's static migration scanner cannot see — hence the explicit
 * property annotations below.
 *
 * @property int $id
 * @property string|null $name
 * @property int|null $user_id
 * @property int $since_tick
 * @property bool $is_primary
 * @property int $hunger_streak
 * @property-read User $user
 * @property-read Collection<int, ColonyResource> $resources
 */
class Colony extends Model
{
    protected $table = 'v_glx_colonies';

    // Prevent accidental writes through this model (v_glx_colonies is a SQLite view).
    protected $guarded = ['*'];

    protected $primaryKey = 'id';

    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'since_tick' => 'integer',
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

    /**
     * @return HasMany<ColonyResource, $this>
     */
    public function resources(): HasMany
    {
        return $this->hasMany(ColonyResource::class, 'colony_id', 'id');
    }
}
