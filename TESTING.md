# 🧪 Инструкция по тестированию точности цен

## Быстрое тестирование

### 1. Тест всех стратегий на одной монете
```bash
./test_all_quick.sh
```

Этот скрипт запустит все стратегии на монете DOGE (которая имеет маленькую цену) и покажет результаты.

### 2. Детальный тест точности цен
```bash
php test_price_precision.php
```

Этот скрипт проверит:
- ✅ Что цены сохраняются с полной точностью
- ✅ Что расчеты Stop Loss и Take Profit корректны
- ✅ Что в базе данных цены не округлены

### 3. Тест конкретных стратегий (SuperTrend+VWAP и Ichimoku+RSI)
```bash
# SuperTrend + VWAP
php artisan crypto:supertrend-vwap --symbol=DOGE,SHIB --interval=15m

# Ichimoku + RSI
php artisan crypto:ichimoku-rsi --symbol=DOGE,SHIB --interval=1h
```

**Почему DOGE и SHIB?** Эти монеты имеют маленькие цены (0.01-0.15), что позволяет легко увидеть, если цены округляются неправильно.

## Проверка в базе данных

### Проверить последние сигналы
```sql
SELECT 
    id,
    symbol,
    strategy,
    price,
    stop_loss,
    take_profit,
    created_at
FROM crypto_signals
WHERE strategy IN ('SuperTrend+VWAP', 'Ichimoku+RSI')
ORDER BY created_at DESC
LIMIT 10;
```

### Проверить точность цен
```sql
-- Проверить количество знаков после запятой
SELECT 
    symbol,
    strategy,
    price,
    LENGTH(SUBSTRING(price::text, POSITION('.' IN price::text) + 1)) as decimal_places,
    stop_loss,
    take_profit
FROM crypto_signals
WHERE strategy IN ('SuperTrend+VWAP', 'Ichimoku+RSI')
ORDER BY created_at DESC
LIMIT 10;
```

### Проверить расчеты
```sql
-- Проверить, что Stop Loss и Take Profit рассчитаны правильно
SELECT 
    symbol,
    strategy,
    price,
    stop_loss,
    take_profit,
    ABS(stop_loss - price) as sl_distance,
    ABS(take_profit - price) as tp_distance
FROM crypto_signals
WHERE strategy IN ('SuperTrend+VWAP', 'Ichimoku+RSI')
ORDER BY created_at DESC
LIMIT 10;
```

## Проверка через Laravel Tinker

```bash
php artisan tinker
```

Затем в tinker:
```php
// Проверить последние сигналы
$signals = \App\Models\CryptoSignal::whereIn('strategy', ['SuperTrend+VWAP', 'Ichimoku+RSI'])
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

foreach ($signals as $signal) {
    $priceStr = (string)$signal->price;
    $decimalPlaces = strlen(substr(strrchr($priceStr, "."), 1));
    echo "{$signal->symbol} ({$signal->strategy}): price={$signal->price} ({$decimalPlaces} decimals), SL={$signal->stop_loss}, TP={$signal->take_profit}\n";
}
```

## Что проверять

1. **Точность цен**: Цены должны иметь больше 2 знаков после запятой для маленьких цен (например, 0.0100781, а не 0.01)

2. **Правильность расчетов**: 
   - Stop Loss и Take Profit должны рассчитываться от точной цены
   - Разница между расчетными и сохраненными значениями должна быть минимальной (< 0.0001)

3. **Сохранение в базу**: Все значения должны сохраняться с максимальной точностью (до 10 знаков после запятой)

## Ожидаемые результаты

✅ **Правильно:**
- Цена: `0.0100781` (7 знаков после запятой)
- Stop Loss: `0.0095781` (рассчитан от точной цены)
- Take Profit: `0.0105781` (рассчитан от точной цены)

❌ **Неправильно:**
- Цена: `0.01` (только 2 знака - округлено!)
- Stop Loss: `0.01` (рассчитан от округленной цены)
- Take Profit: `0.01` (рассчитан от округленной цены)

## Тестирование с Telegram

Если хотите протестировать отправку в Telegram:
```bash
php artisan crypto:supertrend-vwap --symbol=DOGE --telegram
php artisan crypto:ichimoku-rsi --symbol=DOGE --telegram
```

Проверьте сообщения в Telegram - цены должны отображаться с правильной точностью.

