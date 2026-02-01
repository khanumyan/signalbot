<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoAnalysisService
{
    private string $binanceApiUrl = 'https://fapi.binance.com/fapi/v1/klines';

    /**
     * Fetch klines data from Binance
     */
    public function fetchKlines(string $symbol, string $interval = '15m', int $limit = 100): array
    {
        try {
            $response = Http::timeout(10)->get($this->binanceApiUrl, [
                'symbol' => $symbol . 'USDT',
                'interval' => $interval,
                'limit' => $limit
            ]);

            if (!$response->successful()) {
                throw new \Exception("Failed to fetch data: " . $response->status());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error("Error fetching klines for {$symbol}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 🔒 Глобальный фильтр: Проверка рыночного контекста (BTC волатильность)
     * Возвращает true если можно отправлять сигналы, false если нужно приостановить
     */
    public function checkMarketContext(string $symbol, string $signalType, float $btcVolatilityThreshold = 3.0): array
    {
        try {
            // Получаем данные BTC за последние 2 свечи (15m)
            $btcKlines = $this->fetchKlines('BTC', '15m', 2);
            
            if (empty($btcKlines) || count($btcKlines) < 2) {
                // Если не удалось получить данные BTC, разрешаем сигналы (не блокируем)
                return ['allowed' => true, 'reason' => 'BTC data unavailable'];
            }

            $currentBtcPrice = (float) $btcKlines[count($btcKlines) - 1][4];
            $previousBtcPrice = (float) $btcKlines[count($btcKlines) - 2][4];
            
            // Рассчитываем волатильность BTC за 15m в процентах
            $btcVolatility = abs((($currentBtcPrice - $previousBtcPrice) / $previousBtcPrice) * 100);

            // Если BTC волатильность слишком высокая - приостанавливаем сигналы
            if ($btcVolatility > $btcVolatilityThreshold) {
                return [
                    'allowed' => false,
                    'reason' => "BTC волатильность слишком высокая: " . number_format($btcVolatility, 2) . "% (лимит: {$btcVolatilityThreshold}%)"
                ];
            }

            // Дополнительная проверка: если BTC резко падает, блокируем SELL сигналы для альтов
            if ($symbol !== 'BTC' && $signalType === 'SELL') {
                $btcChange = (($currentBtcPrice - $previousBtcPrice) / $previousBtcPrice) * 100;
                
                // Если BTC падает более чем на 1% за 15m, блокируем SELL для альтов
                if ($btcChange < -1.0) {
                    return [
                        'allowed' => false,
                        'reason' => "BTC падает: " . number_format($btcChange, 2) . "% - SELL сигналы для альтов заблокированы"
                    ];
                }
            }

            return ['allowed' => true, 'reason' => 'Market context OK', 'btc_volatility' => $btcVolatility];
        } catch (\Exception $e) {
            // При ошибке разрешаем сигналы (не блокируем)
            Log::warning("Market context check failed: " . $e->getMessage());
            return ['allowed' => true, 'reason' => 'Market context check failed: ' . $e->getMessage()];
        }
    }

    /**
     * Calculate RSI
     */
    public function calculateRSI(array $closes, int $period = 14): float
    {
        if (count($closes) < $period + 1) {
            return 50.0;
        }

        $deltas = [];
        for ($i = 1; $i < count($closes); $i++) {
            $deltas[] = $closes[$i] - $closes[$i - 1];
        }

        $gains = array_map(fn($delta) => max(0, $delta), $deltas);
        $losses = array_map(fn($delta) => max(0, -$delta), $deltas);

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    /**
     * Calculate EMA
     */
    public function calculateEMA(array $closes, int $period): float
    {
        if (count($closes) < $period) {
            return (float) end($closes);
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;

        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }

        return $ema;
    }

    /**
     * Calculate Bollinger Bands
     */
    public function calculateBollingerBands(array $closes, int $period = 20, float $std = 2): array
    {
        if (count($closes) < $period) {
            $price = end($closes);
            return ['upper' => $price, 'middle' => $price, 'lower' => $price];
        }

        $sma = array_sum(array_slice($closes, -$period)) / $period;
        $variance = 0.0;

        for ($i = count($closes) - $period; $i < count($closes); $i++) {
            $variance += pow($closes[$i] - $sma, 2);
        }

        $stdDev = sqrt($variance / $period);

        return [
            'upper' => $sma + ($stdDev * $std),
            'middle' => $sma,
            'lower' => $sma - ($stdDev * $std)
        ];
    }

    /**
     * Calculate ATR
     */
    public function calculateATR(array $highs, array $lows, array $closes, int $period = 14): float
    {
        if (count($highs) < $period + 1) {
            return 0.0;
        }

        $trueRanges = [];
        for ($i = 1; $i < count($highs); $i++) {
            $tr1 = $highs[$i] - $lows[$i];
            $tr2 = abs($highs[$i] - $closes[$i - 1]);
            $tr3 = abs($lows[$i] - $closes[$i - 1]);
            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        return array_sum(array_slice($trueRanges, -$period)) / $period;
    }

    /**
     * Calculate ADX (Average Directional Index)
     * Returns array with ['adx' => float, 'pdi' => float, 'mdi' => float]
     */
    public function calculateADX(array $highs, array $lows, array $closes, int $period = 14): array
    {
        if (count($highs) < $period * 2) {
            return ['adx' => 0.0, 'pdi' => 0.0, 'mdi' => 0.0];
        }

        // Calculate +DM and -DM
        $plusDM = [];
        $minusDM = [];
        
        for ($i = 1; $i < count($highs); $i++) {
            $upMove = $highs[$i] - $highs[$i - 1];
            $downMove = $lows[$i - 1] - $lows[$i];
            
            if ($upMove > $downMove && $upMove > 0) {
                $plusDM[] = $upMove;
            } else {
                $plusDM[] = 0;
            }
            
            if ($downMove > $upMove && $downMove > 0) {
                $minusDM[] = $downMove;
            } else {
                $minusDM[] = 0;
            }
        }

        // Calculate ATR values for each period (for smoothing)
        $atrValues = [];
        for ($i = $period; $i < count($highs); $i++) {
            $atrSlice = $this->calculateATR(
                array_slice($highs, 0, $i + 1),
                array_slice($lows, 0, $i + 1),
                array_slice($closes, 0, $i + 1),
                $period
            );
            $atrValues[] = $atrSlice;
        }
        
        // Calculate +DI and -DI for each period
        $dxValues = [];
        $pdiValues = [];
        $mdiValues = [];
        
        for ($i = 0; $i < count($atrValues); $i++) {
            $idx = $i + $period;
            $smoothedPlusDM = array_sum(array_slice($plusDM, $idx - $period, $period)) / $period;
            $smoothedMinusDM = array_sum(array_slice($minusDM, $idx - $period, $period)) / $period;
            
            $atr = $atrValues[$i];
            $pdi = $atr > 0 ? (($smoothedPlusDM / $atr) * 100) : 0;
            $mdi = $atr > 0 ? (($smoothedMinusDM / $atr) * 100) : 0;
            
            $pdiValues[] = $pdi;
            $mdiValues[] = $mdi;
            
            // Calculate DX
            $diSum = $pdi + $mdi;
            $dx = $diSum > 0 ? (abs($pdi - $mdi) / $diSum) * 100 : 0;
            $dxValues[] = $dx;
        }
        
        // ADX is smoothed DX (Wilder's smoothing)
        if (empty($dxValues)) {
            return ['adx' => 0.0, 'pdi' => 0.0, 'mdi' => 0.0];
        }
        
        // First ADX value is average of first period DX values
        $adx = array_sum(array_slice($dxValues, 0, min($period, count($dxValues)))) / min($period, count($dxValues));
        
        // Smooth ADX using Wilder's method
        for ($i = $period; $i < count($dxValues); $i++) {
            $adx = (($adx * ($period - 1)) + $dxValues[$i]) / $period;
        }
        
        // Current +DI and -DI
        $pdi = !empty($pdiValues) ? end($pdiValues) : 0;
        $mdi = !empty($mdiValues) ? end($mdiValues) : 0;
        
        return [
            'adx' => $adx,
            'pdi' => $pdi,
            'mdi' => $mdi
        ];
    }

    /**
     * Calculate MACD
     */
    public function calculateMACD(array $closes, int $fastPeriod = 12, int $slowPeriod = 26, int $signalPeriod = 9): array
    {
        if (count($closes) < $slowPeriod + $signalPeriod) {
            return ['macd' => 0, 'signal' => 0, 'histogram' => 0];
        }

        $emaFast = $this->calculateEMA($closes, $fastPeriod);
        $emaSlow = $this->calculateEMA($closes, $slowPeriod);
        $macdLine = $emaFast - $emaSlow;

        // Calculate signal line (EMA of MACD)
        $macdValues = [];
        for ($i = $slowPeriod; $i < count($closes); $i++) {
            $slice = array_slice($closes, 0, $i + 1);
            $eFast = $this->calculateEMA($slice, $fastPeriod);
            $eSlow = $this->calculateEMA($slice, $slowPeriod);
            $macdValues[] = $eFast - $eSlow;
        }

        $signalLine = count($macdValues) >= $signalPeriod ? $this->calculateEMA($macdValues, $signalPeriod) : 0;
        $histogram = $macdLine - $signalLine;

        return [
            'macd' => $macdLine,
            'signal' => $signalLine,
            'histogram' => $histogram
        ];
    }

    /**
     * Analyze symbol with MTF strategy
     */
    /**
     * Analyze symbol with MTF (Multi-Timeframe) strategy
     * Новая логика:
     * - HTF (1h): только фильтр, не начисляет баллы
     * - 15m: основная балльная система
     * - 5m: триггер входа (+10 баллов максимум)
     */
    public function analyzeMTF(string $symbol, array $params): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $emaPeriod = $params['ema_period'] ?? 50;
        $emaFastPeriod = $params['ema_fast_period'] ?? 20;

        // Fetch data for different timeframes
        $klines15m = $this->fetchKlines($symbol, '15m', 100);
        $klines1h = $this->fetchKlines($symbol, '1h', 100);
        $klines5m = $this->fetchKlines($symbol, '5m', 100);

        if (empty($klines15m) || empty($klines1h) || empty($klines5m)) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Extract price data
        $closes15m = array_map(fn($k) => (float) $k[4], $klines15m);
        $opens15m = array_map(fn($k) => (float) $k[1], $klines15m);
        $highs15m = array_map(fn($k) => (float) $k[2], $klines15m);
        $lows15m = array_map(fn($k) => (float) $k[3], $klines15m);
        
        $closes1h = array_map(fn($k) => (float) $k[4], $klines1h);
        $closes5m = array_map(fn($k) => (float) $k[4], $klines5m);
        $opens5m = array_map(fn($k) => (float) $k[1], $klines5m);
        $highs5m = array_map(fn($k) => (float) $k[2], $klines5m);
        $lows5m = array_map(fn($k) => (float) $k[3], $klines5m);

        // Calculate indicators
        $price = end($closes15m);
        $price1h = end($closes1h);
        
        $rsi15m = $this->calculateRSI($closes15m, $rsiPeriod);
        $rsi1h = $this->calculateRSI($closes1h, $rsiPeriod);
        $rsi5m = $this->calculateRSI($closes5m, $rsiPeriod);
        
        $ema50_15m = $this->calculateEMA($closes15m, $emaPeriod);
        $ema20_15m = $this->calculateEMA($closes15m, $emaFastPeriod);
        $ema50_1h = $this->calculateEMA($closes1h, $emaPeriod);
        
        $ema9_5m = $this->calculateEMA($closes5m, 9);
        $ema21_5m = $this->calculateEMA($closes5m, 21);
        
        $bb15m = $this->calculateBollingerBands($closes15m, $params['bb_period'] ?? 20, $params['bb_std_dev'] ?? 2);
        $atr = $this->calculateATR($highs15m, $lows15m, $closes15m, $params['atr_period'] ?? 14);

        // 🔑 ШАГ 1: HTF (1h) - ТОЛЬКО ФИЛЬТР (обязательный, не начисляет баллы)
        $buyAllowed = ($price1h >= $ema50_1h) || ($rsi1h >= 45);
        $sellAllowed = ($price1h <= $ema50_1h) || ($rsi1h <= 55);

        // Если HTF блокирует оба направления - возвращаем HOLD
        if (!$buyAllowed && !$sellAllowed) {
            return [
                'price' => $price,
                'rsi' => round($rsi15m, 2),
                'rsi_htf' => round($rsi1h, 2),
                'rsi_ltf' => round($rsi5m, 2),
                'ema' => round($ema50_15m, 2),
                'ema_htf' => round($ema50_1h, 2),
                'bb_upper' => round($bb15m['upper'], 2),
                'bb_lower' => round($bb15m['lower'], 2),
                'atr' => round($atr, 2),
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => 'HTF (1h) блокирует оба направления',
                'strength' => 'WEAK'
            ];
        }

        // 🔑 ШАГ 2: Основная логика - 15m (балльная система)
        $longScore = 0;
        $shortScore = 0;

        // BUY условия (15m)
        if ($buyAllowed) {
            // RSI(15m) ≤ 35
            if ($rsi15m <= 35) {
                $longScore += 25;
            } elseif ($rsi15m > 35 && $rsi15m <= 45) {
                $longScore += 15;
            }

            // Цена ≤ нижняя BB × 1.01
            if ($price <= $bb15m['lower'] * 1.01) {
                $longScore += 25;
            }

            // BB position ≤ 25%
            $bbWidth = $bb15m['upper'] - $bb15m['lower'];
            if ($bbWidth > 0) {
                $bbPosition = (($price - $bb15m['lower']) / $bbWidth) * 100;
                if ($bbPosition <= 25) {
                    $longScore += 15;
                }
            }

            // Цена выше EMA50(15m)
            if ($price > $ema50_15m) {
                $longScore += 15;
            }

            // EMA20 > EMA50
            if ($ema20_15m > $ema50_15m) {
                $longScore += 10;
            }

            // Bullish candle (engulfing или pin bar)
            if ($this->isBullishCandle($opens15m, $highs15m, $lows15m, $closes15m)) {
                $longScore += 10;
            }
        }

        // SELL условия (15m) - зеркально
        if ($sellAllowed) {
            // RSI(15m) ≥ 65
            if ($rsi15m >= 65) {
                $shortScore += 25;
            } elseif ($rsi15m >= 55 && $rsi15m < 65) {
                $shortScore += 15;
            }

            // Цена ≥ верхняя BB × 0.99
            if ($price >= $bb15m['upper'] * 0.99) {
                $shortScore += 25;
            }

            // BB position ≥ 75%
            $bbWidth = $bb15m['upper'] - $bb15m['lower'];
            if ($bbWidth > 0) {
                $bbPosition = (($price - $bb15m['lower']) / $bbWidth) * 100;
                if ($bbPosition >= 75) {
                    $shortScore += 15;
                }
            }

            // Цена ниже EMA50(15m)
            if ($price < $ema50_15m) {
                $shortScore += 15;
            }

            // EMA20 < EMA50
            if ($ema20_15m < $ema50_15m) {
                $shortScore += 10;
            }

            // Bearish candle
            if ($this->isBearishCandle($opens15m, $highs15m, $lows15m, $closes15m)) {
                $shortScore += 10;
            }
        }

        // 🔑 ШАГ 3: LTF (5m) - ТРИГГЕР, не фильтр (макс +10 баллов)
        $ltfTrigger = false;
        
        // BUY триггеры (5m)
        if ($buyAllowed && $longScore > 0) {
            $prevRsi5m = count($closes5m) > 1 ? $this->calculateRSI(array_slice($closes5m, 0, -1), $rsiPeriod) : $rsi5m;
            
            // RSI(5m) пересёк 30 снизу
            if ($prevRsi5m < 30 && $rsi5m >= 30) {
                $longScore += 10;
                $ltfTrigger = true;
            }
            // Bullish candle
            elseif ($this->isBullishCandle($opens5m, $highs5m, $lows5m, $closes5m)) {
                $longScore += 10;
                $ltfTrigger = true;
            }
            // EMA9 > EMA21
            elseif ($ema9_5m > $ema21_5m) {
                $longScore += 10;
                $ltfTrigger = true;
            }
        }

        // SELL триггеры (5m)
        if ($sellAllowed && $shortScore > 0) {
            $prevRsi5m = count($closes5m) > 1 ? $this->calculateRSI(array_slice($closes5m, 0, -1), $rsiPeriod) : $rsi5m;
            
            // RSI(5m) пересёк 70 сверху
            if ($prevRsi5m > 70 && $rsi5m <= 70) {
                $shortScore += 10;
                $ltfTrigger = true;
            }
            // Bearish candle
            elseif ($this->isBearishCandle($opens5m, $highs5m, $lows5m, $closes5m)) {
                $shortScore += 10;
                $ltfTrigger = true;
            }
            // EMA9 < EMA21
            elseif ($ema9_5m < $ema21_5m) {
                $shortScore += 10;
                $ltfTrigger = true;
            }
        }

        // 🔧 Volatility фильтр: ATR% < 0.35% → HOLD (уберет флет и снизит шум)
        $atrPercent = $price > 0 ? (($atr / $price) * 100) : 0;
        if ($atrPercent < 0.35) {
            return [
                'price' => $price,
                'rsi' => round($rsi15m, 2),
                'rsi_htf' => round($rsi1h, 2),
                'rsi_ltf' => round($rsi5m, 2),
                'ema' => round($ema50_15m, 2),
                'ema_htf' => round($ema50_1h, 2),
                'bb_upper' => round($bb15m['upper'], 2),
                'bb_lower' => round($bb15m['lower'], 2),
                'atr' => round($atr, 2),
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Низкая волатильность: ATR% = " . number_format($atrPercent, 2) . "% (требуется ≥ 0.35%)",
                'strength' => 'WEAK'
            ];
        }

        // 🔑 ШАГ 4: Определение сигнала и силы
        $winningScore = max($longScore, $shortScore);
        $signal = 'HOLD';
        $strength = 'WEAK';

        // Проверяем обязательные условия для STRONG
        $hasCandleConfirmation = false;
        $hasEmaCross = false;
        
        if ($longScore > $shortScore) {
            // BUY: проверяем bullish candle или EMA20 > EMA50
            $hasCandleConfirmation = $this->isBullishCandle($opens15m, $highs15m, $lows15m, $closes15m);
            $hasEmaCross = $ema20_15m > $ema50_15m;
        } else {
            // SELL: проверяем bearish candle или EMA20 < EMA50
            $hasCandleConfirmation = $this->isBearishCandle($opens15m, $highs15m, $lows15m, $closes15m);
            $hasEmaCross = $ema20_15m < $ema50_15m;
        }
        
        $hasStrongConfirmation = $hasCandleConfirmation || $hasEmaCross;

        // Определяем силу сигнала
        if ($winningScore >= 70 && $hasStrongConfirmation) {
            // STRONG: минимум 70 баллов И обязательное подтверждение (свеча ИЛИ EMA cross)
            $strength = 'STRONG';
            $signal = $longScore > $shortScore ? 'BUY' : 'SELL';
        } elseif ($winningScore >= 70) {
            // 70+ баллов, но нет подтверждения → максимум MEDIUM
            $strength = 'MEDIUM';
            $signal = $longScore > $shortScore ? 'BUY' : 'SELL';
        } elseif ($winningScore >= 55) {
            $strength = 'MEDIUM';
            $signal = $longScore > $shortScore ? 'BUY' : 'SELL';
        } elseif ($winningScore >= 45) {
            $strength = 'WEAK';
            $signal = $longScore > $shortScore ? 'BUY' : 'SELL';
        }

        // Проверяем, что HTF разрешает выбранное направление
        if ($signal === 'BUY' && !$buyAllowed) {
            $signal = 'HOLD';
            $strength = 'WEAK';
        } elseif ($signal === 'SELL' && !$sellAllowed) {
            $signal = 'HOLD';
            $strength = 'WEAK';
        }

        // 🔑 ШАГ 5: Расчет SL/TP
        $stopLossMultiplier = 2.0;
        $takeProfitMultiplier = 2.5;

        if ($signal === 'BUY') {
            $stopLoss = min($price - ($atr * $stopLossMultiplier), $bb15m['lower'] * 0.98);
            $takeProfit = $price + ($atr * $takeProfitMultiplier);
        } elseif ($signal === 'SELL') {
            $stopLoss = max($price + ($atr * $stopLossMultiplier), $bb15m['upper'] * 1.02);
            $takeProfit = $price - ($atr * $takeProfitMultiplier);
        } else {
            $stopLoss = $price;
            $takeProfit = $price;
        }

        // Generate reason
        $reasons = [];
        if ($signal === 'BUY') {
            $reasons[] = "HTF (1h): разрешено (Цена ≥ EMA50 ИЛИ RSI ≥ 45)";
            $reasons[] = "15m баллы: {$longScore}";
            if ($ltfTrigger) {
                $reasons[] = "5m триггер: подтвержден";
            }
        } elseif ($signal === 'SELL') {
            $reasons[] = "HTF (1h): разрешено (Цена ≤ EMA50 ИЛИ RSI ≤ 55)";
            $reasons[] = "15m баллы: {$shortScore}";
            if ($ltfTrigger) {
                $reasons[] = "5m триггер: подтвержден";
            }
        } else {
            $reasons[] = "Недостаточно баллов (нужно ≥ 45, получено: {$winningScore})";
        }

        // Normalize to percentages for compatibility
        $totalScore = $longScore + $shortScore;
        $longProb = $totalScore > 0 ? round(($longScore / $totalScore) * 100) : 50;
        $shortProb = $totalScore > 0 ? round(($shortScore / $totalScore) * 100) : 50;

        return [
            'price' => $price,
            'rsi' => round($rsi15m, 2),
            'rsi_htf' => round($rsi1h, 2),
            'rsi_ltf' => round($rsi5m, 2),
            'ema' => round($ema50_15m, 2),
            'ema_htf' => round($ema50_1h, 2),
            'bb_upper' => round($bb15m['upper'], 2),
            'bb_lower' => round($bb15m['lower'], 2),
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'reason' => implode('. ', $reasons),
            'strength' => $strength
        ];
    }

    /**
     * Проверяет бычью свечу (engulfing или pin bar)
     */
    private function isBullishCandle(array $opens, array $highs, array $lows, array $closes): bool
    {
        $count = count($closes);
        if ($count < 2) {
            return false;
        }

        $lastOpen = $opens[$count - 1];
        $lastClose = $closes[$count - 1];
        $lastHigh = $highs[$count - 1];
        $lastLow = $lows[$count - 1];
        $prevOpen = $opens[$count - 2];
        $prevClose = $closes[$count - 2];

        // Бычья свеча: закрытие > открытие
        $isBullish = $lastClose > $lastOpen;
        
        // Engulfing: текущая свеча поглощает предыдущую
        $isEngulfing = $isBullish && $lastOpen < $prevClose && $lastClose > $prevOpen;
        
        // Pin bar: длинная нижняя тень (минимум 2/3 от диапазона)
        $candleRange = $lastHigh - $lastLow;
        $lowerShadow = min($lastOpen, $lastClose) - $lastLow;
        $isPinBar = $candleRange > 0 && ($lowerShadow / $candleRange) >= 0.67;

        return $isBullish && ($isEngulfing || $isPinBar);
    }

    /**
     * Проверяет медвежью свечу (engulfing или pin bar)
     */
    private function isBearishCandle(array $opens, array $highs, array $lows, array $closes): bool
    {
        $count = count($closes);
        if ($count < 2) {
            return false;
        }

        $lastOpen = $opens[$count - 1];
        $lastClose = $closes[$count - 1];
        $lastHigh = $highs[$count - 1];
        $lastLow = $lows[$count - 1];
        $prevOpen = $opens[$count - 2];
        $prevClose = $closes[$count - 2];

        // Медвежья свеча: закрытие < открытие
        $isBearish = $lastClose < $lastOpen;
        
        // Engulfing: текущая свеча поглощает предыдущую
        $isEngulfing = $isBearish && $lastOpen > $prevClose && $lastClose < $prevOpen;
        
        // Pin bar: длинная верхняя тень (минимум 2/3 от диапазона)
        $candleRange = $lastHigh - $lastLow;
        $upperShadow = $lastHigh - max($lastOpen, $lastClose);
        $isPinBar = $candleRange > 0 && ($upperShadow / $candleRange) >= 0.67;

        return $isBearish && ($isEngulfing || $isPinBar);
    }

    /**
     * Analyze symbol with EMA+RSI+MACD strategy
     * Работает по прежнему (без изменений) - простая логика без ужесточенных критериев
     */
    public function analyzeEmaRsiMacd(string $symbol, array $params): array
    {
        $emaFast = $params['ema_fast'] ?? 20;
        $emaSlow = $params['ema_slow'] ?? 50;
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $rsiBuyMax = $params['rsi_buy_max'] ?? 70;
        $rsiSellMin = $params['rsi_sell_min'] ?? 30;
        $macdFast = $params['macd_fast'] ?? 12;
        $macdSlow = $params['macd_slow'] ?? 26;
        $macdSignal = $params['macd_signal'] ?? 9;
        $interval = $params['interval'] ?? '15m';
        $limit = $params['limit'] ?? 200;

        // Fetch klines data
        $klines = $this->fetchKlines($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < 100) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Extract price data
        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        // Calculate indicators
        $price = end($closes);
        $ema20 = $this->calculateEMA($closes, $emaFast);
        $ema50 = $this->calculateEMA($closes, $emaSlow);
        $rsi = $this->calculateRSI($closes, $rsiPeriod);
        $macd = $this->calculateMACD($closes, $macdFast, $macdSlow, $macdSignal);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);

        $macdLine = $macd['macd'];
        $macdSignalLine = $macd['signal'];
        $macdHist = $macd['histogram'];

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        // BUY conditions
        if ($price > $ema20 && $ema20 > $ema50 && $macdLine > 0 && $macdHist > 0 && $rsi < $rsiBuyMax) {
            $longScore += 40;
            if ($rsi >= 40 && $rsi <= 60 && abs($macdHist) > 0.5) {
                $longScore += 30; // Strong momentum
            } elseif ($rsi > 30 && abs($macdHist) > 0.2) {
                $longScore += 15; // Medium momentum
            }
        } elseif ($price > $ema20 && $ema20 > $ema50) {
            $longScore += 20; // Trend alignment
        }

        // SELL conditions
        if ($price < $ema20 && $ema20 < $ema50 && $macdLine < 0 && $macdHist < 0 && $rsi > $rsiSellMin) {
            $shortScore += 40;
            if ($rsi >= 40 && $rsi <= 60 && abs($macdHist) > 0.5) {
                $shortScore += 30; // Strong momentum
            } elseif ($rsi < 70 && abs($macdHist) > 0.2) {
                $shortScore += 15; // Medium momentum
            }
        } elseif ($price < $ema20 && $ema20 < $ema50) {
            $shortScore += 20; // Trend alignment
        }

        // RSI signals
        if ($rsi <= 30) {
            $longScore += 20;
        } elseif ($rsi >= 70) {
            $shortScore += 20;
        }

        // MACD signals
        if ($macdHist > 0 && $macdLine > $macdSignalLine) {
            $longScore += 10;
        } elseif ($macdHist < 0 && $macdLine < $macdSignalLine) {
            $shortScore += 10;
        }

        // Normalize to percentages
        $totalScore = $longScore + $shortScore;
        $longProb = $totalScore > 0 ? round(($longScore / $totalScore) * 100) : 50;
        $shortProb = $totalScore > 0 ? round(($shortScore / $totalScore) * 100) : 50;

        // Determine signal
        $signal = $longProb > $shortProb ? 'BUY' : 'SELL';
        if (abs($longProb - $shortProb) < 5) {
            $signal = 'HOLD';
        }

        // Calculate SL/TP
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 2.3;
        $takeProfitMultiplier = $params['take_profit_multiplier'] ?? 2.0;

        if ($signal === 'BUY') {
            $stopLoss = $price - ($atr * $stopLossMultiplier);
            $takeProfit = $price + ($atr * $takeProfitMultiplier);
        } elseif ($signal === 'SELL') {
            $stopLoss = $price + ($atr * $stopLossMultiplier);
            $takeProfit = $price - ($atr * $takeProfitMultiplier);
        } else {
            $stopLoss = $price;
            $takeProfit = $price;
        }

        // Generate reason
        $trend = $ema20 > $ema50 ? 'Bullish' : 'Bearish';
        $macdDirection = $macdLine > 0 ? 'above zero' : 'below zero';
        $reasons = [];
        $reasons[] = "EMA{$emaFast} vs EMA{$emaSlow}: {$trend} trend";
        $reasons[] = "RSI: {$rsi}";
        $reasons[] = "MACD {$macdDirection}, Histogram: " . number_format($macdHist, 2);
        if ($signal !== 'HOLD') {
            $reasons[] = "Price " . ($signal === 'BUY' ? 'above' : 'below') . " EMA{$emaFast}";
        }

        // Return exact values (no rounding) - database supports decimal(20, 10)
        return [
            'price' => $price, // Exact price from provider, no rounding
            'rsi' => $rsi,
            'ema_fast' => $ema20,
            'ema_slow' => $ema50,
            'macd' => $macdLine,
            'macd_signal' => $macdSignalLine,
            'macd_histogram' => $macdHist,
            'atr' => $atr,
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => $stopLoss, // Exact value, no rounding
            'take_profit' => $takeProfit, // Exact value, no rounding
            'reason' => implode('. ', $reasons),
            'strength' => abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK')
        ];
    }

    /**
     * Analyze symbol with Bollinger+RSI strategy
     */
    public function analyzeBollingerRsi(string $symbol, array $params): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $rsiBuyThreshold = $params['rsi_buy_threshold'] ?? 30;
        $rsiSellThreshold = $params['rsi_sell_threshold'] ?? 70;
        $bbPeriod = $params['bb_period'] ?? 20;
        $bbStdDev = $params['bb_std_dev'] ?? 2;
        $interval = $params['interval'] ?? '15m';
        $limit = $params['limit'] ?? 100;

        // Fetch klines data
        $klines = $this->fetchKlines($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < 30) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Extract price data
        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        // Calculate indicators
        $price = end($closes);
        $bb = $this->calculateBollingerBands($closes, $bbPeriod, $bbStdDev);
        $rsi = $this->calculateRSI($closes, $rsiPeriod);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        // BUY: Price touches lower band + RSI < threshold
        $lowerBandTolerance = $bb['lower'] * 1.005;
        if ($price <= $lowerBandTolerance && $rsi < $rsiBuyThreshold) {
            $longScore += 50;
            if ($rsi <= 20) {
                $longScore += 30; // Very oversold
            } elseif ($rsi <= 25) {
                $longScore += 15; // Oversold
            }
        } elseif ($price <= $bb['lower'] * 1.02) {
            $longScore += 20; // Near lower band
        }

        // SELL: Price touches upper band + RSI > threshold
        $upperBandTolerance = $bb['upper'] * 0.995;
        if ($price >= $upperBandTolerance && $rsi > $rsiSellThreshold) {
            $shortScore += 50;
            if ($rsi >= 80) {
                $shortScore += 30; // Very overbought
            } elseif ($rsi >= 75) {
                $shortScore += 15; // Overbought
            }
        } elseif ($price >= $bb['upper'] * 0.98) {
            $shortScore += 20; // Near upper band
        }

        // RSI signals
        if ($rsi <= 20) {
            $longScore += 20;
        } elseif ($rsi >= 80) {
            $shortScore += 20;
        } elseif ($rsi < $rsiBuyThreshold) {
            $longScore += 10;
        } elseif ($rsi > $rsiSellThreshold) {
            $shortScore += 10;
        }

        // Price position in BB
        $bbWidth = $bb['upper'] - $bb['lower'];
        if ($bbWidth > 0) {
            $bbPosition = (($price - $bb['lower']) / $bbWidth) * 100;
            if ($bbPosition <= 10) {
                $longScore += 15; // Near lower band
            } elseif ($bbPosition >= 90) {
                $shortScore += 15; // Near upper band
            }
        }

        // Normalize to percentages
        $totalScore = $longScore + $shortScore;
        $longProb = $totalScore > 0 ? round(($longScore / $totalScore) * 100) : 50;
        $shortProb = $totalScore > 0 ? round(($shortScore / $totalScore) * 100) : 50;

        // Determine signal
        $signal = $longProb > $shortProb ? 'BUY' : 'SELL';
        if (abs($longProb - $shortProb) < 5) {
            $signal = 'HOLD';
        }

        // Calculate SL/TP based on strategy
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 2.0;
        $takeProfitMultiplier = $params['take_profit_multiplier'] ?? 2.0;

        if ($signal === 'BUY') {
            // SL: Below lower band or 2xATR (повышено для защиты от ложных пробоев)
            $stopLoss = min($price - ($atr * $stopLossMultiplier), $bb['lower'] * 0.98);
            $risk = $price - $stopLoss;
            // TP: Middle band or risk-based
            $takeProfit = min($bb['middle'], $price + ($risk * $takeProfitMultiplier));
        } elseif ($signal === 'SELL') {
            // SL: Above upper band or 2xATR (повышено для защиты от ложных пробоев)
            $stopLoss = max($price + ($atr * $stopLossMultiplier), $bb['upper'] * 1.02);
            $risk = $stopLoss - $price;
            // TP: Middle band or risk-based
            $takeProfit = max($bb['middle'], $price - ($risk * $takeProfitMultiplier));
        } else {
            $stopLoss = $price;
            $takeProfit = $price;
        }

        // Generate reason
        $reasons = [];
        if ($signal === 'BUY') {
            $reasons[] = "Цена у нижней полосы Bollinger Bands";
            $reasons[] = "RSI {$rsi} показывает перепроданность";
            $reasons[] = "Цель: средняя линия BB " . number_format($bb['middle'], 2);
        } elseif ($signal === 'SELL') {
            $reasons[] = "Цена у верхней полосы Bollinger Bands";
            $reasons[] = "RSI {$rsi} показывает перекупленность";
            $reasons[] = "Цель: средняя линия BB " . number_format($bb['middle'], 2);
        } else {
            $reasons[] = "Цена в середине диапазона Bollinger Bands";
            $reasons[] = "RSI {$rsi} в нейтральной зоне";
        }

        // Return exact values (no rounding) - database supports decimal(20, 10)
        return [
            'price' => $price, // Exact price from provider, no rounding
            'rsi' => $rsi,
            'bb_upper' => $bb['upper'],
            'bb_middle' => $bb['middle'],
            'bb_lower' => $bb['lower'],
            'atr' => $atr,
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => $stopLoss, // Exact value, no rounding
            'take_profit' => $takeProfit, // Exact value, no rounding
            'reason' => implode('. ', $reasons),
            'strength' => abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK')
        ];
    }

    /**
     * Calculate Stochastic Oscillator
     * Улучшенная версия: правильно рассчитывает D (сглаженный K)
     */
    public function calculateStochastic(array $highs, array $lows, array $closes, int $kPeriod = 14, int $kSmooth = 3, int $dPeriod = 3): array
    {
        if (count($closes) < $kPeriod + $kSmooth) {
            return ['k' => 50.0, 'd' => 50.0];
        }

        // Calculate raw %K values for all periods
        $rawK = [];
        for ($i = $kPeriod - 1; $i < count($closes); $i++) {
            $periodHighs = array_slice($highs, $i - $kPeriod + 1, $kPeriod);
            $periodLows = array_slice($lows, $i - $kPeriod + 1, $kPeriod);
            $currentClose = $closes[$i];
            
            $highest = max($periodHighs);
            $lowest = min($periodLows);
            
            if ($highest != $lowest) {
                $rawK[] = (($currentClose - $lowest) / ($highest - $lowest)) * 100;
            } else {
                $rawK[] = 50.0;
            }
        }

        // Smooth %K (SMA of raw K)
        $smoothedK = [];
        for ($i = $kSmooth - 1; $i < count($rawK); $i++) {
            $smoothPeriod = array_slice($rawK, $i - $kSmooth + 1, $kSmooth);
            $smoothedK[] = array_sum($smoothPeriod) / $kSmooth;
        }

        // Current %K (last smoothed value)
        $k = end($smoothedK);

        // Calculate %D (SMA of smoothed K)
        if (count($smoothedK) >= $dPeriod) {
            $dPeriodValues = array_slice($smoothedK, -$dPeriod);
            $d = array_sum($dPeriodValues) / $dPeriod;
        } else {
            $d = $k; // Fallback if not enough data
        }

        return ['k' => $k, 'd' => $d];
    }

    /**
     * Analyze symbol with EMA+Stochastic strategy (Scalping)
     * Новая "боевая" логика:
     * - Блок 1: Направление (EMA9 >/< EMA21) - обязательный
     * - Блок 2: Тайминг (K пересекает D) - только момент пересечения
     * - Блок 3: Зона Stochastic (K в диапазоне)
     * - Сила на основе |K - D|: STRONG ≥ 7, MEDIUM 3-6, WEAK < 3 (не торгуем)
     * - ATR-фильтр: ATR(14) > SMA(ATR, 20)
     * - Защита от флет-пилы: |EMA9 - EMA21| ≥ 0.1 × ATR
     */
    public function analyzeEmaStochastic(string $symbol, array $params): array
    {
        $emaFast = $params['ema_fast'] ?? 9;
        $emaSlow = $params['ema_slow'] ?? 21;
        $stochKPeriod = $params['stoch_k_period'] ?? 14;
        $stochKSmooth = $params['stoch_k_smooth'] ?? 3;
        $stochDPeriod = $params['stoch_d_period'] ?? 3;
        $interval = $params['interval'] ?? '5m';
        $limit = $params['limit'] ?? 100;

        // Fetch klines data
        $klines = $this->fetchKlines($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < 30) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Extract price data
        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        // Calculate indicators
        $price = end($closes);
        $ema9 = $this->calculateEMA($closes, $emaFast);
        $ema21 = $this->calculateEMA($closes, $emaSlow);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);
        
        // Calculate Stochastic (нужны предыдущие значения для определения пересечения)
        $stochastic = $this->calculateStochastic($highs, $lows, $closes, $stochKPeriod, $stochKSmooth, $stochDPeriod);
        $k = $stochastic['k'];
        $d = $stochastic['d'];
        
        // Получаем предыдущие значения K и D для определения пересечения
        $prevCloses = array_slice($closes, 0, -1);
        $prevHighs = array_slice($highs, 0, -1);
        $prevLows = array_slice($lows, 0, -1);
        $prevStochastic = count($prevCloses) >= $stochKPeriod ? 
            $this->calculateStochastic($prevHighs, $prevLows, $prevCloses, $stochKPeriod, $stochKSmooth, $stochDPeriod) : 
            ['k' => $k, 'd' => $d];
        $prevK = $prevStochastic['k'];
        $prevD = $prevStochastic['d'];

        // 🔧 ФИЛЬТР 1: ATR-фильтр (ATR(14) > SMA(ATR, 20))
        // Рассчитываем SMA(ATR, 20) - нужны предыдущие ATR значения
        $atrValues = [];
        for ($i = 14; $i < count($closes); $i++) {
            $sliceCloses = array_slice($closes, 0, $i + 1);
            $sliceHighs = array_slice($highs, 0, $i + 1);
            $sliceLows = array_slice($lows, 0, $i + 1);
            $atrValues[] = $this->calculateATR($sliceHighs, $sliceLows, $sliceCloses, 14);
        }
        $smaAtr = count($atrValues) >= 20 ? array_sum(array_slice($atrValues, -20)) / 20 : $atr;
        
        if ($atr <= $smaAtr) {
            return [
                'price' => $price,
                'rsi' => 50.0,
                'ema_fast' => $ema9,
                'ema_slow' => $ema21,
                'stochastic_k' => $k,
                'stochastic_d' => $d,
                'atr' => $atr,
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Нет волатильности: ATR({$atr}) ≤ SMA(ATR,20)(" . number_format($smaAtr, 4) . ")",
                'strength' => 'WEAK'
            ];
        }

        // 🔧 ФИЛЬТР 2: Защита от флет-пилы (|EMA9 - EMA21| ≥ 0.1 × ATR)
        $emaDistance = abs($ema9 - $ema21);
        $minEmaDistance = $atr * 0.1;
        
        if ($emaDistance < $minEmaDistance) {
            return [
                'price' => $price,
                'rsi' => 50.0,
                'ema_fast' => $ema9,
                'ema_slow' => $ema21,
                'stochastic_k' => $k,
                'stochastic_d' => $d,
                'atr' => $atr,
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Флет-пила: |EMA9 - EMA21| = " . number_format($emaDistance, 4) . " < 0.1×ATR = " . number_format($minEmaDistance, 4),
                'strength' => 'WEAK'
            ];
        }

        // 🔑 БЛОК 1: НАПРАВЛЕНИЕ (обязательный)
        $buyDirection = $ema9 > $ema21;
        $sellDirection = $ema9 < $ema21;
        
        if (!$buyDirection && !$sellDirection) {
            return [
                'price' => $price,
                'rsi' => 50.0,
                'ema_fast' => $ema9,
                'ema_slow' => $ema21,
                'stochastic_k' => $k,
                'stochastic_d' => $d,
                'atr' => $atr,
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Нет направления: EMA9 = EMA21",
                'strength' => 'WEAK'
            ];
        }

        // 🔑 БЛОК 2: ТАЙМИНГ (K пересекает D - только момент пересечения)
        $kCrossesDUp = ($prevK <= $prevD) && ($k > $d); // K пересекает D снизу
        $kCrossesDDown = ($prevK >= $prevD) && ($k < $d); // K пересекает D сверху

        // 🔑 БЛОК 3: ЗОНА STOCHASTIC
        $kInBuyZone = $k >= 20 && $k <= 50;
        $kInSellZone = $k >= 50 && $k <= 80;
        
        // Проверяем, что НЕ входим в неправильные зоны
        $buyBlocked = $k > 60; // BUY при K > 60 - не входить
        $sellBlocked = $k < 40; // SELL при K < 40 - не входить

        // Определяем сигнал
        $signal = 'HOLD';
        $strength = 'WEAK';
        $delta = abs($k - $d);

        // BUY условия
        if ($buyDirection && $kCrossesDUp && $kInBuyZone && !$buyBlocked) {
            // Определяем силу на основе |K - D|
            if ($delta >= 7) {
                $strength = 'STRONG';
                $signal = 'BUY';
            } elseif ($delta >= 3) {
                $strength = 'MEDIUM';
                $signal = 'BUY';
            }
            // WEAK (< 3) - не торгуем
        }

        // SELL условия
        if ($sellDirection && $kCrossesDDown && $kInSellZone && !$sellBlocked) {
            // Определяем силу на основе |K - D|
            if ($delta >= 7) {
                $strength = 'STRONG';
                $signal = 'SELL';
            } elseif ($delta >= 3) {
                $strength = 'MEDIUM';
                $signal = 'SELL';
            }
            // WEAK (< 3) - не торгуем
        }

        // Если WEAK - не торгуем
        if ($strength === 'WEAK') {
            return [
                'price' => $price,
                'rsi' => 50.0,
                'ema_fast' => $ema9,
                'ema_slow' => $ema21,
                'stochastic_k' => $k,
                'stochastic_d' => $d,
                'atr' => $atr,
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Слабый импульс: |K - D| = " . number_format($delta, 2) . " < 3 (требуется ≥ 3)",
                'strength' => 'WEAK'
            ];
        }

        // 🔧 РИСК-МЕНЕДЖМЕНТ: SL всегда ATR × 1.5, TP зависит от силы
        $stopLossMultiplier = 1.5; // Всегда
        $takeProfitMultiplier = match($strength) {
            'STRONG' => 2.4,
            'MEDIUM' => 1.8,
            default => 1.2
        };

        if ($signal === 'BUY') {
            $stopLoss = $price - ($atr * $stopLossMultiplier);
            $takeProfit = $price + ($atr * $takeProfitMultiplier);
        } elseif ($signal === 'SELL') {
            $stopLoss = $price + ($atr * $stopLossMultiplier);
            $takeProfit = $price - ($atr * $takeProfitMultiplier);
        } else {
            $stopLoss = $price;
            $takeProfit = $price;
        }

        // Generate reason
        $reasons = [];
        $reasons[] = "Направление: EMA{$emaFast} " . ($buyDirection ? '>' : '<') . " EMA{$emaSlow}";
        $reasons[] = "Тайминг: K пересек D " . ($kCrossesDUp ? 'снизу' : 'сверху');
        $reasons[] = "Зона Stochastic: K = " . number_format($k, 1) . " (BUY: [20-50], SELL: [50-80])";
        $reasons[] = "Импульс: |K - D| = " . number_format($delta, 2) . " ({$strength})";
        $reasons[] = "ATR фильтр: OK (ATR > SMA)";
        $reasons[] = "Защита от флета: OK (|EMA9 - EMA21| ≥ 0.1×ATR)";

        // Normalize to percentages for compatibility
        $longProb = $signal === 'BUY' ? 100 : 0;
        $shortProb = $signal === 'SELL' ? 100 : 0;

        // Return exact values (no rounding) - database supports decimal(20, 10)
        return [
            'price' => $price, // Exact price from provider, no rounding
            'rsi' => 50.0, // Not used in this strategy
            'ema_fast' => $ema9,
            'ema_slow' => $ema21,
            'stochastic_k' => $k,
            'stochastic_d' => $d,
            'atr' => $atr,
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => $stopLoss, // Exact value, no rounding
            'take_profit' => $takeProfit, // Exact value, no rounding
            'reason' => implode('. ', $reasons),
            'strength' => $strength
        ];
    }

    /**
     * Calculate SuperTrend indicator
     */
    public function calculateSuperTrend(array $highs, array $lows, array $closes, int $period = 10, float $multiplier = 3.0): array
    {
        if (count($closes) < $period) {
            $price = end($closes);
            return ['value' => $price, 'trend' => 'UP'];
        }

        $atr = $this->calculateATR($highs, $lows, $closes, $period);
        $hl2 = []; // (High + Low) / 2
        $upperBand = [];
        $lowerBand = [];
        $superTrend = [];
        $trend = [];

        for ($i = 0; $i < count($closes); $i++) {
            $hl2[$i] = ($highs[$i] + $lows[$i]) / 2;
            
            if ($i < $period) {
                $upperBand[$i] = $hl2[$i] + ($multiplier * $atr);
                $lowerBand[$i] = $hl2[$i] - ($multiplier * $atr);
                $superTrend[$i] = $lowerBand[$i];
                $trend[$i] = 'UP';
            } else {
                $upperBand[$i] = $hl2[$i] + ($multiplier * $atr);
                $lowerBand[$i] = $hl2[$i] - ($multiplier * $atr);
                
                // Adjust bands
                if ($closes[$i] <= $upperBand[$i - 1]) {
                    $upperBand[$i] = min($upperBand[$i], $upperBand[$i - 1]);
                }
                if ($closes[$i] >= $lowerBand[$i - 1]) {
                    $lowerBand[$i] = max($lowerBand[$i], $lowerBand[$i - 1]);
                }
                
                // Determine SuperTrend
                if ($i > 0) {
                    if ($closes[$i] <= $superTrend[$i - 1]) {
                        $superTrend[$i] = $upperBand[$i];
                        $trend[$i] = 'DOWN';
                    } else {
                        $superTrend[$i] = $lowerBand[$i];
                        $trend[$i] = 'UP';
                    }
                } else {
                    $superTrend[$i] = $lowerBand[$i];
                    $trend[$i] = 'UP';
                }
            }
        }

        $currentTrend = end($trend);
        $currentValue = end($superTrend);

        return [
            'value' => $currentValue,
            'trend' => $currentTrend,
            'upper_band' => end($upperBand),
            'lower_band' => end($lowerBand)
        ];
    }

    /**
     * Calculate VWAP (Volume Weighted Average Price)
     */
    public function calculateVWAP(array $highs, array $lows, array $closes, array $volumes): float
    {
        if (count($closes) === 0 || count($volumes) === 0) {
            return end($closes) ?? 0;
        }

        $totalPV = 0; // Price * Volume
        $totalVolume = 0;

        for ($i = 0; $i < count($closes); $i++) {
            $typicalPrice = ($highs[$i] + $lows[$i] + $closes[$i]) / 3;
            $volume = (float) ($volumes[$i] ?? 0);
            $totalPV += $typicalPrice * $volume;
            $totalVolume += $volume;
        }

        if ($totalVolume == 0) {
            return end($closes);
        }

        return $totalPV / $totalVolume;
    }

    /**
     * Analyze symbol with SuperTrend+VWAP strategy (IMPROVED VERSION)
     * 
     * Improvements:
     * 1. ADX filter (> 20) - only trade with momentum
     * 2. VWAP bounce confirmation - entry only after bounce, not just "near"
     * 3. Stricter scoring system (min 80 total, min 20% difference)
     * 4. Higher timeframe filter (1h trend filter for 15m entries)
     * 5. Better SL/TP ratio (1.5:3.0 instead of 2.0:2.0)
     * 6. Signal frequency limit (max 1 signal per symbol in 3-5 candles)
     */
    public function analyzeSuperTrendVwap(string $symbol, array $params): array
    {
        $supertrendPeriod = $params['supertrend_period'] ?? 10;
        $supertrendMultiplier = $params['supertrend_multiplier'] ?? 3.0;
        $interval = $params['interval'] ?? '15m';
        $limit = $params['limit'] ?? 100;
        $adxPeriod = $params['adx_period'] ?? 14;
        $adxThreshold = $params['adx_threshold'] ?? 25.0; // 🔥 Повышен до 25
        $atrVolatilityThreshold = $params['atr_volatility_threshold'] ?? 0.6; // 🔥 Повышен до 0.6%

        // Fetch klines data
        $klines = $this->fetchKlines($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < 30) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Extract price data
        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);
        $volumes = array_map(fn($k) => (float) $k[5], $klines);

        // Calculate indicators
        $price = end($closes);
        $superTrend = $this->calculateSuperTrend($highs, $lows, $closes, $supertrendPeriod, $supertrendMultiplier);
        $vwap = $this->calculateVWAP($highs, $lows, $closes, $volumes);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);
        $adx = $this->calculateADX($highs, $lows, $closes, $adxPeriod);

        // 🔒 1. УЖЕСТОЧЕННЫЙ FILTER: ADX ≥ 25 И ATR ≥ 0.6%
        $atrVolatilityPercent = $price > 0 ? (($atr / $price) * 100) : 0;
        $hasMomentum = $adx['adx'] >= $adxThreshold && $atrVolatilityPercent >= $atrVolatilityThreshold;
        
        if (!$hasMomentum) {
            // No momentum - return HOLD
            return [
                'price' => $price,
                'supertrend_value' => $superTrend['value'],
                'supertrend_trend' => $superTrend['trend'],
                'vwap' => $vwap,
                'price_to_vwap_percent' => 0,
                'atr' => $atr,
                'adx' => $adx['adx'],
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Нет импульса: ADX=" . number_format($adx['adx'], 2) . " (требуется ≥{$adxThreshold}), ATR волатильность=" . number_format($atrVolatilityPercent, 2) . "% (требуется ≥{$atrVolatilityThreshold}%)",
                'strength' => 'WEAK'
            ];
        }
        
        // 🔒 4. Higher timeframe filter (if interval is 15m, check 1h trend)
        $htfTrend = null;
        if ($interval === '15m') {
            try {
                $htfKlines = $this->fetchKlines($symbol, '1h', 50);
                if (!empty($htfKlines) && count($htfKlines) >= 30) {
                    $htfCloses = array_map(fn($k) => (float) $k[4], $htfKlines);
                    $htfHighs = array_map(fn($k) => (float) $k[2], $htfKlines);
                    $htfLows = array_map(fn($k) => (float) $k[3], $htfKlines);
                    $htfSuperTrend = $this->calculateSuperTrend($htfHighs, $htfLows, $htfCloses, $supertrendPeriod, $supertrendMultiplier);
                    $htfTrend = $htfSuperTrend['trend'];
                }
            } catch (\Exception $e) {
                // If HTF fetch fails, continue without filter
                Log::warning("Failed to fetch HTF data for {$symbol}: " . $e->getMessage());
            }
        }

        // 🔥 Дополнительный RSI фильтр для SuperTrend+VWAP
        $rsi = $this->calculateRSI($closes, 14);
        $rsiBuyThreshold = 25;  // BUY: RSI ≤ 25
        $rsiSellThreshold = 75; // SELL: RSI ≥ 75
        
        // 🔒 2. VWAP BOUNCE CONFIRMATION (not just "near")
        // Check previous candles to confirm bounce
        $currentClose = $closes[count($closes) - 1];
        $previousClose = count($closes) > 1 ? $closes[count($closes) - 2] : $currentClose;
        $currentLow = $lows[count($lows) - 1];
        $currentHigh = $highs[count($highs) - 1];
        
        $trend = $superTrend['trend'];
        $priceToVwap = $price - $vwap;
        $priceToVwapPercent = $vwap > 0 ? (($priceToVwap / $vwap) * 100) : 0;
        
        // Проверяем RSI перед продолжением
        $rsiPassed = false;
        if ($trend === 'UP' && $rsi <= $rsiBuyThreshold) {
            $rsiPassed = true;
        } elseif ($trend === 'DOWN' && $rsi >= $rsiSellThreshold) {
            $rsiPassed = true;
        }
        
        if (!$rsiPassed) {
            return [
                'price' => $price,
                'supertrend_value' => $superTrend['value'],
                'supertrend_trend' => $trend,
                'vwap' => $vwap,
                'price_to_vwap_percent' => 0,
                'atr' => $atr,
                'adx' => $adx['adx'],
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "RSI не в экстремальной зоне: {$rsi} (BUY требуется ≤{$rsiBuyThreshold}, SELL требуется ≥{$rsiSellThreshold})",
                'strength' => 'WEAK'
            ];
        }

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        // BUY conditions with bounce confirmation
        if ($trend === 'UP') {
            // Check HTF filter
            if ($htfTrend === 'DOWN') {
                // HTF trend is DOWN, skip BUY signals
                $longScore = 0;
            } else {
                $longScore += 30; // Base score for UP trend
                
                // 🔒 2. BUY: Price was below VWAP, now closed above VWAP (bounce)
                $wasBelowVwap = $previousClose < $vwap;
                $nowAboveVwap = $currentClose > $vwap;
                $lowAboveSuperTrend = $currentLow > $superTrend['value'];
                
                if ($wasBelowVwap && $nowAboveVwap && $lowAboveSuperTrend) {
                    $longScore += 50; // Strong bounce confirmation
                } elseif ($nowAboveVwap && $lowAboveSuperTrend) {
                    $longScore += 30; // Price above VWAP and SuperTrend
                } elseif ($wasBelowVwap && $nowAboveVwap) {
                    $longScore += 20; // Bounce but need SuperTrend confirmation
                }
                
                // Price above SuperTrend
                if ($price > $superTrend['value']) {
                    $longScore += 20;
                }
            }
        }

        // SELL conditions with bounce confirmation
        if ($trend === 'DOWN') {
            // Check HTF filter
            if ($htfTrend === 'UP') {
                // HTF trend is UP, skip SELL signals
                $shortScore = 0;
            } else {
                $shortScore += 30; // Base score for DOWN trend
                
                // 🔒 2. SELL: Price was above VWAP, now closed below VWAP (bounce)
                $wasAboveVwap = $previousClose > $vwap;
                $nowBelowVwap = $currentClose < $vwap;
                $highBelowSuperTrend = $currentHigh < $superTrend['value'];
                
                if ($wasAboveVwap && $nowBelowVwap && $highBelowSuperTrend) {
                    $shortScore += 50; // Strong bounce confirmation
                } elseif ($nowBelowVwap && $highBelowSuperTrend) {
                    $shortScore += 30; // Price below VWAP and SuperTrend
                } elseif ($wasAboveVwap && $nowBelowVwap) {
                    $shortScore += 20; // Bounce but need SuperTrend confirmation
                }
                
                // Price below SuperTrend
                if ($price < $superTrend['value']) {
                    $shortScore += 20;
                }
            }
        }

        // 🔒 3. УЖЕСТОЧЕННОЕ SCORING: Minimum total score 85, minimum difference 25%
        $totalScore = $longScore + $shortScore;
        
        if ($totalScore < 85) {
            // Total score too low - return HOLD
            return [
                'price' => $price,
                'supertrend_value' => $superTrend['value'],
                'supertrend_trend' => $trend,
                'vwap' => $vwap,
                'price_to_vwap_percent' => $priceToVwapPercent,
                'atr' => $atr,
                'adx' => $adx['adx'],
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Низкий общий балл: {$totalScore} (требуется >= 85)",
                'strength' => 'WEAK'
            ];
        }

        $longProb = $totalScore > 0 ? round(($longScore / $totalScore) * 100) : 50;
        $shortProb = $totalScore > 0 ? round(($shortScore / $totalScore) * 100) : 50;
        $probDifference = abs($longProb - $shortProb);

        // 🔒 3. Minimum difference 25%
        if ($probDifference < 25) {
            return [
                'price' => $price,
                'supertrend_value' => $superTrend['value'],
                'supertrend_trend' => $trend,
                'vwap' => $vwap,
                'price_to_vwap_percent' => $priceToVwapPercent,
                'atr' => $atr,
                'adx' => $adx['adx'],
                'signal' => 'HOLD',
                'long_probability' => $longProb,
                'short_probability' => $shortProb,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Недостаточная разница вероятностей: {$probDifference}% (требуется >= 25%)",
                'strength' => 'WEAK'
            ];
        }

        // Determine signal
        $signal = $longProb > $shortProb ? 'BUY' : 'SELL';
        
        // 🔒 3. Strength determination: WEAK removed, MEDIUM only on 1h+, STRONG on 15m
        $strength = 'WEAK';
        if ($probDifference > 20) {
            if ($interval === '15m' || $interval === '5m') {
                $strength = 'STRONG'; // Only STRONG on lower timeframes
            } elseif ($interval === '1h' || $interval === '4h') {
                if ($probDifference > 30) {
                    $strength = 'STRONG';
                } else {
                    $strength = 'MEDIUM'; // MEDIUM only on higher timeframes
                }
            }
        }

        // 🔒 3. Remove WEAK signals completely
        if ($strength === 'WEAK') {
            return [
                'price' => $price,
                'supertrend_value' => $superTrend['value'],
                'supertrend_trend' => $trend,
                'vwap' => $vwap,
                'price_to_vwap_percent' => $priceToVwapPercent,
                'atr' => $atr,
                'adx' => $adx['adx'],
                'signal' => 'HOLD',
                'long_probability' => $longProb,
                'short_probability' => $shortProb,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Сигнал слишком слабый (WEAK) - отфильтрован",
                'strength' => 'WEAK'
            ];
        }

        // 🔒 5. IMPROVED SL/TP: 1.8:3.0 ratio instead of 2.0:2.0
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 1.8;
        $takeProfitMultiplier = $params['take_profit_multiplier'] ?? 3.0;

        if ($signal === 'BUY') {
            $stopLoss = $price - ($atr * $stopLossMultiplier);
            $takeProfit = $price + ($atr * $takeProfitMultiplier);
        } elseif ($signal === 'SELL') {
            $stopLoss = $price + ($atr * $stopLossMultiplier);
            $takeProfit = $price - ($atr * $takeProfitMultiplier);
        } else {
            $stopLoss = $price;
            $takeProfit = $price;
        }

        // Determine precision for display only (not for database)
        $precision = $this->getPricePrecision($price);

        // Generate reason
        $reasons = [];
        $reasons[] = "SuperTrend: {$trend} тренд";
        if ($htfTrend) {
            $reasons[] = "HTF(1h) тренд: {$htfTrend}";
        }
        $reasons[] = "ADX: " . number_format($adx['adx'], 2) . " (импульс ✅)";
        $reasons[] = "VWAP: " . number_format($vwap, $precision);
        $reasons[] = "Цена " . ($priceToVwapPercent >= 0 ? 'выше' : 'ниже') . " VWAP на " . number_format(abs($priceToVwapPercent), 2) . "%";
        if ($signal !== 'HOLD') {
            $reasons[] = "Цена " . ($signal === 'BUY' ? 'выше' : 'ниже') . " SuperTrend";
            $reasons[] = "Отбой от VWAP подтвержден";
        }

        // Return exact values (no rounding) - database supports decimal(20, 10)
        return [
            'price' => $price,
            'supertrend_value' => $superTrend['value'],
            'supertrend_trend' => $trend,
            'vwap' => $vwap,
            'price_to_vwap_percent' => $priceToVwapPercent,
            'atr' => $atr,
            'adx' => $adx['adx'],
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'reason' => implode('. ', $reasons),
            'strength' => $strength
        ];
    }

    /**
     * Determine appropriate decimal precision based on price
     * For small prices (like 0.0100781), we need more precision
     */
    private function getPricePrecision(float $price): int
    {
        if ($price >= 1) {
            return 2; // For prices >= 1, use 2 decimals
        } elseif ($price >= 0.1) {
            return 4; // For prices >= 0.1, use 4 decimals
        } elseif ($price >= 0.01) {
            return 6; // For prices >= 0.01, use 6 decimals
        } else {
            return 8; // For prices < 0.01, use 8 decimals
        }
    }

    /**
     * Calculate Ichimoku Cloud components
     */
    public function calculateIchimoku(array $highs, array $lows, array $closes, int $tenkanPeriod = 9, int $kijunPeriod = 26, int $senkouBPeriod = 52): array
    {
        $count = count($closes);
        
        if ($count < $senkouBPeriod) {
            $price = end($closes);
            return [
                'tenkan' => $price,
                'kijun' => $price,
                'senkou_a' => $price,
                'senkou_b' => $price,
                'chikou' => $price,
                'cloud_top' => $price,
                'cloud_bottom' => $price,
                'price_above_cloud' => true
            ];
        }

        // Tenkan-sen: (highest high + lowest low) / 2 for last 9 periods
        $tenkanHighs = array_slice($highs, -$tenkanPeriod);
        $tenkanLows = array_slice($lows, -$tenkanPeriod);
        $tenkan = (max($tenkanHighs) + min($tenkanLows)) / 2;

        // Kijun-sen: (highest high + lowest low) / 2 for last 26 periods
        $kijunHighs = array_slice($highs, -$kijunPeriod);
        $kijunLows = array_slice($lows, -$kijunPeriod);
        $kijun = (max($kijunHighs) + min($kijunLows)) / 2;

        // Senkou Span A: (Tenkan + Kijun) / 2, shifted 26 periods forward
        $senkouA = ($tenkan + $kijun) / 2;

        // Senkou Span B: (highest high + lowest low) / 2 for last 52 periods, shifted 26 periods forward
        $senkouBHighs = array_slice($highs, -$senkouBPeriod);
        $senkouBLows = array_slice($lows, -$senkouBPeriod);
        $senkouB = (max($senkouBHighs) + min($senkouBLows)) / 2;

        // Cloud boundaries (current position, not shifted)
        $cloudTop = max($senkouA, $senkouB);
        $cloudBottom = min($senkouA, $senkouB);

        // Chikou Span: current close, shifted 26 periods back (for reference)
        $chikou = $count >= 26 ? $closes[$count - 26] : end($closes);

        // Determine if price is above cloud
        $currentPrice = end($closes);
        $priceAboveCloud = $currentPrice > $cloudTop;

        return [
            'tenkan' => $tenkan,
            'kijun' => $kijun,
            'senkou_a' => $senkouA,
            'senkou_b' => $senkouB,
            'chikou' => $chikou,
            'cloud_top' => $cloudTop,
            'cloud_bottom' => $cloudBottom,
            'price_above_cloud' => $priceAboveCloud
        ];
    }

    /**
     * Analyze symbol with Ichimoku+RSI strategy
     * Новая рабочая логика (без баллов, только условия):
     * - Четкая логика входа без конфликтов
     * - Chikou Span проверка
     * - ATR для расстояния от облака
     * - Фильтр плоского облака
     * - SL/TP на основе уровней Ichimoku
     */
    public function analyzeIchimokuRsi(string $symbol, array $params): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $tenkanPeriod = $params['tenkan_period'] ?? 9;
        $kijunPeriod = $params['kijun_period'] ?? 26;
        $senkouBPeriod = $params['senkou_b_period'] ?? 52;
        $interval = $params['interval'] ?? '1h';
        $limit = $params['limit'] ?? 100;

        $klines = $this->fetchKlines($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < $senkouBPeriod + 26) {
            throw new \Exception("Недостаточно данных для анализа (требуется минимум " . ($senkouBPeriod + 26) . " свечей)");
        }

        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        $price = end($closes);
        $ichimoku = $this->calculateIchimoku($highs, $lows, $closes, $tenkanPeriod, $kijunPeriod, $senkouBPeriod);
        $rsi = $this->calculateRSI($closes, $rsiPeriod);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);

        $tenkan = $ichimoku['tenkan'];
        $kijun = $ichimoku['kijun'];
        $senkouA = $ichimoku['senkou_a'];
        $senkouB = $ichimoku['senkou_b'];
        $chikou = $ichimoku['chikou'];
        $cloudTop = $ichimoku['cloud_top'];
        $cloudBottom = $ichimoku['cloud_bottom'];
        $priceAboveCloud = $ichimoku['price_above_cloud'];

        $count = count($closes);
        $price26PeriodsAgo = $count >= 26 ? $closes[$count - 26] : $price;

        $cloudThickness = abs($senkouA - $senkouB);
        $minCloudThickness = $atr * 0.5;

        if ($cloudThickness < $minCloudThickness) {
            return [
                'price' => $price,
                'rsi' => $rsi,
                'tenkan' => $tenkan,
                'kijun' => $kijun,
                'senkou_a' => $senkouA,
                'senkou_b' => $senkouB,
                'chikou' => $chikou,
                'cloud_top' => $cloudTop,
                'cloud_bottom' => $cloudBottom,
                'price_above_cloud' => $priceAboveCloud,
                'atr' => $atr,
                'signal' => 'HOLD',
                'long_probability' => 50,
                'short_probability' => 50,
                'stop_loss' => $price,
                'take_profit' => $price,
                'reason' => "Плоское облако: |Senkou A - Senkou B| = " . number_format($cloudThickness, 4) . " < 0.5×ATR = " . number_format($minCloudThickness, 4),
                'strength' => 'WEAK'
            ];
        }

        $distanceFromCloud = $priceAboveCloud ? ($price - $cloudTop) : ($cloudBottom - $price);
        $maxDistance = $atr * 1.0;
        $tooFarFromCloud = $distanceFromCloud > ($atr * 1.5);

        $signal = 'HOLD';
        $strength = 'WEAK';
        $reasons = [];

        if ($priceAboveCloud) {
            $tenkanAboveKijun = $tenkan > $kijun;
            $chikouAbovePrice26 = $chikou > $price26PeriodsAgo;
            $rsiInBuyZone = $rsi >= 45 && $rsi <= 65;
            $rsiTooHigh = $rsi > 70;
            $priceNearCloud = $distanceFromCloud <= $maxDistance;

            if ($tenkanAboveKijun && $chikouAbovePrice26 && $rsiInBuyZone && $priceNearCloud && !$rsiTooHigh && !$tooFarFromCloud) {
                $signal = 'BUY';
                $strength = 'STRONG';
                $reasons[] = "Цена выше облака";
                $reasons[] = "Tenkan > Kijun";
                $reasons[] = "Chikou > цена(-26)";
                $reasons[] = "RSI в зоне 45-65";
                $reasons[] = "Расстояние от облака ≤ 1×ATR";
            } elseif ($rsiTooHigh) {
                $reasons[] = "RSI слишком высокий ({$rsi} > 70)";
            } elseif ($tooFarFromCloud) {
                $reasons[] = "Цена слишком далеко от облака (> 1.5×ATR)";
            } elseif (!$tenkanAboveKijun) {
                $reasons[] = "Tenkan ≤ Kijun";
            } elseif (!$chikouAbovePrice26) {
                $reasons[] = "Chikou ≤ цена(-26)";
            } elseif (!$rsiInBuyZone) {
                $reasons[] = "RSI вне зоны ({$rsi}, требуется 45-65)";
            } elseif (!$priceNearCloud) {
                $reasons[] = "Расстояние от облака > 1×ATR";
            }
        } else {
            $tenkanBelowKijun = $tenkan < $kijun;
            $chikouBelowPrice26 = $chikou < $price26PeriodsAgo;
            $rsiInSellZone = $rsi >= 35 && $rsi <= 55;
            $rsiTooLow = $rsi < 30;
            $priceNearCloud = $distanceFromCloud <= $maxDistance;

            if ($tenkanBelowKijun && $chikouBelowPrice26 && $rsiInSellZone && $priceNearCloud && !$rsiTooLow && !$tooFarFromCloud) {
                $signal = 'SELL';
                $strength = 'STRONG';
                $reasons[] = "Цена ниже облака";
                $reasons[] = "Tenkan < Kijun";
                $reasons[] = "Chikou < цена(-26)";
                $reasons[] = "RSI в зоне 35-55";
                $reasons[] = "Расстояние от облака ≤ 1×ATR";
            } elseif ($rsiTooLow) {
                $reasons[] = "RSI слишком низкий ({$rsi} < 30)";
            } elseif ($tooFarFromCloud) {
                $reasons[] = "Цена слишком далеко от облака (> 1.5×ATR)";
            } elseif (!$tenkanBelowKijun) {
                $reasons[] = "Tenkan ≥ Kijun";
            } elseif (!$chikouBelowPrice26) {
                $reasons[] = "Chikou ≥ цена(-26)";
            } elseif (!$rsiInSellZone) {
                $reasons[] = "RSI вне зоны ({$rsi}, требуется 35-55)";
            } elseif (!$priceNearCloud) {
                $reasons[] = "Расстояние от облака > 1×ATR";
            }
        }

        if ($signal === 'HOLD' && empty($reasons)) {
            $reasons[] = "Не выполнены условия для входа";
        }

        $stopLoss = $price;
        $takeProfit = $price;

        if ($signal === 'BUY') {
            $slBelowKijun = min($kijun, $senkouB);
            $stopLoss = $slBelowKijun;
            $risk = $price - $stopLoss;
            $takeProfit = $price + ($risk * 2.0);
        } elseif ($signal === 'SELL') {
            $slAboveKijun = max($kijun, $senkouB);
            $stopLoss = $slAboveKijun;
            $risk = $stopLoss - $price;
            $takeProfit = $price - ($risk * 2.0);
        }

        return [
            'price' => $price,
            'rsi' => $rsi,
            'tenkan' => $tenkan,
            'kijun' => $kijun,
            'senkou_a' => $senkouA,
            'senkou_b' => $senkouB,
            'chikou' => $chikou,
            'cloud_top' => $cloudTop,
            'cloud_bottom' => $cloudBottom,
            'price_above_cloud' => $priceAboveCloud,
            'atr' => $atr,
            'signal' => $signal,
            'long_probability' => $signal === 'BUY' ? 100 : ($signal === 'SELL' ? 0 : 50),
            'short_probability' => $signal === 'SELL' ? 100 : ($signal === 'BUY' ? 0 : 50),
            'stop_loss' => $stopLoss,
            'take_profit' => $takeProfit,
            'reason' => implode('. ', $reasons),
            'strength' => $strength
        ];
    }

    /**
     * Analyze symbol with Smart Money Concepts (SMC) strategy
     * Based on Order Blocks, Market Structure (BOS/CHOCH), Fair Value Gaps
     * Согласно инструкции: торговля только по тренду, с подтверждением через ликвидность и структуру
     */
    public function analyzeSmartMoneyConcepts(string $symbol, array $params): array
    {
        $interval = $params['interval'] ?? '15m';
        $limit = $params['limit'] ?? 200;
        $htfInterval = $params['htf_interval'] ?? '4h'; // H4 для определения тренда (как в инструкции)
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $orderBlockLookback = $params['order_block_lookback'] ?? 30; // Больше свечей для лучшего поиска OB

        // Fetch klines data
        $klines = $this->fetchKlines($symbol, $interval, $limit);
        $htfKlines = $this->fetchKlines($symbol, $htfInterval, 100);

        if (empty($klines) || count($klines) < 50) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Extract price data
        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);
        $volumes = array_map(fn($k) => (float) $k[5], $klines);
        $opens = array_map(fn($k) => (float) $k[1], $klines);

        // HTF data
        $htfCloses = array_map(fn($k) => (float) $k[4], $htfKlines);
        $htfHighs = array_map(fn($k) => (float) $k[2], $htfKlines);
        $htfLows = array_map(fn($k) => (float) $k[3], $htfKlines);

        // Calculate indicators
        $price = end($closes);
        $rsi = $this->calculateRSI($closes, $rsiPeriod);
        $htfRsi = $this->calculateRSI($htfCloses, $rsiPeriod);
        $ema = $this->calculateEMA($closes, 50);
        $htfEma = $this->calculateEMA($htfCloses, 50);
        $atr = $this->calculateATR($highs, $lows, $closes, 14);

        // 🔥 ШАГ 1: Определяем старший тренд на H4 (как в инструкции)
        // Тренд = главное направление торговли, без тренда не торгуем
        $htfTrend = 'NEUTRAL';
        $htfPrice = end($htfCloses);
        
        // Более строгое определение тренда: цена должна быть четко выше/ниже EMA и RSI должен подтверждать
        if ($htfPrice > $htfEma * 1.002 && $htfRsi > 52) { // 0.2% буфер для фильтрации шума
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

        // 🔥 ШАГ 2: Находим Order Blocks (ключевые зоны)
        $orderBlocks = $this->findOrderBlocks($highs, $lows, $closes, $volumes, $opens, $orderBlockLookback);
        
        // 🔥 ШАГ 3: Определяем Market Structure (BOS/CHOCH)
        $marketStructure = $this->detectMarketStructure($highs, $lows, $closes);

        // 🔥 ШАГ 4: Находим Fair Value Gaps (FVG)
        $fvg = $this->findFairValueGaps($highs, $lows, $closes);
        
        // 🔥 ШАГ 5: Находим зоны ликвидности (Liquidity Areas - места где были стоп-лоссы)
        $liquidityAreas = $this->findLiquidityAreas($highs, $lows, $closes);

        // 🔥 ШАГ 6: Проверяем свечной паттерн для подтверждения входа
        $candleConfirmation = $this->checkCandleConfirmation($opens, $highs, $lows, $closes, $htfTrend);

        // Calculate probabilities - СТРОГИЕ КРИТЕРИИ
        $longScore = 0;
        $shortScore = 0;
        $activeOrderBlock = null;
        $hasLiquidity = false;

        // BUY conditions: Бычий тренд + возврат к Bullish Order Block + ликвидность + подтверждение
        if ($htfTrend === 'BULLISH') {
            // 1. Проверяем возврат к Bullish Order Block (ОБЯЗАТЕЛЬНО)
            foreach ($orderBlocks['bullish'] as $ob) {
                // Проверяем точный возврат к OB (цена в зоне или очень близко)
                $obRange = ($ob['high'] - $ob['low']) * 0.1; // 10% от размера OB как допустимая зона
                if ($price >= ($ob['low'] - $obRange) && $price <= ($ob['high'] + $obRange)) {
                    $longScore += 50; // Критично важно - цена вернулась к OB
                    $activeOrderBlock = $ob;
                    
                    // 2. Проверяем наличие ликвидности НИЖЕ Order Block (ОБЯЗАТЕЛЬНО)
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

            // 3. Market Structure подтверждение (BOS/CHOCH) - ОБЯЗАТЕЛЬНО для сильного сигнала
            if ($marketStructure === 'BULLISH_BOS' || $marketStructure === 'BULLISH_CHOCH') {
                $longScore += 25;
            }

            // 4. Fair Value Gap как дополнительное подтверждение
            foreach ($fvg['bullish'] as $gap) {
                if ($price >= $gap['bottom'] && $price <= $gap['top']) {
                    $longScore += 15; // Дополнительный плюс за FVG
                    break;
                }
            }

            // 5. Свечное подтверждение (бычья свеча после возврата к OB)
            if ($candleConfirmation['bullish']) {
                $longScore += 20;
            }

            // 6. RSI фильтр (не перекупленность)
            if ($rsi >= 25 && $rsi <= 45) {
                $longScore += 10;
            }
        }

        // SELL conditions: Медвежий тренд + возврат к Bearish Order Block + ликвидность + подтверждение
        if ($htfTrend === 'BEARISH') {
            // 1. Проверяем возврат к Bearish Order Block (ОБЯЗАТЕЛЬНО)
            foreach ($orderBlocks['bearish'] as $ob) {
                // Проверяем точный возврат к OB
                $obRange = ($ob['high'] - $ob['low']) * 0.1;
                if ($price >= ($ob['low'] - $obRange) && $price <= ($ob['high'] + $obRange)) {
                    $shortScore += 50; // Критично важно
                    $activeOrderBlock = $ob;
                    
                    // 2. Проверяем наличие ликвидности ВЫШЕ Order Block (ОБЯЗАТЕЛЬНО)
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

            // 3. Market Structure подтверждение
            if ($marketStructure === 'BEARISH_BOS' || $marketStructure === 'BEARISH_CHOCH') {
                $shortScore += 25;
            }

            // 4. Fair Value Gap
            foreach ($fvg['bearish'] as $gap) {
                if ($price >= $gap['bottom'] && $price <= $gap['top']) {
                    $shortScore += 15;
                    break;
                }
            }

            // 5. Свечное подтверждение
            if ($candleConfirmation['bearish']) {
                $shortScore += 20;
            }

            // 6. RSI фильтр
            if ($rsi >= 55 && $rsi <= 75) {
                $shortScore += 10;
            }
        }
        
        // 🔥 КРИТИЧНО: Если нет возврата к Order Block или нет ликвидности - не торгуем
        // ДОПОЛНИТЕЛЬНО: Требуем подтверждение через Market Structure для более строгой фильтрации
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
        
        // 🔥 ДОПОЛНИТЕЛЬНЫЙ ФИЛЬТР: Для STRONG требуется обязательное подтверждение через BOS/CHOCH
        // Если нет подтверждения структуры, снижаем баллы
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

        // Normalize to percentages
        $totalScore = max(1, $longScore + $shortScore);
        $longProb = round(($longScore / $totalScore) * 100);
        $shortProb = round(($shortScore / $totalScore) * 100);

        // Determine signal - только если есть достаточный перевес (ПОВЫШЕННЫЕ ТРЕБОВАНИЯ)
        $signal = 'HOLD';
        if ($longScore > $shortScore && $longScore >= 100) { // Минимум 100 баллов для BUY (было 80)
            $signal = 'BUY';
        } elseif ($shortScore > $longScore && $shortScore >= 100) { // Минимум 100 баллов для SELL (было 80)
            $signal = 'SELL';
        }

        // 🔥 УЖЕСТОЧЕННЫЕ КРИТЕРИИ: Только STRONG сигналы (ПОВЫШЕННЫЕ ПОРОГИ)
        $strength = 'WEAK';
        if ($signal !== 'HOLD') {
            $winningScore = $signal === 'BUY' ? $longScore : $shortScore;
            if ($winningScore >= 140) { // STRONG: минимум 140 баллов (было 120)
                $strength = 'STRONG';
            } elseif ($winningScore >= 120) { // MEDIUM: минимум 120 баллов (было 100)
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

        // 🔥 ШАГ 7: Расчет SL/TP согласно инструкции
        // SL за границу OB или Liquidity Area
        // TP на ближайших зонах ликвидности или ключевых уровнях структуры
        $orderBlockHigh = $activeOrderBlock['high'];
        $orderBlockLow = $activeOrderBlock['low'];
        $nearestLiquidity = null;

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
            $takeProfit = $price + ($atr * 3.5); // Базовый TP
            
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
            $takeProfit = $price - ($atr * 3.5); // Базовый TP
            
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

        // Generate reason
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

    /**
     * Находит Order Blocks (последние сильные свечи перед разворотом)
     * Улучшенная версия: более строгие критерии для определения OB
     */
    private function findOrderBlocks(array $highs, array $lows, array $closes, array $volumes, array $opens, int $lookback): array
    {
        $bullishOB = [];
        $bearishOB = [];
        $count = count($closes);

        if ($count < $lookback + 5) {
            return ['bullish' => [], 'bearish' => []];
        }

        // Ищем последние сильные движения перед разворотом
        // Ужесточенные критерии: больший объем, четкий разворот
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

            // Bullish Order Block: сильная бычья свеча (большое тело, высокий объем) перед разворотом вниз
            // ПОВЫШЕННЫЕ ТРЕБОВАНИЯ: тело 70%+ (было 60%), объем 2.0x+ (было 1.8x)
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
            // ПОВЫШЕННЫЕ ТРЕБОВАНИЯ: тело 70%+ (было 60%), объем 2.0x+ (было 1.8x)
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

        // Берем только последние 5 Order Blocks (больше для выбора лучшего)
        return [
            'bullish' => array_slice($bullishOB, -5),
            'bearish' => array_slice($bearishOB, -5)
        ];
    }
    
    /**
     * Находит зоны ликвидности (Liquidity Areas) - места где были стоп-лоссы
     * Ликвидность = локальные максимумы/минимумы, которые могут быть пробойными
     */
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
            'above' => array_slice($liquidityAbove, 0, 5),
            'below' => array_slice($liquidityBelow, 0, 5)
        ];
    }
    
    /**
     * Проверяет свечное подтверждение для входа
     * Бычий тренд: бычья свеча после возврата к OB
     * Медвежий тренд: медвежья свеча после возврата к OB
     */
    private function checkCandleConfirmation(array $opens, array $highs, array $lows, array $closes, string $trend): array
    {
        $count = count($closes);
        if ($count < 2) {
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

    /**
     * Определяет Market Structure (BOS/CHOCH)
     * Улучшенная версия: проверяет BOS/CHOCH не только на текущей свече, но и в недавнем прошлом (последние 15 свечей)
     * Это важно, так как структура могла измениться недавно, и это все равно подтверждает тренд
     */
    private function detectMarketStructure(array $highs, array $lows, array $closes): ?string
    {
        $count = count($closes);
        if ($count < 20) {
            return null;
        }

        $currentPrice = end($closes);
        $lookbackPeriod = 15; // Проверяем последние 15 свечей для BOS/CHOCH
        
        // Проверяем BOS/CHOCH в последних 15 свечах (не только на текущей)
        for ($i = max(0, $count - $lookbackPeriod); $i < $count; $i++) {
            // Находим максимумы и минимумы ДО этой свечи (для определения структуры)
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
            // Проверяем, была ли свеча на индексе $i пробоем структуры
            if ($priceAtI > $highestHigh && $priceBeforeI <= $highestHigh) {
                // Если это было недавно (в последних 10 свечах), считаем актуальным
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
            // Проверяем изменение тренда на свече $i
            if ($i >= 2) {
                $trend = $closes[$i] > $closes[$i - 1] ? 'BULLISH' : 'BEARISH';
                $prevTrend = $closes[$i - 1] > $closes[$i - 2] ? 'BULLISH' : 'BEARISH';
                
                if ($trend !== $prevTrend) {
                    // Если это было недавно (в последних 10 свечах), считаем актуальным
                    if ($count - $i <= 10) {
                        return $trend === 'BULLISH' ? 'BULLISH_CHOCH' : 'BEARISH_CHOCH';
                    }
                }
            }
        }
        
        // Также проверяем текущую свечу (как было раньше)
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

    /**
     * Находит Fair Value Gaps (FVG) - ценовые пробелы
     */
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
            // Bullish FVG: gap вверх (low текущей свечи > high свечи 2 периода назад)
            if ($lows[$i] > $highs[$i + 2]) {
                $bullishFVG[] = [
                    'top' => $lows[$i],
                    'bottom' => $highs[$i + 2],
                    'index' => $i
                ];
            }

            // Bearish FVG: gap вниз (high текущей свечи < low свечи 2 периода назад)
            if ($highs[$i] < $lows[$i + 2]) {
                $bearishFVG[] = [
                    'top' => $lows[$i + 2],
                    'bottom' => $highs[$i],
                    'index' => $i
                ];
            }
        }

        return [
            'bullish' => array_slice($bullishFVG, -3),
            'bearish' => array_slice($bearishFVG, -3)
        ];
    }
}








