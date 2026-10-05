<?php

namespace App\Services;

use App\Models\Capsule;
use App\Models\Notification;

/**
 * A simulated email service.
 *
 * send() waits MAIL_DELAY_SECONDS to imitate a slow SMTP server. It runs inside the web
 * request, so the user waits for it. No email is really sent: the "email" is stored as a row
 * in the notifications table and can be seen on the Notifications page.
 */
class Mailer
{
    public function send(Capsule $capsule, string $recipient, string $message): void
    {
        sleep(config('timecapsule.mail_delay_seconds'));

        $this->record($capsule, $recipient, $message);
    }

    /** Stores the notification row (no delay). */
    public function record(Capsule $capsule, string $recipient, string $message): Notification
    {
        return Notification::create([
            'capsule_id' => $capsule->id,
            'user_id' => $capsule->user_id,   // the capsule owner sees it on /notifications
            'recipient' => $recipient,
            'message' => $message,
        ]);
    }
}
