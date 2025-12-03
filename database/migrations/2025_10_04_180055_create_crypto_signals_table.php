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
        Schema::create('crypto_signals', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 20)->index(); // BTC, ETH, etc.
            $table->enum('type', ['BUY', 'SELL']);
            $table->enum('strength', ['WEAK', 'MEDIUM', 'STRONG']);
            $table->decimal('price', 20, 10); // Точная цена
            $table->decimal('rsi', 8, 4); // RSI значение
            $table->decimal('ema', 20, 10); // EMA значение
            $table->decimal('stop_loss', 20, 10); // Стоп-лосс
            $table->decimal('take_profit', 20, 10); // Тейк-профит
            $table->decimal('volume_ratio', 8, 4); // Объемное соотношение
            $table->string('htf_trend', 20); // BULLISH, BEARISH, NEUTRAL, UNCLEAR
            $table->decimal('htf_rsi', 8, 4); // RSI на старшем ТФ
            $table->decimal('ltf_rsi', 8, 4); // RSI на младшем ТФ
            $table->text('reason'); // Причина сигнала
            $table->boolean('sent_to_telegram')->default(false); // Отправлен ли в Telegram
            $table->timestamp('signal_time'); // Время сигнала
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['symbol', 'signal_time']);
            $table->index(['symbol', 'type', 'strength', 'signal_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_signals');
    }
};
