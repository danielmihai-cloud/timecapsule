<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CapsuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\WallController;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

// GET /health is registered in bootstrap/app.php (outside the "web" group: no session, no cookies).

// --- Open to everyone -------------------------------------------------------
Route::get('/wall', WallController::class);

// Public opened capsules can be seen without logging in.
// The controller answers 404 for everything the visitor is not allowed to see.
Route::get('/capsules/{capsule}', [CapsuleController::class, 'show'])->whereNumber('capsule');
Route::get('/capsules/{capsule}/file', [CapsuleController::class, 'file'])->whereNumber('capsule');

// --- Logged-in users (or everybody as Guest when AUTH_ENABLED=false) --------
// "auth" redirects anonymous visitors to /login (configured in bootstrap/app.php).
Route::middleware('auth')->group(function () {
    Route::get('/', [CapsuleController::class, 'index']);
    Route::get('/capsules/new', [CapsuleController::class, 'create']);
    Route::post('/capsules', [CapsuleController::class, 'store']);
    Route::post('/capsules/{capsule}/delete', [CapsuleController::class, 'destroy'])->whereNumber('capsule');
    Route::get('/notifications', NotificationController::class);
});

// --- Login / registration: only exist when AUTH_ENABLED=true ----------------
// When auth is switched off these routes are simply not registered, so they return 404.
if (config('timecapsule.auth_enabled')) {
    // "guest" = only for visitors who are NOT logged in (logged-in users are sent to /).
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister']);
        Route::post('/register', [AuthController::class, 'register']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
}

// Unknown URLs (any method): our 404 page. Handling them with a route means the 404 page still
// knows who is logged in. No CSRF check here: there is nothing to protect.
Route::any('{path}', function () {
    abort(404);
})->where('path', '.*')->fallback()->withoutMiddleware(ValidateCsrfToken::class);
