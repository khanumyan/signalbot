<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CryptoController extends Controller
{
    private $cryptoSymbols;
    private $binanceApiUrl = 'https://api.binance.com/api/v3/klines';

    public function __construct()
    {
        $this->cryptoSymbols = config('crypto_symbols');
    }

    /**
     * Get RSI data for all cryptocurrencies
     */
    public function getAllRsi(): JsonResponse
    {
        $rsiData = [];
        $errors = [];

        foreach ($this->cryptoSymbols as $symbol) {
            try {
                $rsi = $this->calculateRsiForSymbol($symbol);
                if ($rsi !== null) {
                    $rsiData[$symbol] = $rsi;
                }
            } catch (\Exception $e) {
                $errors[$symbol] = $e->getMessage();
                Log::error("Error calculating RSI for {$symbol}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'data' => $rsiData,
            'errors' => $errors,
            'total_symbols' => count($this->cryptoSymbols),
            'successful_calculations' => count($rsiData),
            'failed_calculations' => count($errors)
        ]);
    }

    /**
     * Get RSI data for a specific cryptocurrency
     */
    public function getRsiForSymbol(string $symbol): JsonResponse
    {
        try {
            $rsi = $this->calculateRsiForSymbol($symbol);
            
            if ($rsi === null) {
                return response()->json([
                    'success' => false,
                    'message' => "No data available for symbol: {$symbol}"
                ], 404);
            }

            return response()->json([
                'success' => true,
                'symbol' => $symbol,
                'rsi' => $rsi
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error calculating RSI for {$symbol}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate RSI for a specific symbol
     */
    private function calculateRsiForSymbol(string $symbol): ?float
    {
        $klines = $this->fetchKlinesData($symbol);
        
        if (empty($klines) || count($klines) < 15) {
            return null;
        }

        return $this->calculateRsi($klines);
    }

    /**
     * Fetch klines data from Binance API
     */
    private function fetchKlinesData(string $symbol): array
    {
        $response = Http::timeout(10)->get($this->binanceApiUrl, [
            'symbol' => $symbol . 'USDT',
            'interval' => '15m',
            'limit' => 30
        ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to fetch data for {$symbol}: " . $response->body());
        }

        $data = $response->json();
        
        if (!is_array($data)) {
            throw new \Exception("Invalid response format for {$symbol}");
        }

        return $data;
    }

    /**
     * Calculate RSI using the provided algorithm
     */
    private function calculateRsi(array $klines): float
    {
        // Extract close prices
        $closes = array_map(function($kline) {
            return (float) $kline[4]; // Close price is at index 4
        }, $klines);

        $n = 14; // RSI period
        $deltas = [];
        $gains = [];
        $losses = [];

        // Calculate price changes
        for ($i = 1; $i < count($closes); $i++) {
            $deltas[] = $closes[$i] - $closes[$i - 1];
        }

        // Separate gains and losses
        foreach ($deltas as $delta) {
            $gains[] = max(0, $delta);
            $losses[] = max(0, -$delta);
        }

        // Calculate initial average gain and loss
        $avgGain = array_sum(array_slice($gains, 0, $n)) / $n;
        $avgLoss = array_sum(array_slice($losses, 0, $n)) / $n;

        // Calculate RSI using Wilder's smoothing
        for ($i = $n; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($n - 1)) + $gains[$i]) / $n;
            $avgLoss = (($avgLoss * ($n - 1)) + $losses[$i]) / $n;
        }

        if ($avgLoss == 0) {
            return 100;
        }

        $rs = $avgGain / $avgLoss;
        $rsi = 100 - (100 / (1 + $rs));

        return round($rsi, 2);
    }

    /**
     * Get raw klines data for a symbol
     */
    public function getKlinesData(string $symbol): JsonResponse
    {
        try {
            $klines = $this->fetchKlinesData($symbol);
            
            return response()->json([
                'success' => true,
                'symbol' => $symbol,
                'klines' => $klines
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Error fetching klines for {$symbol}: " . $e->getMessage()
            ], 500);
        }
    }
}
