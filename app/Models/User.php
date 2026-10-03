<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Eloquent User model for Nouron player accounts.
 *
 * Maps to the `user` table which uses `user_id` as the primary key
 * and bcrypt passwords (compatible with Laravel's hashing out of the box).
 */
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'user_id';

    // The `user` table has no created_at/updated_at columns
    public $timestamps = false;

    protected $fillable = [
        'username',
        'display_name',
        'email',
        'password',
        'role',
        'activated',
        'activation_key',
    ];

    protected $hidden = [
        'password',
        'activation_key',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activated' => 'boolean',
            'registration' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function colonies()
    {
        return $this->hasMany(Colony::class, 'user_id', 'user_id');
    }

    public function resources()
    {
        return $this->hasOne(UserResource::class, 'user_id', 'user_id');
    }

    /**
     * Laravel's Eloquent auth provider calls findForPassport / retrieveByCredentials.
     * We override getAuthIdentifierName() to use user_id, and the login controller
     * handles username-or-email matching before calling Auth::attempt().
     */
    public function getAuthIdentifierName(): string
    {
        return 'user_id';
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPlayer(): bool
    {
        return in_array($this->role, ['player', 'admin'], true);
    }
}
