<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    // The users table has created_at (filled by Laravel) but no updated_at column.
    const UPDATED_AT = null;

    // Our table has no "remember me" column, so switch that feature off.
    protected $rememberTokenName = '';

    protected $fillable = ['email', 'password_hash'];

    protected $hidden = ['password_hash'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    /**
     * Laravel expects a column named "password"; ours is called "password_hash".
     * Auth::attempt() uses this name to find the hash to check.
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    /**
     * The built-in guest user has the hash "!" (not a real bcrypt hash).
     * Returning null tells Laravel "this user has no password", so a login attempt
     * as guest simply fails instead of throwing an error.
     */
    public function getAuthPassword(): ?string
    {
        return $this->password_hash === '!' ? null : $this->password_hash;
    }

    public function capsules(): HasMany
    {
        return $this->hasMany(Capsule::class);
    }

    /** Short name shown on the public wall: the part of the email before "@". */
    public function displayName(): string
    {
        return strstr($this->email, '@', true) ?: $this->email;
    }
}
