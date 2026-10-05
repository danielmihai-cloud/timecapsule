<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Support\Facades\DB;

/**
 * Opens every capsule whose open time has passed and "emails" the recipient.
 * Called at the start of every web request (see app/Http/Middleware/OpenDueCapsules.php).
 */
class CapsuleOpener
{
    /** Returns how many capsules were opened. */
    public function openDue(): int
    {
        $now = now()->format('Y-m-d H:i:s.u'); // UTC

        // One atomic statement: the UPDATE returns only the rows it changed itself. If two
        // requests run at the same time, PostgreSQL lets only one of them set opened_at on a
        // capsule (the other re-checks "opened_at IS NULL" and skips it), so a capsule is
        // never opened, or notified about, twice.
        $rows = DB::select(
            'UPDATE capsules c
                SET opened_at = ?
               FROM users u
              WHERE u.id = c.user_id AND c.opened_at IS NULL AND c.open_at <= ?
          RETURNING c.id, c.user_id, c.title, c.recipient_email, u.email',
            [$now, $now],
            useReadPdo: false,
        );

        foreach ($rows as $row) {
            // No simulated mail delay here: the row is written directly.
            Notification::create([
                'capsule_id' => $row->id,
                'user_id' => $row->user_id,   // the capsule owner sees it on /notifications
                'recipient' => $row->recipient_email ?: $row->email,
                'message' => "Your capsule \"{$row->title}\" is now open.",
            ]);
        }

        return count($rows);
    }
}
