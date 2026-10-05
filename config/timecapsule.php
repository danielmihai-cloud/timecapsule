<?php

/*
|--------------------------------------------------------------------------
| TimeCapsule settings
|--------------------------------------------------------------------------
|
| Everything the app needs comes from environment variables (.env), so the
| same code runs on a laptop, in Docker and on a server.
| Read these values with config('timecapsule.xxx'), never with env() directly:
| after "php artisan config:cache" env() returns null outside config files.
|
*/

$uploadDir = env('UPLOAD_DIR');

return [

    // false = no login at all: every visitor is the built-in "Guest" user (id 1).
    'auth_enabled' => (bool) env('AUTH_ENABLED', true),

    // How long the fake "email sending" takes when a capsule is created (imitates a slow SMTP server).
    'mail_delay_seconds' => (int) env('MAIL_DELAY_SECONDS', 3),

    // Folder on the local disk where uploaded attachments are saved.
    // Empty = storage/app/uploads. A relative path is relative to the project folder.
    'upload_dir' => match (true) {
        empty($uploadDir) => storage_path('app/uploads'),
        // Absolute: "/var/uploads" (Linux, macOS) or "C:\\uploads" (Windows).
        (bool) preg_match('#^([A-Za-z]:)?[\\\\/]#', $uploadDir) => $uploadDir,
        default => base_path($uploadDir),
    },

    // Maximum attachment size in megabytes.
    // PHP's own limits (upload_max_filesize / post_max_size in php.ini) must be a bit larger.
    'max_upload_mb' => (int) env('MAX_UPLOAD_MB', 5),

];
