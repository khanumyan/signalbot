<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CryptoSignal;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Log;

class TestSendSignalCommand extends Command
{
    protected $signature = 'test:send-signal {signal_id : ID сигнала из базы данных}';
    protected $description = 'Тестовая отправка сигнала из базы данных в Telegram';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $signalId = $this->argument('signal_id');
        
        $this->info("🔍 Ищу сигнал с ID: {$signalId}");
        
        $signal = CryptoSignal::find($signalId);
        
        if (!$signal) {
            $this->error("❌ Сигнал с ID {$signalId} не найден в базе данных");
            return Command::FAILURE;
        }

        $this->info("✅ Сигнал найден:");
        $this->info("   Symbol: {$signal->symbol}");
        $this->info("   Type: {$signal->type}");
        $this->info("   Strength: {$signal->strength}");
        $this->info("   Strategy: {$signal->strategy}");
        $this->info("   Price: {$signal->price}");
        $this->newLine();

        // Преобразуем модель в массив для TelegramService
        $signalArray = [
            'type' => $signal->type,
            'strength' => $signal->strength,
            'price' => (string) $signal->price,
            'rsi' => (string) $signal->rsi,
            'ema' => (string) ($signal->ema ?? $signal->price),
            'stop_loss' => (string) $signal->stop_loss,
            'take_profit' => (string) $signal->take_profit,
            'volume_ratio' => (string) ($signal->volume_ratio ?? 1.0),
            'htf_trend' => $signal->htf_trend ?? 'N/A',
            'htf_rsi' => (string) ($signal->htf_rsi ?? 0),
            'ltf_rsi' => (string) ($signal->ltf_rsi ?? 0),
            'reason' => $signal->reason ?? 'Test signal',
        ];

        // Добавляем дополнительные поля для Smart Money Concepts, если они есть
        if ($signal->strategy === 'Smart Money Concepts') {
            // Попробуем получить order_block и market_structure из reason или других полей
            // Для теста можем оставить null или попытаться извлечь из reason
            $signalArray['order_block_high'] = null;
            $signalArray['order_block_low'] = null;
            $signalArray['market_structure'] = null;
        }

        $this->info("📱 Отправляю сигнал в Telegram...");
        
        try {
            // Тестируем подключение
            if (!$this->telegramService->testConnection()) {
                $this->error("❌ Ошибка подключения к Telegram");
                return Command::FAILURE;
            }

            // Отправляем сигнал
            $sent = $this->telegramService->sendInstantSignal(
                $signalArray,
                $signal->symbol,
                $signal->strategy
            );

            if ($sent) {
                $this->info("✅ Сигнал успешно отправлен в Telegram!");
                
                // Обновляем статус в базе
                $signal->sent_to_telegram = true;
                $signal->save();
                $this->info("💾 Статус отправки обновлен в базе данных");
                
                return Command::SUCCESS;
            } else {
                $this->warn("⚠️ Отправка сигнала вернула false (проверьте логи)");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error("❌ Ошибка при отправке: " . $e->getMessage());
            Log::error("Test send signal error", [
                'signal_id' => $signalId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}


