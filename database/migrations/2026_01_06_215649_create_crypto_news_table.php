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
        Schema::create('crypto_news', function (Blueprint $table) {
            $table->id();
            $table->string('article_id')->unique()->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('link');
            $table->timestamp('pub_date')->nullable();
            $table->json('creator')->nullable(); // Массив авторов
            $table->json('coin')->nullable(); // Массив монет
            $table->string('image_url')->nullable();
            $table->string('source_name')->nullable();
            $table->string('source_id')->nullable();
            $table->json('keywords')->nullable(); // Массив ключевых слов
            $table->text('content')->nullable();
            $table->string('language', 10)->nullable();
            $table->boolean('sent_to_telegram')->default(false);
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index('pub_date');
            $table->index('sent_to_telegram');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crypto_news');
    }
};
