<?php

namespace App\Console\Commands;

use App\Models\CryptoSignal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckSignalStatusCommand extends Command
{
    protected $signature = 'signals:check-status';
    protected $description = 'Check status of signals created 24-36 hours ago (DONE/MISSED/PROCESSING)';

    public function handle()
    {
        $this->info('🔍 Checking signal statuses...');

        try {
            // Находим сигналы открытые 24-36 часов назад (по signal_time)
            $now = Carbon::now();
            $fromTime = $now->copy()->subHours(36);
            $toTime = $now->copy()->subHours(24);

            $signals = CryptoSignal::whereNull('status')
                ->whereBetween('signal_time', [$fromTime, $toTime])
                ->get();

            if ($signals->isEmpty()) {
                $this->info('✅ No signals found in the 24-36 hours range');
                return Command::SUCCESS;
            }

            $this->info("📊 Found {$signals->count()} signals to check");

            $doneCount = 0;
            $missedCount = 0;
            $processingCount = 0;
            $errorCount = 0;

            $progressBar = $this->output->createProgressBar($signals->count());
            $progressBar->start();

            foreach ($signals as $signal) {
                try {
                    $status = $this->checkSignalStatus($signal);
                    $signal->update(['status' => $status]);

                    match($status) {
                        'DONE' => $doneCount++,
                        'MISSED' => $missedCount++,
                        'PROCESSING' => $processingCount++,
                        default => null
                    };

                    $progressBar->advance();
                    usleep(200000); // 0.2 секунды задержка между запросами
                } catch (\Exception $e) {
                    $errorCount++;
                    Log::error("Error checking signal {$signal->id}: " . $e->getMessage());
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("✅ Status check complete!");
            $this->info("   DONE: {$doneCount}");
            $this->info("   MISSED: {$missedCount}");
            $this->info("   PROCESSING: {$processingCount}");
            if ($errorCount > 0) {
                $this->warn("   ERRORS: {$errorCount}");
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('Signal status check error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * Проверяет статус сигнала на основе исторических данных
     */
    private function checkSignalStatus(CryptoSignal $signal): string
    {
        // Получаем исторические данные от времени открытия сигнала (signal_time) до сейчас
        $startTime = $signal->signal_time->timestamp * 1000; // Binance требует миллисекунды
        $endTime = Carbon::now()->timestamp * 1000;

        // Получаем все свечи за период (используем 15m интервал)
        $allKlines = $this->fetchHistoricalKlines($signal->symbol, $startTime, $endTime);

        if (empty($allKlines)) {
            return 'PROCESSING'; // Если не удалось получить данные, оставляем в обработке
        }

        // Извлекаем все цены (high, low, close) за период
        $reachedTakeProfit = false;
        $hitStopLoss = false;

        foreach ($allKlines as $kline) {
            $high = (float) $kline[2]; // High price
            $low = (float) $kline[3];  // Low price
            $close = (float) $kline[4]; // Close price

            if ($signal->type === 'BUY') {
                // BUY: проверяем достижение TP и пробитие SL
                if ($high >= $signal->take_profit) {
                    $reachedTakeProfit = true;
                }
                if ($low < $signal->stop_loss) {
                    $hitStopLoss = true;
                }
            } else {
                // SELL: проверяем достижение TP и пробитие SL
                if ($low <= $signal->take_profit) {
                    $reachedTakeProfit = true;
                }
                if ($high > $signal->stop_loss) {
                    $hitStopLoss = true;
                }
            }
        }

        // Определяем статус
        if ($hitStopLoss) {
            return 'MISSED';
        }

        if ($reachedTakeProfit && !$hitStopLoss) {
            return 'DONE';
        }

        // Если не достигнут TP и не пробит SL - все еще в процессе
        return 'PROCESSING';
    }

    /**
     * Получает исторические данные klines за период
     */
    private function fetchHistoricalKlines(string $symbol, int $startTime, int $endTime): array
    {
        try {
            $allKlines = [];
            $currentStartTime = $startTime;
            $limit = 1000; // Максимум за один запрос
            $interval = '15m'; // 15 минут интервал

            // Binance API позволяет получить максимум 1000 свечей за запрос
            // Нужно делать несколько запросов если период большой
            while ($currentStartTime < $endTime) {
                $response = Http::timeout(30)->get('https://fapi.binance.com/fapi/v1/klines', [
                    'symbol' => $symbol . 'USDT',
                    'interval' => $interval,
                    'startTime' => $currentStartTime,
                    'endTime' => $endTime,
                    'limit' => $limit
                ]);

                if (!$response->successful()) {
                    Log::warning("Failed to fetch historical klines for {$symbol}: " . $response->status());
                    break;
                }

                $klines = $response->json();
                if (empty($klines) || !is_array($klines)) {
                    break;
                }

                $allKlines = array_merge($allKlines, $klines);

                // Если получили меньше лимита, значит это последняя порция
                if (count($klines) < $limit) {
                    break;
                }

                // Следующий запрос начинаем с последней свечи + 1
                $lastKlineTime = $klines[count($klines) - 1][0]; // Время последней свечи
                $currentStartTime = $lastKlineTime + 1;

                usleep(100000); // 0.1 секунды задержка между запросами
            }

            return $allKlines;

        } catch (\Exception $e) {
            Log::error("Error fetching historical klines for {$symbol}: " . $e->getMessage());
            return [];
        }
    }
}
