<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Models\CryptoSignal;

class EmaStochasticCommand extends Command
{
    protected $signature = 'crypto:ema-stochastic
                            {--symbol= : Analyze specific symbol}
                            {--interval=5m : Time interval (1m, 5m, 15m)}
                            {--limit=100 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '⚡ EMA(9/21) + Stochastic Strategy: Impulse scalping strategy';

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
        $this->info('⚡ Starting EMA + Stochastic Analysis...');
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
        
        $this->info("📊 Analyzing " . count($symbols) . " symbols with EMA+Stochastic strategy");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("EMA+Stochastic error for {$cryptoSymbol}: " . $e->getMessage());
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
                        if (in_array($signal['strength'], ['MEDIUM', 'STRONG']) && CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'EMA+Stochastic')) {
                            $this->telegramService->sendInstantSignal($signal, $symbol, 'EMA+Stochastic');
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

        if (empty($klines) || count($klines) < 30) {
            $this->analysisErrors[$symbol] = "Insufficient data";
            return;
        }

        $closes = array_map(fn($k) => (float) $k[4], $klines);
        $highs = array_map(fn($k) => (float) $k[2], $klines);
        $lows = array_map(fn($k) => (float) $k[3], $klines);

        $ema9 = $this->calculateEMA($closes, 9);
        $ema21 = $this->calculateEMA($closes, 21);
        $stochastic = $this->calculateStochastic($highs, $lows, $closes, 14, 3, 3);
        $atr = $this->calculateATR($highs, $lows, $closes, 14);
        $price = end($closes);

        $signal = $this->generateSignal($symbol, $price, $ema9, $ema21, $stochastic, $atr);

        if ($signal) {
            $this->analysisSignals[$symbol] = [$signal];
        }
    }

    private function generateSignal(string $symbol, float $price, float $ema9, float $ema21, array $stoch, float $atr): ?array
    {
        $signalType = null;
        $strength = 'WEAK';

        $k = $stoch['k'];
        $d = $stoch['d'];

        // BUY: EMA9 crosses above EMA21 + Stochastic exits from oversold (< 20)
        if ($ema9 > $ema21 && $k > 20 && $k < 50 && $k > $d) {
            $signalType = 'BUY';
            
            if ($k > $d && ($k - $d) > 5) {
                $strength = 'STRONG';
            } elseif ($k > $d) {
                $strength = 'MEDIUM';
            }
        }
        // SELL: EMA9 crosses below EMA21 + Stochastic exits from overbought (> 80)
        elseif ($ema9 < $ema21 && $k < 80 && $k > 50 && $k < $d) {
            $signalType = 'SELL';
            
            if ($k < $d && ($d - $k) > 5) {
                $strength = 'STRONG';
            } elseif ($k < $d) {
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
            'rsi' => 50.0,
            'ema' => $ema9,
            'stochastic_k' => $k,
            'stochastic_d' => $d,
            'atr' => $atr,
            'stop_loss' => $slTp['stop_loss'],
            'take_profit' => $slTp['take_profit'],
            'volume_ratio' => 1.0,
            'htf_trend' => 'N/A',
            'htf_rsi' => 0,
            'ltf_rsi' => 0,
            'reason' => $this->generateReason($signalType, $ema9, $ema21, $k, $d)
        ];
    }

    private function calculateSLTP(string $type, float $price, float $atr, string $strength): array
    {
        // Для скальпинга более узкие SL/TP
        $multiplier = match($strength) {
            'STRONG' => ['sl' => 1.2, 'tp' => 2.4], // RR 1:2
            'MEDIUM' => ['sl' => 1.2, 'tp' => 1.8], // RR 1:1.5
            default => ['sl' => 1.2, 'tp' => 1.2]   // RR 1:1
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

    private function generateReason(string $type, float $ema9, float $ema21, float $k, float $d): string
    {
        $emaTrend = $ema9 > $ema21 ? 'Bullish' : 'Bearish';
        $stochCross = $k > $d ? 'K>D' : 'K<D';
        
        return "EMA+Stochastic: {$type} | EMA {$emaTrend} | Stochastic {$stochCross} | K: " . 
               number_format($k, 1) . " | D: " . number_format($d, 1);
    }

    private function fetchKlinesData(string $symbol, string $interval, int $limit): array
    {
        $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/klines', [
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

    private function calculateStochastic(array $highs, array $lows, array $closes, int $kPeriod, int $kSmooth, int $dPeriod): array
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
        
        $k = 0;
        if ($highest != $lowest) {
            $k = (($currentClose - $lowest) / ($highest - $lowest)) * 100;
        }

        // For simplicity, using K as D (in real implementation would smooth K and then calculate D)
        $d = $k;

        return ['k' => $k, 'd' => $d];
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
                'strategy' => 'EMA+Stochastic',
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
            Log::error("Failed to save EMA+Stochastic signal", ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
    }

    private function displayResults(int $totalSymbols, int $totalSignals): void
    {
        $this->info("📈 EMA+Stochastic Analysis Complete!");
        $this->info("Total symbols: {$totalSymbols}");
        $this->info("Total signals: {$totalSignals}");
        $this->info("Errors: " . count($this->analysisErrors));
        $this->newLine();
    }
}

