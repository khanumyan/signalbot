<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;

echo "🔍 Проверка доставки сигналов\n";
echo "================================\n\n";

// 1. Проверяем всех пользователей
$users = User::all();
echo "📊 Всего пользователей: " . $users->count() . "\n\n";

foreach ($users as $user) {
    echo "👤 Пользователь ID: {$user->id}\n";
    echo "   Имя: {$user->name}\n";
    echo "   Email: {$user->email}\n";
    echo "   Phone: " . ($user->phone ?? 'не указан') . "\n";
    echo "   Telegram Chat ID: " . ($user->telegram_chat_id ?? 'не указан') . "\n";
    
    // Проверяем подписки
    $subscriptions = $user->subscriptions;
    echo "   Подписок: " . $subscriptions->count() . "\n";
    
    if ($subscriptions->count() > 0) {
        foreach ($subscriptions as $sub) {
            $isActive = $sub->status === 'active' 
                && $sub->date_from <= now() 
                && $sub->date_to >= now();
            
            $isAllowedProduct = in_array($sub->product_id, [1, 2, 4, 5]);
            
            echo "      - Product ID: {$sub->product_id}\n";
            echo "        Status: {$sub->status}\n";
            echo "        Дата с: {$sub->date_from}\n";
            echo "        Дата по: {$sub->date_to}\n";
            echo "        Активна: " . ($isActive ? '✅ ДА' : '❌ НЕТ') . "\n";
            echo "        Разрешенный продукт: " . ($isAllowedProduct ? '✅ ДА' : '❌ НЕТ') . "\n";
        }
    }
    
    // Проверяем, будет ли пользователь получать сигналы
    $willReceive = User::whereHas('subscriptions', function ($query) {
            $query->where('status', 'active')
                  ->whereIn('product_id', [1, 2, 4, 5])
                  ->where('date_from', '<=', now())
                  ->where('date_to', '>=', now());
        })
        ->where('id', $user->id)
        ->whereNotNull('telegram_chat_id')
        ->where('telegram_chat_id', '!=', '')
        ->exists();
    
    echo "   🎯 Будет получать сигналы: " . ($willReceive ? '✅ ДА' : '❌ НЕТ') . "\n";
    echo "\n";
}

// 2. Проверяем, сколько пользователей получат сигналы
$activeUsers = User::whereHas('subscriptions', function ($query) {
        $query->where('status', 'active')
              ->whereIn('product_id', [1, 2, 4, 5])
              ->where('date_from', '<=', now())
              ->where('date_to', '>=', now());
    })
    ->whereNotNull('telegram_chat_id')
    ->where('telegram_chat_id', '!=', '')
    ->get();

echo "📈 ИТОГО:\n";
echo "================================\n";
echo "Пользователей, которые получат сигналы: " . $activeUsers->count() . "\n";

if ($activeUsers->count() > 0) {
    echo "\nСписок Chat ID, которые получат сигналы:\n";
    foreach ($activeUsers as $user) {
        echo "  - Chat ID: {$user->telegram_chat_id} (User ID: {$user->id}, Email: {$user->email})\n";
    }
} else {
    echo "\n⚠️  ВНИМАНИЕ: Нет пользователей, которые получат сигналы!\n";
    echo "\nВозможные причины:\n";
    echo "1. Нет активных подписок (status = 'active')\n";
    echo "2. Product ID не входит в список [1, 2, 4, 5]\n";
    echo "3. Подписка истекла (date_to < now())\n";
    echo "4. У пользователей нет telegram_chat_id\n";
}











