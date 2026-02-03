<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Models\CryptoSignal;

class EmaStochasticCommand extends Command
{
    protected $signature = 'crypto:ema-stochastic
                            {--symbol= : Analyze specific symbol}
                            {--interval=15m : Time interval (5m, 15m, 30m)}
                            {--limit=100 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '⚡ EMA(9/21) + Stochastic Strategy: Impulse scalping strategy';

    protected array $analysisSignals = [];
    protected array $analysisErrors = [];
    protected TelegramService $telegramService;
    protected \App\Services\CryptoAnalysisService $analysisService;

    public function __construct(TelegramService $telegramService, \App\Services\CryptoAnalysisService $analysisService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
        $this->analysisService = $analysisService;
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
                        // 🔥 Отправляем только MEDIUM и STRONG сигналы (для скальпинга)
                        if (in_array($signal['strength'], ['MEDIUM', 'STRONG'])) {
                            // 🔒 Глобальный фильтр: проверка рыночного контекста
                            $marketContext = $this->analysisService->checkMarketContext($symbol, $signal['type']);
                            
                            if (!$marketContext['allowed']) {
                                $this->info("⏭️ Skipping {$symbol}: {$signal['type']} - " . $marketContext['reason']);
                                continue;
                            }
                            
                            if (CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'EMA+Stochastic')) {
                                try {
                                    $sent = $this->telegramService->sendInstantSignal($signal, $symbol, 'EMA+Stochastic');
                                    if ($sent) {
                                        $this->info("✅ Signal sent to Telegram: {$symbol} {$signal['type']} ({$signal['strength']})");
                                        $this->saveSignalToDatabase($signal, $symbol, true);
                                    } else {
                                        $this->warn("⚠️ Failed to send signal to Telegram: {$symbol} {$signal['type']} ({$signal['strength']})");
                                        $this->saveSignalToDatabase($signal, $symbol, false);
                                    }
                                } catch (\Exception $e) {
                                    $this->error("❌ Error sending signal to Telegram: {$symbol} - " . $e->getMessage());
                                    Log::error("EMA+Stochastic: Error sending signal", [
                                        'symbol' => $symbol,
                                        'error' => $e->getMessage()
                                    ]);
                                    $this->saveSignalToDatabase($signal, $symbol, false);
                                }
                            } else {
                                $this->info("⏭️ Skipping {$symbol}: {$signal['type']} - duplicate signal (shouldSendSignal returned false)");
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

            $result = $this->analysisService->analyzeEmaStochastic($symbol, $params);

            if ($result['signal'] === 'HOLD') {
                return;
            }

            $signal = $this->convertResultToSignal($result);
            
            if ($signal) {
                $this->analysisSignals[$symbol] = [$signal];
            }
        } catch (\Exception $e) {
            $this->analysisErrors[$symbol] = $e->getMessage();
            \Illuminate\Support\Facades\Log::error("EMA+Stochastic analysis error for {$symbol}: " . $e->getMessage());
        }
    }

    private function convertResultToSignal(array $result): ?array
    {
        return [
            'type' => $result['signal'],
            'strength' => $result['strength'],
            'price' => $result['price'],
            'rsi' => $result['rsi'] ?? 50.0,
            'ema' => $result['ema_fast'] ?? $result['price'],
            'stochastic_k' => $result['stochastic_k'] ?? 50.0,
            'stochastic_d' => $result['stochastic_d'] ?? 50.0,
            'stop_loss' => $result['stop_loss'],
            'take_profit' => $result['take_profit'],
            'volume_ratio' => 1.0,
            'htf_trend' => 'N/A',
            'htf_rsi' => 0,
            'ltf_rsi' => 0,
            'reason' => $result['reason']
        ];
    }


    private function saveSignalToDatabase(array $signal, string $symbol, bool $sentToTelegram = false): void
    {
        try {
            $savedSignal = CryptoSignal::saveSignal([
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

            // Обновляем статус отправки в Telegram
            if ($sentToTelegram) {
                $savedSignal->sent_to_telegram = true;
                $savedSignal->save();
            }

            $this->info("💾 Signal saved to database: {$symbol} {$signal['type']} ({$signal['strength']}) - sent: " . ($sentToTelegram ? 'YES' : 'NO'));
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

