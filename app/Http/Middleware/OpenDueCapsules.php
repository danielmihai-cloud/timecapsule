<?php

namespace App\Http\Middleware;

use App\Services\CapsuleOpener;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Opens due capsules before the page is built, so every page shows the current state.
 * Runs on every web request (GET /health and static files never reach it).
 * Database errors go to the normal error page.
 */
class OpenDueCapsules
{
    public function __construct(private CapsuleOpener $opener) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->opener->openDue();

        return $next($request);
    }
}
