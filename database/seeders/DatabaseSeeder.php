<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Creates the built-in "Guest" user (id 1), used when AUTH_ENABLED=false.
     * Safe to run many times: the Docker container runs it on every start.
     */
    public function run(): void
    {
        // "!" is not a valid bcrypt hash, so nobody can log in as the guest user.
        // insertOrIgnore = INSERT ... ON CONFLICT DO NOTHING
        DB::table('users')->insertOrIgnore([
            'id' => 1,
            'email' => 'guest@timecapsule.local',
            'password_hash' => '!',
        ]);

        // We inserted id 1 by hand, so move the id sequence forward:
        // otherwise the first registered user would also get id 1 and fail.
        DB::statement("SELECT setval('users_id_seq', GREATEST((SELECT MAX(id) FROM users), 1))");
    }
}
