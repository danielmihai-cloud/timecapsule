<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /** GET /notifications — the "emails" written by App\Services\Mailer for the current user. */
    public function __invoke(): View
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return view('notifications', ['notifications' => $notifications]);
    }
}
