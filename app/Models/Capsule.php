<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Capsule extends Model
{
    // Only created_at exists (capsules are never edited).
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'title', 'message', 'open_at', 'is_public', 'recipient_email',
        'file_path', 'file_name', 'file_mime', 'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'open_at' => 'datetime',
            'opened_at' => 'datetime',
            'created_at' => 'datetime',
            'is_public' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** A capsule is opened by the first web request after its open time (see CapsuleOpener). */
    public function isOpened(): bool
    {
        return $this->opened_at !== null;
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $user->id === $this->user_id;
    }

    /**
     * Owners can always see their capsule. Everybody else only if it is public and already opened.
     * (Controllers answer 404 otherwise, so strangers cannot even tell the capsule exists.)
     */
    public function isVisibleTo(?User $user): bool
    {
        return $this->isOwnedBy($user) || ($this->is_public && $this->isOpened());
    }

    public function hasFile(): bool
    {
        return $this->file_path !== null;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->file_mime, 'image/');
    }

    /**
     * "Opens in 5 minutes" / "Opens in 3 hours" / "Opens in 12 days",
     * or "Opening soon…" (fallback: the time has passed, but the capsule is not opened yet).
     */
    public function countdown(): string
    {
        $seconds = $this->open_at->getTimestamp() - now()->getTimestamp();

        if ($seconds <= 0) {
            return 'Opening soon…';
        }
        if ($seconds < 3600) {
            return self::opensIn((int) ceil($seconds / 60), 'minute');
        }
        if ($seconds < 86400) {
            return self::opensIn(intdiv($seconds, 3600), 'hour');
        }

        return self::opensIn(intdiv($seconds, 86400), 'day');
    }

    private static function opensIn(int $count, string $unit): string
    {
        return "Opens in {$count} {$unit}".($count === 1 ? '' : 's');
    }

    /** First 120 characters of the message (multibyte safe). */
    public function excerpt(): string
    {
        return mb_strlen($this->message) > 120
            ? mb_substr($this->message, 0, 120).'…'
            : $this->message;
    }
}
