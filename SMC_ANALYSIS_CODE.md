# 💎 Smart Money Concepts - Блок кода анализа

## 📋 Основной метод анализа

```php
/**
 * Analyze symbol with Smart Money Concepts (SMC) strategy
 * Based on Order Blocks, Market Structure (BOS/CHOCH), Fair Value Gaps
 * Согласно инструкции: торговля только по тренду, с подтверждением через ликвидность и структуру
 */
public function analyzeSmartMoneyConcepts(string $symbol, array $params): array
{
    // ============================================
    // ПАРАМЕТРЫ И ПОЛУЧЕНИЕ ДАННЫХ
    // ============================================
    $interval = $params['interval'] ?? '15m';
    $limit = $params['limit'] ?? 200;
    $htfInterval = $params['htf_interval'] ?? '4h'; // H4 для определения тренда
    $rsiPeriod = $params['rsi_period'] ?? 14;
    $orderBlockLookback = $params['order_block_lookback'] ?? 30;

    // Получаем данные свечей
    $klines = $this->fetchKlines($symbol, $interval, $limit);        // 200 свечей на 15m
    $htfKlines = $this->fetchKlines($symbol, $htfInterval, 100);    // 100 свечей на 4h

    if (empty($klines) || count($klines) < 50) {
        throw new \Exception("Недостаточно данных для анализа");
    }

    // Извлекаем данные о ценах
    $closes = array_map(fn($k) => (float) $k[4], $klines);
    $highs = array_map(fn($k) => (float) $k[2], $klines);
    $lows = array_map(fn($k) => (float) $k[3], $klines);
    $volumes = array_map(fn($k) => (float) $k[5], $klines);
    $opens = array_map(fn($k) => (float) $k[1], $klines);

    // HTF данные (4h)
    $htfCloses = array_map(fn($k) => (float) $k[4], $htfKlines);
    $htfHighs = array_map(fn($k) => (float) $k[2], $htfKlines);
    $htfLows = array_map(fn($k) => (float) $k[3], $htfKlines);

    // ============================================
    // РАСЧЕТ ИНДИКАТОРОВ
    // ============================================
    $price = end($closes);                                    // Текущая цена (последняя закрытая свеча)
    $rsi = $this->calculateRSI($closes, $rsiPeriod);        // RSI(14) на 15m
    $htfRsi = $this->calculateRSI($htfCloses, $rsiPeriod);    // RSI(14) на 4h
    $ema = $this->calculateEMA($closes, 50);                  // EMA(50) на 15m
    $htfEma = $this->calculateEMA($htfCloses, 50);            // EMA(50) на 4h
    $atr = $this->calculateATR($highs, $lows, $closes, 14);  // ATR(14) для расчета TP

    // ============================================
    // ШАГ 1: ОПРЕДЕЛЕНИЕ СТАРШЕГО ТРЕНДА (H4) ⚠️ КРИТИЧНО
    // ============================================
    $htfTrend = 'NEUTRAL';
    $htfPrice = end($htfCloses);
    
    // Более строгое определение тренда: цена должна быть четко выше/ниже EMA и RSI должен подтверждать
    if ($htfPrice > $htfEma * 1.002 && $htfRsi > 52) {
        $htfTrend = 'BULLISH';
    } elseif ($htfPrice < $htfEma * 0.998 && $htfRsi < 48) {
        $htfTrend = 'BEARISH';
    }
    
    // 🔥 КРИТИЧНО: Если нет четкого тренда - не торгуем (пропускаем боковик)
    if ($htfTrend === 'NEUTRAL') {
        return [
            'price' => $price,
            'rsi' => $rsi,
            'ema' => $ema,
            'atr' => $atr,
            'signal' => 'HOLD',
            'long_probability' => 50,
            'short_probability' => 50,
            'stop_loss' => $price,
            'take_profit' => $price,
            'reason' => 'Нет четкого тренда на HTF (H4) - пропуск согласно SMC правилам',
            'strength' => 'WEAK',
            'htf_trend' => $htfTrend,
            'htf_rsi' => $htfRsi,
            'market_structure' => null
        ];
    }

    // ============================================
    // ШАГ 2-6: АНАЛИЗ SMC ЭЛЕМЕНТОВ
    // ============================================
    
    // ШАГ 2: Находим Order Blocks (ключевые зоны)
    $orderBlocks = $this->findOrderBlocks($highs, $lows, $closes, $volumes, $opens, $orderBlockLookback);
    
    // ШАГ 3: Определяем Market Structure (BOS/CHOCH)
    $marketStructure = $this->detectMarketStructure($highs, $lows, $closes);
    
    // ШАГ 4: Находим Fair Value Gaps (FVG)
    $fvg = $this->findFairValueGaps($highs, $lows, $closes);
    
    // ШАГ 5: Находим зоны ликвидности (Liquidity Areas)
    $liquidityAreas = $this->findLiquidityAreas($highs, $lows, $closes);
    
    // ШАГ 6: Проверяем свечной паттерн для подтверждения входа
    $candleConfirmation = $this->checkCandleConfirmation($opens, $highs, $lows, $closes, $htfTrend);

    // ============================================
    // РАСЧЕТ БАЛЛОВ - СТРОГИЕ КРИТЕРИИ
    // ============================================
    $longScore = 0;
    $shortScore = 0;
    $activeOrderBlock = null;
    $hasLiquidity = false;

    // ============================================
    // BUY УСЛОВИЯ: Бычий тренд + возврат к Bullish Order Block + ликвидность
    // ============================================
    if ($htfTrend === 'BULLISH') {
        // 1. Проверяем возврат к Bullish Order Block (ОБЯЗАТЕЛЬНО) → +50 баллов
        foreach ($orderBlocks['bullish'] as $ob) {
            $obRange = ($ob['high'] - $ob['low']) * 0.1; // 10% от размера OB как допустимая зона
            if ($price >= ($ob['low'] - $obRange) && $price <= ($ob['high'] + $obRange)) {
                $longScore += 50; // Критично важно - цена вернулась к OB
                $activeOrderBlock = $ob;
                
                // 2. Проверяем наличие ликвидности НИЖЕ Order Block (ОБЯЗАТЕЛЬНО) → +30 баллов
                foreach ($liquidityAreas['below'] as $liq) {
                    if ($liq['price'] < $ob['low'] && $liq['price'] > $ob['low'] * 0.95) {
                        $longScore += 30; // Ликвидность найдена ниже OB
                        $hasLiquidity = true;
                        break;
                    }
                }
                break;
            }
        }

        // 3. Market Structure подтверждение (BOS/CHOCH) → +25 баллов
        if ($marketStructure === 'BULLISH_BOS' || $marketStructure === 'BULLISH_CHOCH') {
            $longScore += 25;
        }

        // 4. Fair Value Gap как дополнительное подтверждение → +15 баллов
        foreach ($fvg['bullish'] as $gap) {
            if ($price >= $gap['bottom'] && $price <= $gap['top']) {
                $longScore += 15;
                break;
            }
        }

        // 5. Свечное подтверждение (бычья свеча) → +20 баллов
        if ($candleConfirmation['bullish']) {
            $longScore += 20;
        }

        // 6. RSI фильтр (не перекупленность) → +10 баллов
        if ($rsi >= 25 && $rsi <= 45) {
            $longScore += 10;
        }
    }

    // ============================================
    // SELL УСЛОВИЯ: Медвежий тренд + возврат к Bearish Order Block + ликвидность
    // ============================================
    if ($htfTrend === 'BEARISH') {
        // 1. Проверяем возврат к Bearish Order Block (ОБЯЗАТЕЛЬНО) → +50 баллов
        foreach ($orderBlocks['bearish'] as $ob) {
            $obRange = ($ob['high'] - $ob['low']) * 0.1;
            if ($price >= ($ob['low'] - $obRange) && $price <= ($ob['high'] + $obRange)) {
                $shortScore += 50; // Критично важно
                $activeOrderBlock = $ob;
                
                // 2. Проверяем наличие ликвидности ВЫШЕ Order Block (ОБЯЗАТЕЛЬНО) → +30 баллов
                foreach ($liquidityAreas['above'] as $liq) {
                    if ($liq['price'] > $ob['high'] && $liq['price'] < $ob['high'] * 1.05) {
                        $shortScore += 30; // Ликвидность найдена выше OB
                        $hasLiquidity = true;
                        break;
                    }
                }
                break;
            }
        }

        // 3. Market Structure подтверждение → +25 баллов
        if ($marketStructure === 'BEARISH_BOS' || $marketStructure === 'BEARISH_CHOCH') {
            $shortScore += 25;
        }

        // 4. Fair Value Gap → +15 баллов
        foreach ($fvg['bearish'] as $gap) {
            if ($price >= $gap['bottom'] && $price <= $gap['top']) {
                $shortScore += 15;
                break;
            }
        }

        // 5. Свечное подтверждение → +20 баллов
        if ($candleConfirmation['bearish']) {
            $shortScore += 20;
        }

        // 6. RSI фильтр → +10 баллов
        if ($rsi >= 55 && $rsi <= 75) {
            $shortScore += 10;
        }
    }
    
    // ============================================
    // ПРОВЕРКА ОБЯЗАТЕЛЬНЫХ УСЛОВИЙ
    // ============================================
    // 🔥 КРИТИЧНО: Если нет возврата к Order Block или нет ликвидности - не торгуем
    if (!$activeOrderBlock || !$hasLiquidity) {
        return [
            'price' => $price,
            'rsi' => $rsi,
            'ema' => $ema,
            'atr' => $atr,
            'signal' => 'HOLD',
            'long_probability' => 50,
            'short_probability' => 50,
            'stop_loss' => $price,
            'take_profit' => $price,
            'reason' => (!$activeOrderBlock ? 'Нет возврата к Order Block' : 'Нет ликвидности рядом с Order Block') . ' - пропуск согласно SMC правилам',
            'strength' => 'WEAK',
            'htf_trend' => $htfTrend,
            'htf_rsi' => $htfRsi,
            'market_structure' => $marketStructure
        ];
    }
    
    // ============================================
    // ДОПОЛНИТЕЛЬНЫЙ ФИЛЬТР: Штраф за отсутствие подтверждения структуры
    // ============================================
    $structureConfirmation = false;
    if ($htfTrend === 'BULLISH' && ($marketStructure === 'BULLISH_BOS' || $marketStructure === 'BULLISH_CHOCH')) {
        $structureConfirmation = true;
    } elseif ($htfTrend === 'BEARISH' && ($marketStructure === 'BEARISH_BOS' || $marketStructure === 'BEARISH_CHOCH')) {
        $structureConfirmation = true;
    }
    
    // Если нет подтверждения структуры, снижаем баллы на 20 (ужесточение)
    if (!$structureConfirmation) {
        if ($htfTrend === 'BULLISH') {
            $longScore = max(0, $longScore - 20);
        } else {
            $shortScore = max(0, $shortScore - 20);
        }
    }

    // ============================================
    // ОПРЕДЕЛЕНИЕ СИГНАЛА И СИЛЫ
    // ============================================
    // Normalize to percentages
    $totalScore = max(1, $longScore + $shortScore);
    $longProb = round(($longScore / $totalScore) * 100);
    $shortProb = round(($shortScore / $totalScore) * 100);

    // Determine signal - только если есть достаточный перевес
    $signal = 'HOLD';
    if ($longScore > $shortScore && $longScore >= 100) { // Минимум 100 баллов для BUY
        $signal = 'BUY';
    } elseif ($shortScore > $longScore && $shortScore >= 100) { // Минимум 100 баллов для SELL
        $signal = 'SELL';
    }

    // 🔥 УЖЕСТОЧЕННЫЕ КРИТЕРИИ: Только STRONG сигналы
    $strength = 'WEAK';
    if ($signal !== 'HOLD') {
        $winningScore = $signal === 'BUY' ? $longScore : $shortScore;
        if ($winningScore >= 140) { // STRONG: минимум 140 баллов
            $strength = 'STRONG';
        } elseif ($winningScore >= 120) { // MEDIUM: минимум 120 баллов
            $strength = 'MEDIUM';
        }
    }

    // Если WEAK или недостаточно критериев - возвращаем HOLD
    if ($strength === 'WEAK' || $signal === 'HOLD') {
        return [
            'price' => $price,
            'rsi' => $rsi,
            'ema' => $ema,
            'atr' => $atr,
            'signal' => 'HOLD',
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => $price,
            'take_profit' => $price,
            'reason' => 'Недостаточно критериев для входа (нужно: возврат к OB + ликвидность + подтверждение структуры)',
            'strength' => 'WEAK',
            'htf_trend' => $htfTrend,
            'htf_rsi' => $htfRsi,
            'market_structure' => $marketStructure
        ];
    }

    // ============================================
    // ШАГ 7: РАСЧЕТ SL/TP
    // ============================================
    $orderBlockHigh = $activeOrderBlock['high'];
    $orderBlockLow = $activeOrderBlock['low'];

    if ($signal === 'BUY') {
        // Стоп-лосс: ниже Order Block или ниже ближайшей ликвидности
        $stopLoss = $orderBlockLow * 0.995; // На 0.5% ниже OB
        
        // Ищем ближайшую ликвидность ниже для более точного SL
        $nearestLiquidityForSL = null;
        foreach ($liquidityAreas['below'] as $liq) {
            if ($liq['price'] < $orderBlockLow && ($nearestLiquidityForSL === null || $liq['price'] > $nearestLiquidityForSL)) {
                $nearestLiquidityForSL = $liq['price'];
            }
        }
        if ($nearestLiquidityForSL && $nearestLiquidityForSL < $stopLoss) {
            $stopLoss = $nearestLiquidityForSL * 0.995;
        }
        
        // Тейк-профит: на ближайшей зоне ликвидности выше или на основе структуры
        $takeProfit = $price + ($atr * 7.0); // Базовый TP (ATR × 7)
        
        // Ищем ближайшую ликвидность выше для TP
        $nearestLiquidityForTP = null;
        foreach ($liquidityAreas['above'] as $liq) {
            if ($liq['price'] > $price && ($nearestLiquidityForTP === null || $liq['price'] < $nearestLiquidityForTP)) {
                $nearestLiquidityForTP = $liq['price'];
            }
        }
        if ($nearestLiquidityForTP && $nearestLiquidityForTP > $price) {
            $takeProfit = $nearestLiquidityForTP * 0.998; // Чуть ниже ликвидности
        }
    } else { // SELL
        // Стоп-лосс: выше Order Block или выше ближайшей ликвидности
        $stopLoss = $orderBlockHigh * 1.005; // На 0.5% выше OB
        
        // Ищем ближайшую ликвидность выше для более точного SL
        $nearestLiquidityForSL = null;
        foreach ($liquidityAreas['above'] as $liq) {
            if ($liq['price'] > $orderBlockHigh && ($nearestLiquidityForSL === null || $liq['price'] < $nearestLiquidityForSL)) {
                $nearestLiquidityForSL = $liq['price'];
            }
        }
        if ($nearestLiquidityForSL && $nearestLiquidityForSL > $stopLoss) {
            $stopLoss = $nearestLiquidityForSL * 1.005;
        }
        
        // Тейк-профит: на ближайшей зоне ликвидности ниже
        $takeProfit = $price - ($atr * 7.0); // Базовый TP (ATR × 7)
        
        // Ищем ближайшую ликвидность ниже для TP
        $nearestLiquidityForTP = null;
        foreach ($liquidityAreas['below'] as $liq) {
            if ($liq['price'] < $price && ($nearestLiquidityForTP === null || $liq['price'] > $nearestLiquidityForTP)) {
                $nearestLiquidityForTP = $liq['price'];
            }
        }
        if ($nearestLiquidityForTP && $nearestLiquidityForTP < $price) {
            $takeProfit = $nearestLiquidityForTP * 1.002; // Чуть выше ликвидности
        }
    }

    // ============================================
    // ФОРМИРОВАНИЕ РЕЗУЛЬТАТА
    // ============================================
    $reasons = [];
    $reasons[] = "HTF тренд (H4): {$htfTrend}";
    $reasons[] = "Возврат к Order Block: " . number_format($orderBlockLow, 2) . " - " . number_format($orderBlockHigh, 2);
    $reasons[] = "Ликвидность найдена";
    if ($marketStructure) {
        $reasons[] = "Market Structure: {$marketStructure}";
    }
    if ($candleConfirmation[$signal === 'BUY' ? 'bullish' : 'bearish']) {
        $reasons[] = "Свечное подтверждение";
    }
    $reasons[] = "RSI: {$rsi}";

    return [
        'price' => $price,
        'rsi' => $rsi,
        'ema' => $ema,
        'atr' => $atr,
        'signal' => $signal,
        'long_probability' => $longProb,
        'short_probability' => $shortProb,
        'stop_loss' => $stopLoss,
        'take_profit' => $takeProfit,
        'reason' => implode('. ', $reasons),
        'strength' => $strength,
        'htf_trend' => $htfTrend,
        'htf_rsi' => $htfRsi,
        'order_block_high' => $orderBlockHigh,
        'order_block_low' => $orderBlockLow,
        'market_structure' => $marketStructure,
        'volume_ratio' => 1.0
    ];
}
```

---

## 🔍 Вспомогательные методы

### 1. Поиск Order Blocks

```php
private function findOrderBlocks(array $highs, array $lows, array $closes, array $volumes, array $opens, int $lookback): array
{
    $bullishOB = [];
    $bearishOB = [];
    $count = count($closes);

    if ($count < $lookback + 5) {
        return ['bullish' => [], 'bearish' => []];
    }

    // Ищем последние сильные движения перед разворотом
    for ($i = $count - $lookback; $i < $count - 3; $i++) {
        $currentHigh = $highs[$i];
        $currentLow = $lows[$i];
        $currentClose = $closes[$i];
        $currentOpen = $opens[$i];
        $currentVolume = $volumes[$i];
        $avgVolume = array_sum(array_slice($volumes, max(0, $i - 20), 20)) / 20;
        
        // Размер свечи (body)
        $bodySize = abs($currentClose - $currentOpen);
        $candleRange = $currentHigh - $currentLow;
        $bodyRatio = $candleRange > 0 ? $bodySize / $candleRange : 0;

        // Bullish Order Block: сильная бычья свеча перед разворотом вниз
        // Требования: тело ≥ 70%, объем ≥ 2.0x
        if ($currentClose > $currentOpen && $bodyRatio > 0.7 && $currentVolume > $avgVolume * 2.0) {
            // Проверяем, был ли четкий разворот после (минимум 2-3 свечи вниз)
            $reversalConfirmed = false;
            if ($i + 3 < $count) {
                $nextCloses = array_slice($closes, $i + 1, 3);
                $reversalConfirmed = count(array_filter($nextCloses, fn($c) => $c < $currentClose)) >= 2;
            }
            
            if ($reversalConfirmed) {
                $bullishOB[] = [
                    'high' => $currentHigh,
                    'low' => $currentLow,
                    'index' => $i
                ];
            }
        }

        // Bearish Order Block: сильная медвежья свеча перед разворотом вверх
        // Требования: тело ≥ 70%, объем ≥ 2.0x
        if ($currentClose < $currentOpen && $bodyRatio > 0.7 && $currentVolume > $avgVolume * 2.0) {
            // Проверяем четкий разворот вверх
            $reversalConfirmed = false;
            if ($i + 3 < $count) {
                $nextCloses = array_slice($closes, $i + 1, 3);
                $reversalConfirmed = count(array_filter($nextCloses, fn($c) => $c > $currentClose)) >= 2;
            }
            
            if ($reversalConfirmed) {
                $bearishOB[] = [
                    'high' => $currentHigh,
                    'low' => $currentLow,
                    'index' => $i
                ];
            }
        }
    }

    // Берем только последние 5 Order Blocks
    return [
        'bullish' => array_slice($bullishOB, -5),
        'bearish' => array_slice($bearishOB, -5)
    ];
}
```

### 2. Поиск зон ликвидности

```php
private function findLiquidityAreas(array $highs, array $lows, array $closes): array
{
    $count = count($closes);
    $liquidityAbove = [];
    $liquidityBelow = [];
    
    if ($count < 20) {
        return ['above' => [], 'below' => []];
    }
    
    // Ищем локальные максимумы (ликвидация лонгов - ликвидность выше)
    for ($i = 10; $i < $count - 5; $i++) {
        $isLocalHigh = true;
        for ($j = $i - 5; $j <= $i + 5; $j++) {
            if ($j !== $i && $j >= 0 && $j < $count && $highs[$j] >= $highs[$i]) {
                $isLocalHigh = false;
                break;
            }
        }
        if ($isLocalHigh) {
            $liquidityAbove[] = [
                'price' => $highs[$i],
                'index' => $i
            ];
        }
    }
    
    // Ищем локальные минимумы (ликвидация шортов - ликвидность ниже)
    for ($i = 10; $i < $count - 5; $i++) {
        $isLocalLow = true;
        for ($j = $i - 5; $j <= $i + 5; $j++) {
            if ($j !== $i && $j >= 0 && $j < $count && $lows[$j] <= $lows[$i]) {
                $isLocalLow = false;
                break;
            }
        }
        if ($isLocalLow) {
            $liquidityBelow[] = [
                'price' => $lows[$i],
                'index' => $i
            ];
        }
    }
    
    // Сортируем по близости к текущей цене
    $currentPrice = end($closes);
    usort($liquidityAbove, fn($a, $b) => abs($a['price'] - $currentPrice) <=> abs($b['price'] - $currentPrice));
    usort($liquidityBelow, fn($a, $b) => abs($a['price'] - $currentPrice) <=> abs($b['price'] - $currentPrice));
    
    return [
        'above' => $liquidityAbove,
        'below' => $liquidityBelow
    ];
}
```

### 3. Определение Market Structure (BOS/CHOCH)

```php
private function detectMarketStructure(array $highs, array $lows, array $closes): ?string
{
    $count = count($closes);
    if ($count < 20) {
        return null;
    }

    $currentPrice = end($closes);
    $lookbackPeriod = 15; // Проверяем последние 15 свечей для BOS/CHOCH
    
    // Проверяем BOS/CHOCH в последних 15 свечах
    for ($i = max(0, $count - $lookbackPeriod); $i < $count; $i++) {
        $beforeHighs = array_slice($highs, max(0, $i - 10), min(10, $i));
        $beforeLows = array_slice($lows, max(0, $i - 10), min(10, $i));
        
        if (empty($beforeHighs) || empty($beforeLows)) {
            continue;
        }
        
        $highestHigh = max($beforeHighs);
        $lowestLow = min($beforeLows);
        
        $priceAtI = $closes[$i];
        $priceBeforeI = $i > 0 ? $closes[$i - 1] : $priceAtI;
        
        // BOS (Break Of Structure) - пробой предыдущего максимума/минимума
        if ($priceAtI > $highestHigh && $priceBeforeI <= $highestHigh) {
            if ($count - $i <= 10) {
                return 'BULLISH_BOS';
            }
        }
        if ($priceAtI < $lowestLow && $priceBeforeI >= $lowestLow) {
            if ($count - $i <= 10) {
                return 'BEARISH_BOS';
            }
        }
        
        // CHOCH (Change Of Character) - изменение характера движения
        if ($i >= 2) {
            $trend = $closes[$i] > $closes[$i - 1] ? 'BULLISH' : 'BEARISH';
            $prevTrend = $closes[$i - 1] > $closes[$i - 2] ? 'BULLISH' : 'BEARISH';
            
            if ($trend !== $prevTrend) {
                if ($count - $i <= 10) {
                    return $trend === 'BULLISH' ? 'BULLISH_CHOCH' : 'BEARISH_CHOCH';
                }
            }
        }
    }
    
    // Также проверяем текущую свечу
    $recentHighs = array_slice($highs, -20);
    $recentLows = array_slice($lows, -20);
    $highestHigh = max($recentHighs);
    $lowestLow = min($recentLows);
    $previousPrice = $closes[$count - 2];
    
    // BOS на текущей свече
    if ($currentPrice > $highestHigh && $previousPrice <= $highestHigh) {
        return 'BULLISH_BOS';
    }
    if ($currentPrice < $lowestLow && $previousPrice >= $lowestLow) {
        return 'BEARISH_BOS';
    }
    
    // CHOCH на текущей свече
    $trend = $currentPrice > $previousPrice ? 'BULLISH' : 'BEARISH';
    $prevTrend = $count > 2 ? ($closes[$count - 2] > $closes[$count - 3] ? 'BULLISH' : 'BEARISH') : null;
    
    if ($prevTrend && $trend !== $prevTrend) {
        return $trend === 'BULLISH' ? 'BULLISH_CHOCH' : 'BEARISH_CHOCH';
    }

    return null;
}
```

### 4. Поиск Fair Value Gaps (FVG)

```php
private function findFairValueGaps(array $highs, array $lows, array $closes): array
{
    $bullishFVG = [];
    $bearishFVG = [];
    $count = count($closes);
    
    if ($count < 3) {
        return ['bullish' => [], 'bearish' => []];
    }
    
    // Ищем FVG в последних 20 свечах
    for ($i = max(0, $count - 20); $i < $count - 2; $i++) {
        // Бычий FVG: максимум свечи 1 < минимум свечи 3
        if ($highs[$i] < $lows[$i + 2]) {
            $bullishFVG[] = [
                'bottom' => $highs[$i],
                'top' => $lows[$i + 2],
                'index' => $i
            ];
        }
        
        // Медвежий FVG: минимум свечи 1 > максимум свечи 3
        if ($lows[$i] > $highs[$i + 2]) {
            $bearishFVG[] = [
                'bottom' => $highs[$i + 2],
                'top' => $lows[$i],
                'index' => $i
            ];
        }
    }
    
    return [
        'bullish' => $bullishFVG,
        'bearish' => $bearishFVG
    ];
}
```

### 5. Проверка свечного подтверждения

```php
private function checkCandleConfirmation(array $opens, array $highs, array $lows, array $closes, string $htfTrend): array
{
    $count = count($closes);
    if ($count < 1) {
        return ['bullish' => false, 'bearish' => false];
    }
    
    $lastOpen = $opens[$count - 1];
    $lastClose = $closes[$count - 1];
    $lastHigh = $highs[$count - 1];
    $lastLow = $lows[$count - 1];
    
    $bodySize = abs($lastClose - $lastOpen);
    $candleRange = $lastHigh - $lastLow;
    $bodyRatio = $candleRange > 0 ? $bodySize / $candleRange : 0;
    
    // Бычье подтверждение: закрытие выше открытия, тело > 50% свечи
    $bullishConfirmation = $lastClose > $lastOpen && $bodyRatio > 0.5;
    
    // Медвежье подтверждение: закрытие ниже открытия, тело > 50% свечи
    $bearishConfirmation = $lastClose < $lastOpen && $bodyRatio > 0.5;
    
    return [
        'bullish' => $bullishConfirmation,
        'bearish' => $bearishConfirmation
    ];
}
```

---

## 📊 Структура возвращаемого результата

```php
return [
    'price' => $price,                    // Текущая цена
    'rsi' => $rsi,                        // RSI(14) на 15m
    'ema' => $ema,                        // EMA(50) на 15m
    'atr' => $atr,                        // ATR(14) для расчета TP
    'signal' => $signal,                  // 'BUY', 'SELL' или 'HOLD'
    'long_probability' => $longProb,      // Вероятность лонга (0-100)
    'short_probability' => $shortProb,    // Вероятность шорта (0-100)
    'stop_loss' => $stopLoss,              // Стоп-лосс
    'take_profit' => $takeProfit,          // Тейк-профит
    'reason' => implode('. ', $reasons),   // Причина сигнала
    'strength' => $strength,               // 'STRONG', 'MEDIUM' или 'WEAK'
    'htf_trend' => $htfTrend,             // 'BULLISH', 'BEARISH' или 'NEUTRAL'
    'htf_rsi' => $htfRsi,                 // RSI(14) на 4h
    'order_block_high' => $orderBlockHigh, // Верхняя граница Order Block
    'order_block_low' => $orderBlockLow,   // Нижняя граница Order Block
    'market_structure' => $marketStructure,// BOS/CHOCH структура
    'volume_ratio' => 1.0                  // Коэффициент объема
];
```

---

## 🔑 Ключевые моменты

1. **Тренд обязателен:** Без четкого тренда на H4 сигнал не генерируется
2. **Order Block обязателен:** Без возврата к OB сигнал не генерируется
3. **Ликвидность обязательна:** Без ликвидности рядом с OB сигнал не генерируется
4. **Минимум 100 баллов:** Для генерации сигнала нужно минимум 100 баллов
5. **STRONG ≥ 140 баллов:** Только STRONG сигналы отправляются в Telegram
6. **TP = ATR × 7.0:** Увеличенный тейк-профит для лучшего соотношения риск/прибыль



