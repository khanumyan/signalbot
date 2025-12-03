#!/bin/bash

# Скрипт для тестирования всех торговых стратегий

echo "🚀 Testing All Trading Strategies"
echo "=================================="
echo ""

# Тестовая монета
TEST_SYMBOL="BTC"

echo "1️⃣  Testing EMA + RSI + MACD Strategy..."
php artisan crypto:ema-rsi-macd --symbol=$TEST_SYMBOL --interval=15m
echo ""
echo "---"
echo ""

echo "2️⃣  Testing Bollinger Bands + RSI Strategy..."
php artisan crypto:bollinger-rsi --symbol=$TEST_SYMBOL --interval=15m
echo ""
echo "---"
echo ""

echo "3️⃣  Testing EMA + Stochastic Strategy..."
php artisan crypto:ema-stochastic --symbol=$TEST_SYMBOL --interval=5m
echo ""
echo "---"
echo ""

echo "4️⃣  Testing SuperTrend + VWAP Strategy..."
php artisan crypto:supertrend-vwap --symbol=$TEST_SYMBOL --interval=15m
echo ""
echo "---"
echo ""

echo "5️⃣  Testing Ichimoku + RSI Strategy..."
php artisan crypto:ichimoku-rsi --symbol=$TEST_SYMBOL --interval=1h
echo ""
echo "---"
echo ""

echo "6️⃣  Testing MTF Strategy..."
php artisan crypto:analyze --symbol=$TEST_SYMBOL --interval=15m
echo ""
echo "---"
echo ""

echo "✅ All strategies tested!"
echo ""
echo "To run specific strategy with Telegram:"
echo "  php artisan crypto:ema-rsi-macd --symbol=BTC,ETH --telegram"
echo ""
echo "To run all strategies automatically:"
echo "  php artisan schedule:work"


