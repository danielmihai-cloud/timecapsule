<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// "Emails" the app would have sent (see app/Services/Mailer.php).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('capsule_id');
            $table->integer('user_id');
            $table->string('recipient', 255);
            $table->text('message');
            $table->timestamp('created_at', 6)->default(DB::raw("(NOW() AT TIME ZONE 'UTC')"));

            $table->foreign('capsule_id')->references('id')->on('capsules')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('user_id', 'notifications_user_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
