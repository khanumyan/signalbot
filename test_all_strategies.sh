#!/bin/bash

# Скрипт для тестирования всех стратегий анализа криптовалют
# Использование: ./test_all_strategies.sh [SYMBOL]

SYMBOL=${1:-BTC}
LIMIT=50

echo "=========================================="
echo "🧪 Тестирование стратегий анализа"
echo "Символ: $SYMBOL"
echo "Лимит свечей: $LIMIT"
echo "=========================================="
echo ""

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для тестирования команды
test_command() {
    local cmd=$1
    local name=$2
    
    echo -e "${YELLOW}📊 Тестирование: $name${NC}"
    echo "Команда: $cmd"
    
    if php artisan $cmd --symbol=$SYMBOL --limit=$LIMIT 2>&1 | grep -q "error\|Error\|ERROR\|Failed\|failed"; then
        echo -e "${RED}❌ ОШИБКА${NC}"
        php artisan $cmd --symbol=$SYMBOL --limit=$LIMIT 2>&1 | tail -5
    else
        echo -e "${GREEN}✅ Команда выполнена успешно${NC}"
        php artisan $cmd --symbol=$SYMBOL --limit=$LIMIT 2>&1 | tail -3
    fi
    echo ""
}

# Тестируем все доступные стратегии
echo "1. MTF Strategy (Multi-TimeFrame)"
test_command "crypto:analyze" "MTF Strategy"

echo "2. EMA + RSI + MACD Strategy"
test_command "crypto:ema-rsi-macd" "EMA+RSI+MACD"

echo "3. Bollinger Bands + RSI Strategy"
test_command "crypto:bollinger-rsi" "Bollinger+RSI"

echo "4. EMA + Stochastic Strategy"
test_command "crypto:ema-stochastic" "EMA+Stochastic"

echo "5. SuperTrend + VWAP Strategy"
if php artisan list | grep -q "supertrend-vwap"; then
    test_command "crypto:supertrend-vwap" "SuperTrend+VWAP"
else
    echo -e "${RED}❌ Команда crypto:supertrend-vwap не найдена${NC}"
    echo ""
fi

echo "6. Ichimoku + RSI Strategy"
if php artisan list | grep -q "ichimoku-rsi"; then
    test_command "crypto:ichimoku-rsi" "Ichimoku+RSI"
else
    echo -e "${RED}❌ Команда crypto:ichimoku-rsi не найдена${NC}"
    echo ""
fi

echo "=========================================="
echo "✅ Тестирование завершено!"
echo "=========================================="

