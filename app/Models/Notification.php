<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One "email" the app would have sent. Written by App\Services\Mailer.
 */
class Notification extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['capsule_id', 'user_id', 'recipient', 'message'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }
}
