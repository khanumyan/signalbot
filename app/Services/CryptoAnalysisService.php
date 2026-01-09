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
}








