<?php

/**
 * Скрипт для проверки работы всех стратегий через веб-интерфейс
 * 
 * Использование: php check_strategies.php [SYMBOL]
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\CryptoAnalysisService;
use App\Models\UserStrategySetting;

$symbol = $argv[1] ?? 'BTC';
$testParams = [];

echo "==========================================\n";
echo "🧪 Тестирование стратегий через сервис\n";
echo "Символ: $symbol\n";
echo "==========================================\n\n";

$analysisService = new CryptoAnalysisService();
$strategies = [
    'MTF' => 'Multi-TimeFrame Strategy',
    'EMA+RSI+MACD' => 'EMA + RSI + MACD',
    'Bollinger+RSI' => 'Bollinger Bands + RSI',
    'EMA+Stochastic' => 'EMA + Stochastic',
    'SuperTrend+VWAP' => 'SuperTrend + VWAP',
    'Ichimoku+RSI' => 'Ichimoku + RSI',
];

$results = [];

foreach ($strategies as $strategyName => $strategyTitle) {
    echo "📊 Тестирование: $strategyTitle ($strategyName)\n";
    
    try {
        // Получаем параметры по умолчанию
        $defaultParams = UserStrategySetting::getDefaultParameters($strategyName);
        $defaultParams['limit'] = 100;
        
        // Вызываем соответствующий метод анализа
        $result = match($strategyName) {
            'MTF' => $analysisService->analyzeMTF($symbol, $defaultParams),
            'EMA+RSI+MACD' => $analysisService->analyzeEmaRsiMacd($symbol, $defaultParams),
            'Bollinger+RSI' => $analysisService->analyzeBollingerRsi($symbol, $defaultParams),
            'EMA+Stochastic' => $analysisService->analyzeEmaStochastic($symbol, $defaultParams),
            'SuperTrend+VWAP' => $analysisService->analyzeSuperTrendVwap($symbol, $defaultParams),
            'Ichimoku+RSI' => $analysisService->analyzeIchimokuRsi($symbol, $defaultParams),
            default => null
        };
        
        if ($result) {
            echo "   ✅ Успешно!\n";
            echo "   📈 Сигнал: {$result['signal']}\n";
            echo "   💪 Сила: {$result['strength']}\n";
            echo "   📊 LONG: {$result['long_probability']}% | SHORT: {$result['short_probability']}%\n";
            echo "   💰 Цена: \${$result['price']}\n";
            if (isset($result['stop_loss'])) {
                echo "   🛡️ SL: \${$result['stop_loss']} | 🎯 TP: \${$result['take_profit']}\n";
            }
            $results[$strategyName] = ['status' => 'success', 'result' => $result];
        } else {
            echo "   ⚠️ Результат пустой\n";
            $results[$strategyName] = ['status' => 'empty'];
        }
    } catch (\Exception $e) {
        echo "   ❌ ОШИБКА: {$e->getMessage()}\n";
        $results[$strategyName] = ['status' => 'error', 'error' => $e->getMessage()];
    }
    
    echo "\n";
    usleep(200000); // Небольшая задержка между запросами
}

echo "==========================================\n";
echo "📊 Итоги тестирования:\n";
echo "==========================================\n";

$successCount = 0;
$errorCount = 0;

foreach ($results as $strategy => $result) {
    if ($result['status'] === 'success') {
        $successCount++;
        echo "✅ $strategy - работает\n";
    } elseif ($result['status'] === 'error') {
        $errorCount++;
        echo "❌ $strategy - ошибка: {$result['error']}\n";
    } else {
        echo "⚠️ $strategy - пустой результат\n";
    }
}

echo "\n";
echo "Успешно: $successCount\n";
echo "Ошибок: $errorCount\n";
echo "Всего: " . count($results) . "\n";









