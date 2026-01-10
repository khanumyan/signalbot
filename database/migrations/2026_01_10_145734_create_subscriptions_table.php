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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['active', 'in-active'])->default('active');
            $table->date('date_from');
            $table->date('date_to');
            $table->timestamps();
            
            // Индексы для быстрого поиска
            $table->index(['user_id', 'status']);
            $table->index(['product_id', 'status']);
            $table->index('date_to'); // Для поиска истекающих подписок
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
