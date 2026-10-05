<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * The AUTH_ENABLED switch.
 *
 * AUTH_ENABLED=true  → nothing to do: normal login with sessions ("auth" middleware on routes).
 * AUTH_ENABLED=false → every request acts as the built-in Guest user (id 1).
 *                      onceUsingId() logs in for this request only (nothing stored in the session).
 *                      The login/register/logout routes are not registered at all (see routes/web.php),
 *                      so they answer 404.
 */
class CurrentUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('timecapsule.auth_enabled')) {
            Auth::onceUsingId(1);
        }

        return $next($request);
    }
}
