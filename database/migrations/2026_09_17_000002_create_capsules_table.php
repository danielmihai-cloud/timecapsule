<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('capsules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title', 120);
            $table->text('message');
            $table->timestamp('open_at', 6);                     // the day it may be opened, 00:00 UTC
            $table->boolean('is_public')->default(false);
            $table->string('recipient_email', 255)->nullable();
            $table->string('file_path', 255)->nullable();        // stored name inside UPLOAD_DIR, e.g. "a1b2...c3.png"
            $table->string('file_name', 255)->nullable();        // original name shown to the user
            $table->string('file_mime', 100)->nullable();
            $table->timestamp('opened_at', 6)->nullable();       // set by App\Services\CapsuleOpener
            $table->timestamp('created_at', 6)->default(DB::raw("(NOW() AT TIME ZONE 'UTC')"));

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id', 'capsules_user_id_idx');
            $table->index('open_at', 'capsules_open_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capsules');
    }
};
