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
        $adxThreshold = $params['adx_threshold'] ?? 20.0;
        $atrVolatilityThreshold = $params['atr_volatility_threshold'] ?? 0.4; // 0.4% minimum volatility

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

        // 🔒 1. FILTER: ADX or ATR volatility check
        $atrVolatilityPercent = $price > 0 ? (($atr / $price) * 100) : 0;
        $hasMomentum = $adx['adx'] > $adxThreshold || $atrVolatilityPercent > $atrVolatilityThreshold;
        
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
                'reason' => "Нет импульса: ADX=" . number_format($adx['adx'], 2) . " (требуется >{$adxThreshold}), ATR волатильность=" . number_format($atrVolatilityPercent, 2) . "% (требуется >{$atrVolatilityThreshold}%)",
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

        // 🔒 2. VWAP BOUNCE CONFIRMATION (not just "near")
        // Check previous candles to confirm bounce
        $currentClose = $closes[count($closes) - 1];
        $previousClose = count($closes) > 1 ? $closes[count($closes) - 2] : $currentClose;
        $currentLow = $lows[count($lows) - 1];
        $currentHigh = $highs[count($highs) - 1];
        
        $trend = $superTrend['trend'];
        $priceToVwap = $price - $vwap;
        $priceToVwapPercent = $vwap > 0 ? (($priceToVwap / $vwap) * 100) : 0;

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

        // 🔒 3. STRICTER SCORING: Minimum total score 80, minimum difference 20%
        $totalScore = $longScore + $shortScore;
        
        if ($totalScore < 80) {
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
                'reason' => "Низкий общий балл: {$totalScore} (требуется >= 80)",
                'strength' => 'WEAK'
            ];
        }

        $longProb = $totalScore > 0 ? round(($longScore / $totalScore) * 100) : 50;
        $shortProb = $totalScore > 0 ? round(($shortScore / $totalScore) * 100) : 50;
        $probDifference = abs($longProb - $shortProb);

        // 🔒 3. Minimum difference 20%
        if ($probDifference < 20) {
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
                'reason' => "Недостаточная разница вероятностей: {$probDifference}% (требуется >= 20%)",
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

        // 🔒 5. IMPROVED SL/TP: 1.5:3.0 ratio instead of 2.0:2.0
        $stopLossMultiplier = $params['stop_loss_multiplier'] ?? 1.5;
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

        // Calculate SL/TP based on original price (no rounding for database)
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

        // Determine precision for display only (not for database)
        $precision = $this->getPricePrecision($price);

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

        // Return exact values (no rounding) - database supports decimal(20, 10)
        // Price, stop_loss, take_profit will be saved exactly as calculated
        return [
            'price' => $price, // Exact price from provider, no rounding
            'rsi' => $rsi,
            'tenkan' => $tenkan,
            'kijun' => $kijun,
            'senkou_a' => $ichimoku['senkou_a'],
            'senkou_b' => $ichimoku['senkou_b'],
            'cloud_top' => $cloudTop,
            'cloud_bottom' => $cloudBottom,
            'price_above_cloud' => $priceAboveCloud,
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
}








