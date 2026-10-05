<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Same table as docs/ui-reference/schema.sql (used by the other two variants).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');                       // SERIAL PRIMARY KEY
            $table->string('email', 255)->unique();
            $table->string('password_hash', 255);           // bcrypt hash, never the plain password
            // All times are stored in UTC ("timestamp without time zone").
            $table->timestamp('created_at', 6)->default(DB::raw("(NOW() AT TIME ZONE 'UTC')"));
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
