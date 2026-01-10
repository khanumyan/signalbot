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
    public function analyzeMTF(string $symbol, array $params): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $emaPeriod = $params['ema_period'] ?? 50;
        $rsiBuyThreshold = $params['rsi_buy_threshold'] ?? 30;
        $rsiSellThreshold = $params['rsi_sell_threshold'] ?? 70;

        // Fetch data for different timeframes
        $klines15m = $this->fetchKlines($symbol, '15m', 100);
        $klines1h = $this->fetchKlines($symbol, '1h', 100);
        $klines5m = $this->fetchKlines($symbol, '5m', 100);

        if (empty($klines15m) || empty($klines1h) || empty($klines5m)) {
            throw new \Exception("Недостаточно данных для анализа");
        }

        // Calculate indicators
        $closes15m = array_map(fn($k) => (float) $k[4], $klines15m);
        $closes1h = array_map(fn($k) => (float) $k[4], $klines1h);
        $closes5m = array_map(fn($k) => (float) $k[4], $klines5m);
        $highs15m = array_map(fn($k) => (float) $k[2], $klines15m);
        $lows15m = array_map(fn($k) => (float) $k[3], $klines15m);

        $rsi15m = $this->calculateRSI($closes15m, $rsiPeriod);
        $rsi1h = $this->calculateRSI($closes1h, $rsiPeriod);
        $rsi5m = $this->calculateRSI($closes5m, $rsiPeriod);
        $ema15m = $this->calculateEMA($closes15m, $emaPeriod);
        $ema1h = $this->calculateEMA($closes1h, $emaPeriod);
        $bb15m = $this->calculateBollingerBands($closes15m, $params['bb_period'] ?? 20, $params['bb_std_dev'] ?? 2);
        $atr = $this->calculateATR($highs15m, $lows15m, $closes15m, $params['atr_period'] ?? 14);

        $price = end($closes15m);

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        // RSI signals
        if ($rsi15m <= $rsiBuyThreshold) {
            $longScore += 30;
        } elseif ($rsi15m >= $rsiSellThreshold) {
            $shortScore += 30;
        }

        // HTF trend
        if ($price > $ema1h && $rsi1h > 50) {
            $longScore += 20;
        } elseif ($price < $ema1h && $rsi1h < 50) {
            $shortScore += 20;
        }

        // LTF confirmation
        if ($rsi5m > 30 && $price > $ema15m) {
            $longScore += 15;
        } elseif ($rsi5m < 70 && $price < $ema15m) {
            $shortScore += 15;
        }

        // Bollinger Bands
        if ($price <= $bb15m['lower']) {
            $longScore += 20;
        } elseif ($price >= $bb15m['upper']) {
            $shortScore += 20;
        }

        // EMA trend
        if ($price > $ema15m) {
            $longScore += 15;
        } else {
            $shortScore += 15;
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

        // Generate reason
        $reasons = [];
        if ($rsi15m <= $rsiBuyThreshold) {
            $reasons[] = "RSI {$rsi15m} показывает перепроданность";
        } elseif ($rsi15m >= $rsiSellThreshold) {
            $reasons[] = "RSI {$rsi15m} показывает перекупленность";
        }
        if ($price > $ema1h) {
            $reasons[] = "Цена выше EMA на старшем таймфрейме (бычий тренд)";
        } else {
            $reasons[] = "Цена ниже EMA на старшем таймфрейме (медвежий тренд)";
        }
        if ($price <= $bb15m['lower']) {
            $reasons[] = "Цена у нижней границы Bollinger Bands";
        } elseif ($price >= $bb15m['upper']) {
            $reasons[] = "Цена у верхней границы Bollinger Bands";
        }

        return [
            'price' => $price,
            'rsi' => round($rsi15m, 2),
            'rsi_htf' => round($rsi1h, 2),
            'rsi_ltf' => round($rsi5m, 2),
            'ema' => round($ema15m, 2),
            'ema_htf' => round($ema1h, 2),
            'bb_upper' => round($bb15m['upper'], 2),
            'bb_lower' => round($bb15m['lower'], 2),
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'reason' => implode('. ', $reasons),
            'strength' => abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK')
        ];
    }

    /**
     * Analyze symbol with EMA+RSI+MACD strategy
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
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 2.0;
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

        return [
            'price' => round($price, 2),
            'rsi' => round($rsi, 2),
            'ema_fast' => round($ema20, 2),
            'ema_slow' => round($ema50, 2),
            'macd' => round($macdLine, 4),
            'macd_signal' => round($macdSignalLine, 4),
            'macd_histogram' => round($macdHist, 4),
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
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

        return [
            'price' => round($price, 2),
            'rsi' => round($rsi, 2),
            'bb_upper' => round($bb['upper'], 2),
            'bb_middle' => round($bb['middle'], 2),
            'bb_lower' => round($bb['lower'], 2),
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
            'reason' => implode('. ', $reasons),
            'strength' => abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK')
        ];
    }

    /**
     * Calculate Stochastic Oscillator
     */
    public function calculateStochastic(array $highs, array $lows, array $closes, int $kPeriod = 14, int $kSmooth = 3, int $dPeriod = 3): array
    {
        if (count($closes) < $kPeriod) {
            return ['k' => 50.0, 'd' => 50.0];
        }

        // Calculate %K
        $recentHighs = array_slice($highs, -$kPeriod);
        $recentLows = array_slice($lows, -$kPeriod);
        $currentClose = end($closes);
        
        $highest = max($recentHighs);
        $lowest = min($recentLows);
        
        $k = 50.0;
        if ($highest != $lowest) {
            $k = (($currentClose - $lowest) / ($highest - $lowest)) * 100;
        }

        // For simplicity, using K as D (in real implementation would smooth K and then calculate D)
        $d = $k;

        return ['k' => $k, 'd' => $d];
    }

    /**
     * Analyze symbol with EMA+Stochastic strategy
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
        $stochastic = $this->calculateStochastic($highs, $lows, $closes, $stochKPeriod, $stochKSmooth, $stochDPeriod);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);

        $k = $stochastic['k'];
        $d = $stochastic['d'];

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        // BUY conditions: EMA9 > EMA21 + Stochastic exits from oversold
        if ($ema9 > $ema21 && $k > 20 && $k < 50 && $k > $d) {
            $longScore += 40;
            if ($k > $d && ($k - $d) > 5) {
                $longScore += 30; // Strong momentum
            } elseif ($k > $d) {
                $longScore += 15; // Medium momentum
            }
        } elseif ($ema9 > $ema21) {
            $longScore += 20; // Trend alignment
        }

        // SELL conditions: EMA9 < EMA21 + Stochastic exits from overbought
        if ($ema9 < $ema21 && $k < 80 && $k > 50 && $k < $d) {
            $shortScore += 40;
            if ($k < $d && ($d - $k) > 5) {
                $shortScore += 30; // Strong momentum
            } elseif ($k < $d) {
                $shortScore += 15; // Medium momentum
            }
        } elseif ($ema9 < $ema21) {
            $shortScore += 20; // Trend alignment
        }

        // Stochastic signals
        if ($k <= 20 && $k > $d) {
            $longScore += 20; // Oversold with bullish cross
        } elseif ($k >= 80 && $k < $d) {
            $shortScore += 20; // Overbought with bearish cross
        } elseif ($k > 20 && $k < 50 && $k > $d) {
            $longScore += 10; // Bullish momentum
        } elseif ($k > 50 && $k < 80 && $k < $d) {
            $shortScore += 10; // Bearish momentum
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

        // Calculate SL/TP (scalping strategy - tighter stops)
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 1.2;
        $takeProfitMultiplier = $params['take_profit_multiplier'] ?? 1.8;

        // Determine strength for multiplier adjustment
        $strength = abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK');
        $tpMultiplier = match($strength) {
            'STRONG' => 2.4,
            'MEDIUM' => 1.8,
            default => 1.2
        };

        if ($signal === 'BUY') {
            $stopLoss = $price - ($atr * $stopLossMultiplier);
            $takeProfit = $price + ($atr * $tpMultiplier);
        } elseif ($signal === 'SELL') {
            $stopLoss = $price + ($atr * $stopLossMultiplier);
            $takeProfit = $price - ($atr * $tpMultiplier);
        } else {
            $stopLoss = $price;
            $takeProfit = $price;
        }

        // Generate reason
        $emaTrend = $ema9 > $ema21 ? 'Bullish' : 'Bearish';
        $stochCross = $k > $d ? 'K>D' : 'K<D';
        $reasons = [];
        $reasons[] = "EMA{$emaFast} vs EMA{$emaSlow}: {$emaTrend} trend";
        $reasons[] = "Stochastic {$stochCross} | K: " . number_format($k, 1) . " | D: " . number_format($d, 1);
        if ($signal !== 'HOLD') {
            $reasons[] = "Price " . ($signal === 'BUY' ? 'above' : 'below') . " EMA{$emaFast}";
        }

        return [
            'price' => round($price, 2),
            'rsi' => 50.0, // Not used in this strategy
            'ema_fast' => round($ema9, 2),
            'ema_slow' => round($ema21, 2),
            'stochastic_k' => round($k, 2),
            'stochastic_d' => round($d, 2),
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
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
     * Analyze symbol with SuperTrend+VWAP strategy
     */
    public function analyzeSuperTrendVwap(string $symbol, array $params): array
    {
        $supertrendPeriod = $params['supertrend_period'] ?? 10;
        $supertrendMultiplier = $params['supertrend_multiplier'] ?? 3.0;
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
        $volumes = array_map(fn($k) => (float) $k[5], $klines);

        // Calculate indicators
        $price = end($closes);
        $superTrend = $this->calculateSuperTrend($highs, $lows, $closes, $supertrendPeriod, $supertrendMultiplier);
        $vwap = $this->calculateVWAP($highs, $lows, $closes, $volumes);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        $trend = $superTrend['trend'];
        $priceToVwap = $price - $vwap;
        $priceToVwapPercent = $vwap > 0 ? (($priceToVwap / $vwap) * 100) : 0;

        // BUY conditions: SuperTrend UP + price returns to VWAP from above
        if ($trend === 'UP') {
            $longScore += 30;
            
            // Price returning to VWAP from above (mean reversion)
            if ($priceToVwapPercent > -1 && $priceToVwapPercent < 2) {
                $longScore += 40; // Price near or slightly above VWAP
            } elseif ($priceToVwapPercent >= 2 && $priceToVwapPercent < 5) {
                $longScore += 20; // Price above VWAP but not too far
            }
            
            // Price above SuperTrend
            if ($price > $superTrend['value']) {
                $longScore += 20;
            }
        } elseif ($trend === 'DOWN') {
            $shortScore += 10; // Weak bearish trend
        }

        // SELL conditions: SuperTrend DOWN + price returns to VWAP from below
        if ($trend === 'DOWN') {
            $shortScore += 30;
            
            // Price returning to VWAP from below (mean reversion)
            if ($priceToVwapPercent < 1 && $priceToVwapPercent > -2) {
                $shortScore += 40; // Price near or slightly below VWAP
            } elseif ($priceToVwapPercent <= -2 && $priceToVwapPercent > -5) {
                $shortScore += 20; // Price below VWAP but not too far
            }
            
            // Price below SuperTrend
            if ($price < $superTrend['value']) {
                $shortScore += 20;
            }
        } elseif ($trend === 'UP') {
            $longScore += 10; // Weak bullish trend
        }

        // VWAP distance signals
        if (abs($priceToVwapPercent) < 0.5) {
            // Price very close to VWAP - neutral
            if ($trend === 'UP') {
                $longScore += 10;
            } else {
                $shortScore += 10;
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

        // Calculate SL/TP
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 2.0;
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
        $reasons = [];
        $reasons[] = "SuperTrend: {$trend} тренд";
        $reasons[] = "VWAP: " . number_format($vwap, 2);
        $reasons[] = "Цена " . ($priceToVwapPercent >= 0 ? 'выше' : 'ниже') . " VWAP на " . number_format(abs($priceToVwapPercent), 2) . "%";
        if ($signal !== 'HOLD') {
            $reasons[] = "Цена " . ($signal === 'BUY' ? 'выше' : 'ниже') . " SuperTrend";
        }

        return [
            'price' => round($price, 2),
            'supertrend_value' => round($superTrend['value'], 2),
            'supertrend_trend' => $trend,
            'vwap' => round($vwap, 2),
            'price_to_vwap_percent' => round($priceToVwapPercent, 2),
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
            'reason' => implode('. ', $reasons),
            'strength' => abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK')
        ];
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
     */
    public function analyzeIchimokuRsi(string $symbol, array $params): array
    {
        $rsiPeriod = $params['rsi_period'] ?? 14;
        $rsiBuyMin = $params['rsi_buy_min'] ?? 40;
        $rsiBuyMax = $params['rsi_buy_max'] ?? 70;
        $rsiSellMin = $params['rsi_sell_min'] ?? 30;
        $rsiSellMax = $params['rsi_sell_max'] ?? 60;
        $tenkanPeriod = $params['tenkan_period'] ?? 9;
        $kijunPeriod = $params['kijun_period'] ?? 26;
        $senkouBPeriod = $params['senkou_b_period'] ?? 52;
        $interval = $params['interval'] ?? '1h';
        $limit = $params['limit'] ?? 100;

        // Fetch klines data
        $klines = $this->fetchKlines($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < $senkouBPeriod) {
            throw new \Exception("Недостаточно данных для анализа (требуется минимум {$senkouBPeriod} свечей)");
        }

        // Extract price data
        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        // Calculate indicators
        $price = end($closes);
        $ichimoku = $this->calculateIchimoku($highs, $lows, $closes, $tenkanPeriod, $kijunPeriod, $senkouBPeriod);
        $rsi = $this->calculateRSI($closes, $rsiPeriod);
        $atr = $this->calculateATR($highs, $lows, $closes, $params['atr_period'] ?? 14);

        // Calculate probabilities
        $longScore = 0;
        $shortScore = 0;

        $tenkan = $ichimoku['tenkan'];
        $kijun = $ichimoku['kijun'];
        $cloudTop = $ichimoku['cloud_top'];
        $cloudBottom = $ichimoku['cloud_bottom'];
        $priceAboveCloud = $ichimoku['price_above_cloud'];

        // BUY conditions: Price above cloud + Tenkan > Kijun + RSI 40-70
        if ($priceAboveCloud) {
            $longScore += 30;
            
            if ($tenkan > $kijun) {
                $longScore += 25; // Bullish crossover
            }
            
            if ($rsi >= $rsiBuyMin && $rsi <= $rsiBuyMax) {
                $longScore += 30; // RSI in buy zone
            } elseif ($rsi > $rsiBuyMax) {
                $longScore += 10; // RSI slightly overbought but still valid
            }
            
            // Distance from cloud
            $cloudDistance = (($price - $cloudTop) / $cloudTop) * 100;
            if ($cloudDistance > 0 && $cloudDistance < 5) {
                $longScore += 15; // Price just above cloud (good entry)
            }
        } else {
            $shortScore += 10; // Price below cloud
        }

        // SELL conditions: Price below cloud + Tenkan < Kijun + RSI 30-60
        if (!$priceAboveCloud) {
            $shortScore += 30;
            
            if ($tenkan < $kijun) {
                $shortScore += 25; // Bearish crossover
            }
            
            if ($rsi >= $rsiSellMin && $rsi <= $rsiSellMax) {
                $shortScore += 30; // RSI in sell zone
            } elseif ($rsi < $rsiSellMin) {
                $shortScore += 10; // RSI slightly oversold but still valid
            }
            
            // Distance from cloud
            $cloudDistance = (($cloudBottom - $price) / $cloudBottom) * 100;
            if ($cloudDistance > 0 && $cloudDistance < 5) {
                $shortScore += 15; // Price just below cloud (good entry)
            }
        } else {
            $longScore += 10; // Price above cloud
        }

        // Tenkan/Kijun relationship
        if ($tenkan > $kijun) {
            $longScore += 10;
        } elseif ($tenkan < $kijun) {
            $shortScore += 10;
        }

        // RSI additional signals
        if ($rsi > 70 && $priceAboveCloud) {
            $longScore -= 10; // Overbought, reduce long score
        } elseif ($rsi < 30 && !$priceAboveCloud) {
            $shortScore -= 10; // Oversold, reduce short score
        }

        // Normalize to percentages
        $totalScore = max(1, $longScore + $shortScore); // Prevent division by zero
        $longProb = round(($longScore / $totalScore) * 100);
        $shortProb = round(($shortScore / $totalScore) * 100);

        // Determine signal
        $signal = $longProb > $shortProb ? 'BUY' : 'SELL';
        if (abs($longProb - $shortProb) < 5) {
            $signal = 'HOLD';
        }

        // Calculate SL/TP
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 2.0;
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
        $reasons = [];
        $reasons[] = "Цена " . ($priceAboveCloud ? 'выше' : 'ниже') . " облака Ишимоку";
        $reasons[] = "Tenkan " . ($tenkan > $kijun ? '>' : '<') . " Kijun";
        $reasons[] = "RSI: {$rsi}";
        if ($signal === 'BUY') {
            $reasons[] = "RSI в зоне покупки ({$rsiBuyMin}-{$rsiBuyMax})";
        } elseif ($signal === 'SELL') {
            $reasons[] = "RSI в зоне продажи ({$rsiSellMin}-{$rsiSellMax})";
        }

        return [
            'price' => round($price, 2),
            'rsi' => round($rsi, 2),
            'tenkan' => round($tenkan, 2),
            'kijun' => round($kijun, 2),
            'senkou_a' => round($ichimoku['senkou_a'], 2),
            'senkou_b' => round($ichimoku['senkou_b'], 2),
            'cloud_top' => round($cloudTop, 2),
            'cloud_bottom' => round($cloudBottom, 2),
            'price_above_cloud' => $priceAboveCloud,
            'atr' => round($atr, 2),
            'signal' => $signal,
            'long_probability' => $longProb,
            'short_probability' => $shortProb,
            'stop_loss' => round($stopLoss, 2),
            'take_profit' => round($takeProfit, 2),
            'reason' => implode('. ', $reasons),
            'strength' => abs($longProb - $shortProb) > 20 ? 'STRONG' : (abs($longProb - $shortProb) > 10 ? 'MEDIUM' : 'WEAK')
        ];
    }
}








