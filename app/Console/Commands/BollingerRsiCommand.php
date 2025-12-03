<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Models\CryptoSignal;

class BollingerRsiCommand extends Command
{
    protected $signature = 'crypto:bollinger-rsi
                            {--symbol= : Analyze specific symbol}
                            {--interval=15m : Time interval (5m, 15m, 1h)}
                            {--limit=100 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '💥 Bollinger Bands + RSI Strategy: Counter-trend bounces in ranging markets';

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
        $this->info('💥 Starting Bollinger Bands + RSI Analysis...');
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

        $this->info("📊 Analyzing " . count($symbols) . " symbols with Bollinger+RSI strategy");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("Bollinger+RSI error for {$cryptoSymbol}: " . $e->getMessage());
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
                        if (in_array($signal['strength'], ['STRONG']) && CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'Bollinger+RSI')) {
                            $this->telegramService->sendInstantSignal($signal, $symbol, 'Bollinger+RSI');
                            $this->saveSignalToDatabase($signal, $symbol);
                            usleep(500000);
                        }
                        elseif ($signal['strength'] === 'WEAK') {
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

        if (empty($klines) || count($klines) < 30) {
            $this->analysisErrors[$symbol] = "Insufficient data";
            return;
        }

        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        $bb = $this->calculateBollingerBands($closes, 20, 2);
        $rsi = $this->calculateRSI($closes, 14);
        $atr = $this->calculateATR($highs, $lows, $closes, 14);
        $price = end($closes);

        $signal = $this->generateSignal($symbol, $price, $bb, $rsi, $atr);

        if ($signal) {
            $this->analysisSignals[$symbol] = [$signal];
        }
    }

    private function generateSignal(string $symbol, float $price, array $bb, float $rsi, float $atr): ?array
    {
        $signalType = null;
        $strength = 'WEAK';

        // BUY: Price touches lower band + RSI < 30
        if ($price <= $bb['lower'] * 1.005 && $rsi < 30) {
            $signalType = 'BUY';

            if ($rsi <= 20) {
                $strength = 'STRONG';
            } elseif ($rsi <= 25) {
                $strength = 'MEDIUM';
            }
        }
        // SELL: Price touches upper band + RSI > 70
        elseif ($price >= $bb['upper'] * 0.995 && $rsi > 70) {
            $signalType = 'SELL';

            if ($rsi >= 80) {
                $strength = 'STRONG';
            } elseif ($rsi >= 75) {
                $strength = 'MEDIUM';
            }
        }

        if (!$signalType) {
            return null;
        }

        $slTp = $this->calculateSLTP($signalType, $price, $bb, $atr, $strength);

        return [
            'type' => $signalType,
            'strength' => $strength,
            'price' => $price,
            'rsi' => $rsi,
            'ema' => $bb['middle'],
            'bb_upper' => $bb['upper'],
            'bb_middle' => $bb['middle'],
            'bb_lower' => $bb['lower'],
            'atr' => $atr,
            'stop_loss' => $slTp['stop_loss'],
            'take_profit' => $slTp['take_profit'],
            'volume_ratio' => 1.0,
            'htf_trend' => 'N/A',
            'htf_rsi' => 0,
            'ltf_rsi' => 0,
            'reason' => $this->generateReason($signalType, $rsi, $price, $bb)
        ];
    }

    private function calculateSLTP(string $type, float $price, array $bb, float $atr, string $strength): array
    {
        if ($type === 'BUY') {
            // SL: Немного за нижней полосой или 1xATR
            $sl = min($price - $atr, $bb['lower'] * 0.985);

            // TP: Средняя линия BB или RR 1:1.5
            $risk = $price - $sl;
            $tp = match($strength) {
                'STRONG' => min($bb['middle'], $price + ($risk * 2.0)),
                'MEDIUM' => min($bb['middle'], $price + ($risk * 1.5)),
                default => min($bb['middle'], $price + ($risk * 1.5))
            };

            return [
                'stop_loss' => $sl,
                'take_profit' => $tp
            ];
        } else {
            // SL: Немного за верхней полосой или 1xATR
            $sl = max($price + $atr, $bb['upper'] * 1.015);

            // TP: Средняя линия BB или RR 1:1.5
            $risk = $sl - $price;
            $tp = match($strength) {
                'STRONG' => max($bb['middle'], $price - ($risk * 2.0)),
                'MEDIUM' => max($bb['middle'], $price - ($risk * 1.5)),
                default => max($bb['middle'], $price - ($risk * 1.5))
            };

            return [
                'stop_loss' => $sl,
                'take_profit' => $tp
            ];
        }
    }

    private function generateReason(string $type, float $rsi, float $price, array $bb): string
    {
        $band = $type === 'BUY' ? 'Lower' : 'Upper';
        $bandValue = $type === 'BUY' ? $bb['lower'] : $bb['upper'];

        return "Bollinger+RSI: {$type} at {$band} Band | Price: " . number_format($price, 2) .
               " | Band: " . number_format($bandValue, 2) . " | RSI: {$rsi} | Target: Middle Band " . number_format($bb['middle'], 2);
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

    private function calculateBollingerBands(array $closes, int $period, float $std): array
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
                'strategy' => 'Bollinger+RSI',
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
            Log::error("Failed to save Bollinger+RSI signal", ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
    }

    private function displayResults(int $totalSymbols, int $totalSignals): void
    {
        $this->info("📈 Bollinger+RSI Analysis Complete!");
        $this->info("Total symbols: {$totalSymbols}");
        $this->info("Total signals: {$totalSignals}");
        $this->info("Errors: " . count($this->analysisErrors));
        $this->newLine();
    }
}

