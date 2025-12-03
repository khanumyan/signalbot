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
        Schema::table('user_strategy_settings', function (Blueprint $table) {
            // Drop unique constraint on strategy_name
            $table->dropUnique(['strategy_name']);
            
            // Add user_identifier (can be session_id, IP, or user_id if auth exists)
            $table->string('user_identifier', 100)->nullable()->after('id');
            
            // Add unique constraint on (user_identifier, strategy_name)
            $table->unique(['user_identifier', 'strategy_name'], 'user_strategy_unique');
            
            // Add index for faster lookups
            $table->index('user_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_strategy_settings', function (Blueprint $table) {
            $table->dropUnique('user_strategy_unique');
            $table->dropIndex(['user_identifier']);
            $table->dropColumn('user_identifier');
            $table->unique('strategy_name');
        });
    }
};
