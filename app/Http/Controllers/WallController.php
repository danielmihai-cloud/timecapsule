<?php

namespace App\Http\Controllers;

use App\Models\Capsule;
use Illuminate\View\View;

class WallController extends Controller
{
    /** GET /wall — opened public capsules of all users, newest first. Open to everyone. */
    public function __invoke(): View
    {
        $capsules = Capsule::with('user')
            ->whereNotNull('opened_at')
            ->where('is_public', true)
            ->orderByDesc('opened_at')
            ->limit(50)
            ->get();

        return view('wall', ['capsules' => $capsules]);
    }
}
