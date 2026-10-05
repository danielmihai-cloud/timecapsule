<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\CurrentUser;
use App\Http\Middleware\OpenDueCapsules;
use App\Support\DatabaseProblem;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // GET /health lives outside the "web" middleware group: monitoring tools call it
        // very often, and it should not create a session file every time.
        then: function () {
            Route::get('/health', HealthController::class);
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // AUTH_ENABLED switch, runs on every web request (after the session has started).
        // Then open due capsules, before any page is built (see OpenDueCapsules).
        $middleware->web(append: [CurrentUser::class, OpenDueCapsules::class]);
        // Laravel sorts middleware by a priority list; make sure the switch runs BEFORE the
        // "auth" middleware checks whether somebody is logged in.
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: CurrentUser::class);
        // Open due capsules BEFORE route model binding loads the capsule of the page.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: OpenDueCapsules::class);

        // Where the "auth" and "guest" middleware send people.
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // For HTTP errors Laravel renders resources/views/errors/{code}.blade.php
        // (403, 404, 419 = expired CSRF token, 500). With APP_DEBUG=true a crash shows
        // Laravel's detailed error page instead of errors/500.

        // A missing table or an unreachable database gets a 500 page with a helpful hint.
        $exceptions->render(function (Throwable $e) {
            $hint = DatabaseProblem::hint($e);
            if ($hint !== null) {
                return response()->view('errors.500', ['hint' => $hint], 500);
            }

            return null; // everything else: Laravel's normal handling
        });
    })->create();
