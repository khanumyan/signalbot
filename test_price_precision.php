<?php

/**
 * Тестовый скрипт для проверки точности цен
 * Проверяет, что цены сохраняются точно, без округления
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CryptoSignal;
use App\Services\CryptoAnalysisService;

echo "🧪 Тестирование точности цен\n";
echo "============================\n\n";

// Тестируем на монете с маленькой ценой (например, SHIB или DOGE)
$testSymbol = 'DOGE'; // DOGE обычно имеет цену около 0.08-0.15
$analysisService = new CryptoAnalysisService();

echo "1️⃣ Тестирование SuperTrend+VWAP стратегии...\n";
try {
    $result = $analysisService->analyzeSuperTrendVwap($testSymbol, [
        'interval' => '15m',
        'limit' => 100,
    ]);
    
    echo "   ✅ Анализ выполнен успешно\n";
    echo "   📊 Цена: " . $result['price'] . "\n";
    echo "   📊 Stop Loss: " . $result['stop_loss'] . "\n";
    echo "   📊 Take Profit: " . $result['take_profit'] . "\n";
    
    // Проверяем, что цена не округлена до 2 знаков
    $priceStr = (string)$result['price'];
    $decimalPlaces = strlen(substr(strrchr($priceStr, "."), 1));
    echo "   🔍 Знаков после запятой в цене: {$decimalPlaces}\n";
    
    if ($decimalPlaces <= 2) {
        echo "   ⚠️  ВНИМАНИЕ: Цена может быть округлена (только {$decimalPlaces} знаков)\n";
    } else {
        echo "   ✅ Цена сохранена с полной точностью ({$decimalPlaces} знаков)\n";
    }
    
    // Проверяем расчеты
    $atr = $result['atr'] ?? 0;
    $price = $result['price'];
    $stopLoss = $result['stop_loss'];
    $takeProfit = $result['take_profit'];
    
    if ($result['signal'] === 'BUY') {
        $expectedSL = $price - ($atr * 2.0);
        $expectedTP = $price + ($atr * 2.0);
    } else {
        $expectedSL = $price + ($atr * 2.0);
        $expectedTP = $price - ($atr * 2.0);
    }
    
    $slDiff = abs($stopLoss - $expectedSL);
    $tpDiff = abs($takeProfit - $expectedTP);
    
    echo "   🔍 Разница в Stop Loss: " . number_format($slDiff, 10) . "\n";
    echo "   🔍 Разница в Take Profit: " . number_format($tpDiff, 10) . "\n";
    
    if ($slDiff > 0.0001 || $tpDiff > 0.0001) {
        echo "   ⚠️  ВНИМАНИЕ: Возможна ошибка в расчетах\n";
    } else {
        echo "   ✅ Расчеты корректны\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

echo "2️⃣ Тестирование Ichimoku+RSI стратегии...\n";
try {
    $result = $analysisService->analyzeIchimokuRsi($testSymbol, [
        'interval' => '1h',
        'limit' => 100,
    ]);
    
    echo "   ✅ Анализ выполнен успешно\n";
    echo "   📊 Цена: " . $result['price'] . "\n";
    echo "   📊 Stop Loss: " . $result['stop_loss'] . "\n";
    echo "   📊 Take Profit: " . $result['take_profit'] . "\n";
    
    // Проверяем, что цена не округлена до 2 знаков
    $priceStr = (string)$result['price'];
    $decimalPlaces = strlen(substr(strrchr($priceStr, "."), 1));
    echo "   🔍 Знаков после запятой в цене: {$decimalPlaces}\n";
    
    if ($decimalPlaces <= 2) {
        echo "   ⚠️  ВНИМАНИЕ: Цена может быть округлена (только {$decimalPlaces} знаков)\n";
    } else {
        echo "   ✅ Цена сохранена с полной точностью ({$decimalPlaces} знаков)\n";
    }
    
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";

echo "3️⃣ Проверка последних сигналов в базе данных...\n";
try {
    $lastSignals = CryptoSignal::whereIn('strategy', ['SuperTrend+VWAP', 'Ichimoku+RSI'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    
    if ($lastSignals->isEmpty()) {
        echo "   ℹ️  Нет сигналов в базе данных\n";
    } else {
        echo "   ✅ Найдено {$lastSignals->count()} последних сигналов\n";
        foreach ($lastSignals as $signal) {
            $priceStr = (string)$signal->price;
            $decimalPlaces = strlen(substr(strrchr($priceStr, "."), 1));
            echo "   📊 {$signal->symbol} ({$signal->strategy}): цена={$signal->price} ({$decimalPlaces} знаков), SL={$signal->stop_loss}, TP={$signal->take_profit}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n";
echo "✅ Тестирование завершено!\n";
echo "\n";
echo "💡 Для полного тестирования запустите:\n";
echo "   php artisan crypto:supertrend-vwap --symbol=DOGE,SHIB --interval=15m\n";
echo "   php artisan crypto:ichimoku-rsi --symbol=DOGE,SHIB --interval=1h\n";

