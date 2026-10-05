<?php

namespace App\Http\Controllers;

use App\Support\DatabaseProblem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * GET /health — a tiny status check for monitoring tools and Docker.
 * Answers 200 when the database works, 500 otherwise. "hostname" shows which machine answered.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            // Reading a real table also catches a database that has no tables yet.
            DB::select('SELECT COUNT(*) FROM users');

            return response()->json(['status' => 'ok', 'db' => 'ok', 'hostname' => gethostname()]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => 'error',
                'db' => 'error',
                'hint' => DatabaseProblem::hint($e),
                'hostname' => gethostname(),
            ], 500, [], JSON_UNESCAPED_SLASHES);
        }
    }
}
