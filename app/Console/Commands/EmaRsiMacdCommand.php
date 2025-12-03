<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Models\CryptoSignal;

class EmaRsiMacdCommand extends Command
{
    protected $signature = 'crypto:ema-rsi-macd
                            {--symbol= : Analyze specific symbol}
                            {--interval=15m : Time interval (15m, 1h, 4h)}
                            {--limit=200 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '🧠 EMA + RSI + MACD Strategy: Universal trend-following setup';

    protected array $analysisSignals = [];
    protected array $analysisErrors = [];
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $this->info('🧠 Starting EMA + RSI + MACD Analysis...');
        $this->newLine();

        $symbol = $this->option('symbol');
        $interval = $this->option('interval');
        $limit = (int) $this->option('limit');
        $sendTelegram = $this->option('telegram');
        $telegramOnly = $this->option('telegram-only');

        if ($sendTelegram || $telegramOnly) {
            $this->info('📱 Testing Telegram connection...');
            if (!$this->telegramService->testConnection()) {
                $this->error('❌ Telegram connection failed!');
                return Command::FAILURE;
            }
            $this->info('✅ Telegram connection successful!');
            $this->newLine();
        }

        $symbols = $symbol ? array_map('trim', explode(',', $symbol)) : config('crypto_symbols');

        $this->info("📊 Analyzing " . count($symbols) . " symbols with EMA+RSI+MACD strategy");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("EMA+RSI+MACD error for {$cryptoSymbol}: " . $e->getMessage());
            }
            $progressBar->advance();
            usleep(100000);
        }

        $progressBar->finish();
        $this->newLine(2);

        $totalSignals = !empty($this->analysisSignals) ? array_sum(array_map('count', $this->analysisSignals)) : 0;
        $totalSymbols = count($symbols);

        if ($sendTelegram || $telegramOnly) {
            if (!empty($this->analysisSignals)) {
                $this->info('📱 Sending signals to instant signal bot...');
                foreach ($this->analysisSignals as $symbol => $signals) {
                    foreach ($signals as $signal) {
                        // Отправляем только MEDIUM и STRONG сигналы
                        if (in_array($signal['strength'], ['STRONG']) && CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'EMA+RSI+MACD')) {
                            $this->telegramService->sendInstantSignal($signal, $symbol, 'EMA+RSI+MACD');
                            $this->saveSignalToDatabase($signal, $symbol);
                            usleep(500000);
                        } elseif ($signal['strength'] === 'WEAK') {
                            $this->info("⏭️ Skipping WEAK signal for {$symbol}: {$signal['type']} ({$signal['strength']})");
                        }
                    }
                }
                $this->info('✅ Signals sent to instant bot!');
            } else {
                $this->telegramService->sendNoSignalsMessage($totalSymbols, count($this->analysisErrors), $this->analysisErrors);
                $this->info('✅ No signals message sent!');
            }
        }

        if (!$telegramOnly) {
            $this->displayResults($totalSymbols, $totalSignals);
        }

        return Command::SUCCESS;
    }

    private function analyzeSymbol(string $symbol, string $interval, int $limit): void
    {
        $klines = $this->fetchKlinesData($symbol, $interval, $limit);

        if (empty($klines) || count($klines) < 100) {
            $this->analysisErrors[$symbol] = "Insufficient data";
            return;
        }

        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        $ema20 = $this->calculateEMA($closes, 20);
        $ema50 = $this->calculateEMA($closes, 50);
        $rsi = $this->calculateRSI($closes, 14);
        $macd = $this->calculateMACD($closes);
        $atr = $this->calculateATR($highs, $lows, $closes, 14);

        $signal = $this->generateSignal($symbol, end($closes), $ema20, $ema50, $rsi, $macd, $atr);

        if ($signal) {
            $this->analysisSignals[$symbol] = [$signal];
        }
    }

    private function generateSignal(string $symbol, float $price, float $ema20, float $ema50, float $rsi, array $macd, float $atr): ?array
    {
        $macdLine = $macd['macd'];
        $macdSignal = $macd['signal'];
        $macdHist = $macd['histogram'];

        $signalType = null;
        $strength = 'WEAK';

        // BUY: Price crosses above EMA20, EMA20 > EMA50, MACD crosses above 0
        if ($price > $ema20 && $ema20 > $ema50 && $macdLine > 0 && $macdHist > 0 && $rsi < 70) {
            $signalType = 'BUY';

            // Strength based on RSI and MACD momentum
            if ($rsi >= 40 && $rsi <= 60 && abs($macdHist) > 0.5) {
                $strength = 'STRONG';
            } elseif ($rsi > 30 && abs($macdHist) > 0.2) {
                $strength = 'MEDIUM';
            }
        }
        // SELL: Price crosses below EMA20, EMA20 < EMA50, MACD crosses below 0
        elseif ($price < $ema20 && $ema20 < $ema50 && $macdLine < 0 && $macdHist < 0 && $rsi > 30) {
            $signalType = 'SELL';

            if ($rsi >= 40 && $rsi <= 60 && abs($macdHist) > 0.5) {
                $strength = 'STRONG';
            } elseif ($rsi < 70 && abs($macdHist) > 0.2) {
                $strength = 'MEDIUM';
            }
        }

        if (!$signalType) {
            return null;
        }

        $slTp = $this->calculateSLTP($signalType, $price, $atr, $strength);

        return [
            'type' => $signalType,
            'strength' => $strength,
            'price' => $price,
            'rsi' => $rsi,
            'ema' => $ema20,
            'macd' => $macdLine,
            'macd_signal' => $macdSignal,
            'macd_histogram' => $macdHist,
            'atr' => $atr,
            'stop_loss' => $slTp['stop_loss'],
            'take_profit' => $slTp['take_profit'],
            'volume_ratio' => 1.0,
            'htf_trend' => 'N/A',
            'htf_rsi' => 0,
            'ltf_rsi' => 0,
            'reason' => $this->generateReason($signalType, $rsi, $ema20, $ema50, $macdLine, $macdHist)
        ];
    }

    private function calculateSLTP(string $type, float $price, float $atr, string $strength): array
    {
        $multiplier = match($strength) {
            'STRONG' => ['sl' => 1.5, 'tp' => 4.5], // RR 1:3
            'MEDIUM' => ['sl' => 1.5, 'tp' => 3.0], // RR 1:2
            default => ['sl' => 1.5, 'tp' => 2.25]  // RR 1:1.5
        };

        if ($type === 'BUY') {
            return [
                'stop_loss' => $price - ($atr * $multiplier['sl']),
                'take_profit' => $price + ($atr * $multiplier['tp'])
            ];
        } else {
            return [
                'stop_loss' => $price + ($atr * $multiplier['sl']),
                'take_profit' => $price - ($atr * $multiplier['tp'])
            ];
        }
    }

    private function generateReason(string $type, float $rsi, float $ema20, float $ema50, float $macd, float $macdHist): string
    {
        $trend = $ema20 > $ema50 ? 'Bullish' : 'Bearish';
        $macdDirection = $macd > 0 ? 'above zero' : 'below zero';

        return "EMA+RSI+MACD: {$type} signal | Trend: {$trend} | RSI: {$rsi} | MACD {$macdDirection} | Histogram: " . number_format($macdHist, 2);
    }

    private function fetchKlinesData(string $symbol, string $interval, int $limit): array
    {
        $response = Http::timeout(10)->get('https://api.binance.com/api/v3/klines', [
            'symbol' => $symbol . 'USDT',
            'interval' => $interval,
            'limit' => $limit
        ]);

        if (!$response->successful()) {
            throw new \Exception("API Error: " . $response->body());
        }

        return $response->json();
    }

    private function calculateEMA(array $closes, int $period): float
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

    private function calculateRSI(array $closes, int $period): float
    {
        if (count($closes) < $period + 1) {
            return 50.0;
        }

        $deltas = [];
        for ($i = 1; $i < count($closes); $i++) {
            $deltas[] = $closes[$i] - $closes[$i - 1];
        }

        $gains = array_map(fn($d) => max(0, $d), $deltas);
        $losses = array_map(fn($d) => max(0, -$d), $deltas);

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

    private function calculateMACD(array $closes): array
    {
        $ema12 = $this->calculateEMA($closes, 12);
        $ema26 = $this->calculateEMA($closes, 26);
        $macdLine = $ema12 - $ema26;

        // Calculate signal line (9-period EMA of MACD)
        $macdValues = [];
        for ($i = 26; $i < count($closes); $i++) {
            $slice = array_slice($closes, 0, $i + 1);
            $e12 = $this->calculateEMA($slice, 12);
            $e26 = $this->calculateEMA($slice, 26);
            $macdValues[] = $e12 - $e26;
        }

        $signalLine = count($macdValues) >= 9 ? $this->calculateEMA($macdValues, 9) : 0;
        $histogram = $macdLine - $signalLine;

        return [
            'macd' => $macdLine,
            'signal' => $signalLine,
            'histogram' => $histogram
        ];
    }

    private function calculateATR(array $highs, array $lows, array $closes, int $period): float
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

    private function saveSignalToDatabase(array $signal, string $symbol): void
    {
        try {
            CryptoSignal::saveSignal([
                'symbol' => $symbol,
                'strategy' => 'EMA+RSI+MACD',
                'type' => $signal['type'],
                'strength' => $signal['strength'],
                'price' => $signal['price'],
                'rsi' => $signal['rsi'],
                'ema' => $signal['ema'],
                'stop_loss' => $signal['stop_loss'],
                'take_profit' => $signal['take_profit'],
                'volume_ratio' => $signal['volume_ratio'],
                'htf_trend' => $signal['htf_trend'],
                'htf_rsi' => $signal['htf_rsi'],
                'ltf_rsi' => $signal['ltf_rsi'],
                'reason' => $signal['reason']
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to save EMA+RSI+MACD signal", ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
    }

    private function displayResults(int $totalSymbols, int $totalSignals): void
    {
        $this->info("📈 EMA+RSI+MACD Analysis Complete!");
        $this->info("Total symbols: {$totalSymbols}");
        $this->info("Total signals: {$totalSignals}");
        $this->info("Errors: " . count($this->analysisErrors));
        $this->newLine();
    }
}

