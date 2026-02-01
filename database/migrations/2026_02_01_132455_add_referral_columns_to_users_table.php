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
        Schema::table('users', function (Blueprint $table) {
            $table->string('share_referal_code')->nullable()->unique()->after('verification_token');
            $table->foreignId('who_referred')->nullable()->constrained('users')->onDelete('set null')->after('share_referal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['who_referred']);
            $table->dropColumn(['share_referal_code', 'who_referred']);
        });
    }
};
