#!/bin/bash

# Быстрый тест всех стратегий на одной монете с маленькой ценой
# Это поможет проверить, что цены сохраняются точно

echo "🧪 Быстрое тестирование всех стратегий"
echo "======================================="
echo ""

# Используем монету с маленькой ценой для проверки точности
TEST_SYMBOL="DOGE"  # DOGE обычно имеет цену около 0.08-0.15

echo "📊 Тестируем на символе: $TEST_SYMBOL (маленькая цена для проверки точности)"
echo ""

echo "1️⃣ SuperTrend + VWAP Strategy..."
php artisan crypto:supertrend-vwap --symbol=$TEST_SYMBOL --interval=15m --limit=50
echo ""

echo "2️⃣ Ichimoku + RSI Strategy..."
php artisan crypto:ichimoku-rsi --symbol=$TEST_SYMBOL --interval=1h --limit=50
echo ""

echo "3️⃣ EMA + RSI + MACD Strategy..."
php artisan crypto:ema-rsi-macd --symbol=$TEST_SYMBOL --interval=15m --limit=50
echo ""

echo "4️⃣ Bollinger Bands + RSI Strategy..."
php artisan crypto:bollinger-rsi --symbol=$TEST_SYMBOL --interval=15m --limit=50
echo ""

echo "5️⃣ EMA + Stochastic Strategy..."
php artisan crypto:ema-stochastic --symbol=$TEST_SYMBOL --interval=5m --limit=50
echo ""

echo "6️⃣ MTF Strategy..."
php artisan crypto:analyze --symbol=$TEST_SYMBOL --interval=15m --limit=50
echo ""

echo "✅ Все стратегии протестированы!"
echo ""
echo "📊 Проверьте базу данных:"
echo "   SELECT symbol, strategy, price, stop_loss, take_profit, created_at"
echo "   FROM crypto_signals"
echo "   WHERE symbol = '$TEST_SYMBOL'"
echo "   ORDER BY created_at DESC LIMIT 10;"
echo ""
echo "💡 Запустите тест точности цен:"
echo "   php test_price_precision.php"

