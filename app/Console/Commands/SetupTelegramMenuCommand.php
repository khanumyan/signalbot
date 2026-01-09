<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class SetupTelegramMenuCommand extends Command
{
    protected $signature = 'telegram:setup-menu';
    protected $description = 'Setup Telegram bot menu with Web App button';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $this->info('🔧 Настройка меню Telegram бота...');

        try {
            $result = $this->telegramService->setupMenu([]);

            if ($result) {
                $this->info('✅ Меню бота успешно настроено!');
                $this->newLine();
                $this->info('📱 Теперь пользователи могут открыть приложение через кнопку меню в боте.');
                $this->info('🌐 URL приложения: ' . env('APP_URL', 'http://localhost:8000'));
                return Command::SUCCESS;
            } else {
                $this->error('❌ Не удалось настроить меню бота');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('❌ Ошибка: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
