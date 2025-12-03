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
        Schema::create('user_strategy_settings', function (Blueprint $table) {
            $table->id();
            $table->string('strategy_name', 100)->unique(); // MTF, EMA+RSI+MACD, Bollinger+RSI, etc.
            $table->boolean('is_active')->default(true); // Включена ли стратегия
            $table->json('parameters'); // JSON с параметрами стратегии
            $table->text('description')->nullable(); // Описание настроек
            $table->timestamps();
            
            $table->index('strategy_name');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_strategy_settings');
    }
};
