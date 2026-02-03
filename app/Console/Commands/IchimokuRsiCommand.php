<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Services\CryptoAnalysisService;
use App\Models\CryptoSignal;

class IchimokuRsiCommand extends Command
{
    protected $signature = 'crypto:ichimoku-rsi
                            {--symbol= : Analyze specific symbol}
                            {--interval=1h : Time interval (1h, 4h, 1d)}
                            {--limit=100 : Number of candles to fetch}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = '🔥 Ichimoku + RSI Strategy: Trend with cloud support';

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
        $symbol = $this->option('symbol');
        $interval = $this->option('interval');
        $limit = (int) $this->option('limit');
        $sendTelegram = $this->option('telegram');
        $telegramOnly = $this->option('telegram-only');

        if ($sendTelegram || $telegramOnly) {
            if (!$this->telegramService->testConnection()) {
                return Command::FAILURE;
            }
        }

        $symbols = $symbol ? array_map('trim', explode(',', $symbol)) : config('crypto_symbols');

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("Ichimoku+RSI error for {$cryptoSymbol}: " . $e->getMessage());
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
                        if (in_array($signal['strength'], ['STRONG'])) {
                            // 🔒 Глобальный фильтр: проверка рыночного контекста
                            $marketContext = $this->analysisService->checkMarketContext($symbol, $signal['type']);
                            
                            if (!$marketContext['allowed']) {
                                $this->info("⏭️ Skipping {$symbol}: {$signal['type']} - " . $marketContext['reason']);
                                continue;
                            }
                            
                            if (CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'Ichimoku+RSI', $signal['rsi'])) {
                                try {
                                    $sent = $this->telegramService->sendInstantSignal($signal, $symbol, 'Ichimoku+RSI');
                                    if ($sent) {
                                        $this->info("✅ Signal sent to Telegram: {$symbol} {$signal['type']} ({$signal['strength']})");
                                        $this->saveSignalToDatabase($signal, $symbol, true);
                                    } else {
                                        $this->warn("⚠️ Failed to send signal to Telegram: {$symbol} {$signal['type']} ({$signal['strength']})");
                                        $this->saveSignalToDatabase($signal, $symbol, false);
                                    }
                                } catch (\Exception $e) {
                                    $this->error("❌ Error sending signal to Telegram: {$symbol} - " . $e->getMessage());
                                    Log::error("Ichimoku+RSI: Error sending signal", [
                                        'symbol' => $symbol,
                                        'error' => $e->getMessage()
                                    ]);
                                    $this->saveSignalToDatabase($signal, $symbol, false);
                                }
                            } else {
                                $this->info("⏭️ Skipping {$symbol}: {$signal['type']} - duplicate signal (shouldSendSignal returned false)");
                            }
                            usleep(500000);
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
            'rsi_period' => 14,
            'tenkan_period' => 9,
            'kijun_period' => 26,
            'senkou_b_period' => 52,
            'atr_period' => 14,
        ];

        $result = $this->analysisService->analyzeIchimokuRsi($symbol, $params);

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
            'rsi' => $result['rsi'],
            'ema' => $result['kijun'], // Use Kijun as EMA equivalent
            'stop_loss' => $result['stop_loss'],
            'take_profit' => $result['take_profit'],
            'volume_ratio' => 1.0,
            'htf_trend' => $result['price_above_cloud'] ? 'BULLISH' : 'BEARISH',
            'htf_rsi' => $result['rsi'],
            'ltf_rsi' => 0,
            'reason' => $result['reason'],
            'tenkan' => $result['tenkan'],
            'kijun' => $result['kijun'],
            'senkou_a' => $result['senkou_a'],
            'senkou_b' => $result['senkou_b'],
            'cloud_top' => $result['cloud_top'],
            'cloud_bottom' => $result['cloud_bottom'],
            'price_above_cloud' => $result['price_above_cloud'],
        ];
    }

    private function saveSignalToDatabase(array $signal, string $symbol, bool $sentToTelegram = false): void
    {
        try {
            $savedSignal = CryptoSignal::saveSignal([
                'symbol' => $symbol,
                'strategy' => 'Ichimoku+RSI',
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
            Log::error("Failed to save Ichimoku+RSI signal", ['symbol' => $symbol, 'error' => $e->getMessage()]);
        }
    }

    private function displayResults(int $totalSymbols, int $totalSignals): void
    {
    }
}
