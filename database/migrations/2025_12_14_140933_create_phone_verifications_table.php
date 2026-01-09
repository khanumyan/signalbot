<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phone_verifications', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->index();
            $table->string('verification_code', 6);
            $table->string('token', 64)->unique()->index();
            $table->bigInteger('telegram_chat_id')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_verifications');
    }
};
