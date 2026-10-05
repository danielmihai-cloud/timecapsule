<?php

namespace App\Support;

use PDOException;
use Throwable;

/**
 * Turns common database errors into a short hint for the person running the app.
 */
class DatabaseProblem
{
    public static function hint(Throwable $e): ?string
    {
        // Laravel wraps PDO errors in QueryException; look at the whole chain.
        for ($error = $e; $error !== null; $error = $error->getPrevious()) {
            if (! $error instanceof PDOException) {
                continue;
            }

            $message = $error->getMessage();
            if (str_contains($message, 'SQLSTATE[42P01]')) {   // undefined table
                return 'The database has no tables yet. Run: php artisan migrate --seed';
            }
            if (str_contains($message, 'SQLSTATE[08')) {       // connection errors (08xxx)
                return 'Cannot connect to the database. Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD.';
            }
        }

        return null;
    }
}
