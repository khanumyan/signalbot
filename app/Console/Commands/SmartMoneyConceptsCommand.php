<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Services\CryptoAnalysisService;
use App\Models\CryptoSignal;

class SmartMoneyConceptsCommand extends Command
{
    protected $signature = 'crypto:smart-money-concepts
                            {--symbol= : Analyze specific symbol}
                            {--interval=15m : Time interval (15m, 1h, 4h)}
                            {--limit=200 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '💎 Smart Money Concepts Strategy: SMC with Order Blocks, BOS/CHOCH, FVG';

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
        $this->info('💎 Starting Smart Money Concepts Analysis...');
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

        $this->info("📊 Analyzing " . count($symbols) . " symbols with Smart Money Concepts strategy");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("Smart Money Concepts error for {$cryptoSymbol}: " . $e->getMessage());
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
                        // 🔥 Отправляем только STRONG и MEDIUM сигналы
                        if (in_array($signal['strength'], ['STRONG', 'MEDIUM'])) {
                            // 🔒 Глобальный фильтр: проверка рыночного контекста
                            $marketContext = $this->analysisService->checkMarketContext($symbol, $signal['type']);
                            
                            if (!$marketContext['allowed']) {
                                $this->info("⏭️ Skipping {$symbol}: {$signal['type']} - " . $marketContext['reason']);
                                continue;
                            }
                            
                            if (CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'Smart Money Concepts', $signal['rsi'] ?? null)) {
                                $this->telegramService->sendInstantSignal($signal, $symbol, 'Smart Money Concepts');
                                $this->saveSignalToDatabase($signal, $symbol);
                            }
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
        try {
            $params = [
                'interval' => $interval,
                'limit' => $limit,
            ];

            $result = $this->analysisService->analyzeSmartMoneyConcepts($symbol, $params);

            if ($result['signal'] === 'HOLD') {
                return;
            }

            $signal = $this->convertResultToSignal($result);
            
            if ($signal) {
                $this->analysisSignals[$symbol] = [$signal];
            }
        } catch (\Exception $e) {
            $this->analysisErrors[$symbol] = $e->getMessage();
            Log::error("Smart Money Concepts analysis error for {$symbol}: " . $e->getMessage());
        }
    }

    private function convertResultToSignal(array $result): ?array
    {
        return [
            'type' => $result['signal'],
            'strength' => $result['strength'],
            'price' => $result['price'],
            'rsi' => $result['rsi'] ?? 50.0,
            'ema' => $result['ema'] ?? $result['price'],
            'stop_loss' => $result['stop_loss'],
            'take_profit' => $result['take_profit'],
            'volume_ratio' => $result['volume_ratio'] ?? 1.0,
            'htf_trend' => $result['htf_trend'] ?? 'N/A',
            'htf_rsi' => $result['htf_rsi'] ?? 0,
            'ltf_rsi' => 0,
            'reason' => $result['reason'],
            'order_block_high' => $result['order_block_high'] ?? null,
            'order_block_low' => $result['order_block_low'] ?? null,
            'market_structure' => $result['market_structure'] ?? null,
        ];
    }

    private function saveSignalToDatabase(array $signal, string $symbol): void
    {
        try {
            CryptoSignal::saveSignal([
                'symbol' => $symbol,
                'strategy' => 'Smart Money Concepts',
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

            $this->info("💾 Signal saved to database: {$symbol} {$signal['type']} ({$signal['strength']})");
        } catch (\Exception $e) {
            Log::error("Failed to save Smart Money Concepts signal", ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
    }

    private function displayResults(int $totalSymbols, int $totalSignals): void
    {
        $this->info("📈 Smart Money Concepts Analysis Complete!");
        $this->info("Total symbols analyzed: {$totalSymbols}");
        $this->info("Total signals found: {$totalSignals}");
        $this->info("Errors: " . count($this->analysisErrors));
    }
}

