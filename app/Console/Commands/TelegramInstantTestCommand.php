<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class TelegramInstantTestCommand extends Command
{
    protected $signature = 'telegram:test-instant';
    protected $description = 'Test instant Telegram bot connection and send test signal';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $this->info('📱 Testing Instant Telegram Bot...');

        // Создаем тестовый сигнал
        $testSignal = [
            'type' => 'BUY',
            'strength' => 'STRONG',
            'rsi' => 25.5,
            'price' => 42500.00,
            'ema' => 41200.00,
            'volume_ratio' => 2.5,
            'reason' => 'Test signal for instant bot'
        ];

        try {
            $success = $this->telegramService->sendInstantSignal($testSignal, 'BTC');
            
            if ($success) {
                $this->info('✅ Instant test signal sent successfully!');
                return Command::SUCCESS;
            } else {
                $this->error('❌ Failed to send instant test signal!');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
