<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Services\CryptoAnalysisService;
use App\Models\CryptoSignal;

class SuperTrendVwapCommand extends Command
{
    protected $signature = 'crypto:supertrend-vwap
                            {--symbol= : Analyze specific symbol}
                            {--interval=15m : Time interval (15m, 1h, 4h)}
                            {--limit=100 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '📊 SuperTrend + VWAP Strategy: Intraday trending strategy';

    protected array $analysisSignals = [];
    protected array $analysisErrors = [];
    protected TelegramService $telegramService;
    protected CryptoAnalysisService $analysisService;

    public function __construct(TelegramService $telegramService, CryptoAnalysisService $analysisService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->analysisService = $analysisService;
    }

    public function handle(): int
    {
        $this->info('📊 Starting SuperTrend + VWAP Analysis...');
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

        $this->info("📊 Analyzing " . count($symbols) . " symbols with SuperTrend+VWAP strategy");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("SuperTrend+VWAP error for {$cryptoSymbol}: " . $e->getMessage());
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
                        if (in_array($signal['strength'], ['STRONG']) && CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'SuperTrend+VWAP')) {
                            $this->telegramService->sendInstantSignal($signal, $symbol, 'SuperTrend+VWAP');
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
        $params = [
            'interval' => $interval,
            'limit' => $limit,
            'supertrend_period' => 10,
            'supertrend_multiplier' => 3.0,
            'atr_period' => 14,
            'stop_loss_multiplier' => 2.0,
            'take_profit_multiplier' => 2.0,
        ];

        $result = $this->analysisService->analyzeSuperTrendVwap($symbol, $params);

        // Skip HOLD signals
        if ($result['signal'] === 'HOLD') {
            return;
        }

        // Convert result to signal format
        $signal = $this->convertResultToSignal($result);
        
        if ($signal) {
            $this->analysisSignals[$symbol] = [$signal];
        }
    }

    private function convertResultToSignal(array $result): ?array
    {
        return [
            'type' => $result['signal'],
            'strength' => $result['strength'],
            'price' => $result['price'],
            'rsi' => 0, // Not used in this strategy
            'ema' => $result['vwap'],
            'stop_loss' => $result['stop_loss'],
            'take_profit' => $result['take_profit'],
            'volume_ratio' => 1.0,
            'htf_trend' => $result['supertrend_trend'],
            'htf_rsi' => 0,
            'ltf_rsi' => 0,
            'reason' => $result['reason'],
            'supertrend' => $result['supertrend_value'], // For TelegramService compatibility
            'supertrend_value' => $result['supertrend_value'],
            'supertrend_trend' => $result['supertrend_trend'],
            'vwap' => $result['vwap'],
            'price_to_vwap_percent' => $result['price_to_vwap_percent'],
        ];
    }

    private function saveSignalToDatabase(array $signal, string $symbol): void
    {
        try {
            CryptoSignal::saveSignal([
                'symbol' => $symbol,
                'strategy' => 'SuperTrend+VWAP',
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
            Log::error("Failed to save SuperTrend+VWAP signal", ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
    }

    private function displayResults(int $totalSymbols, int $totalSignals): void
    {
        $this->info("📈 SuperTrend+VWAP Analysis Complete!");
        $this->info("Total symbols: {$totalSymbols}");
        $this->info("Total signals: {$totalSignals}");
        $this->info("Errors: " . count($this->analysisErrors));
        $this->newLine();
    }
}
