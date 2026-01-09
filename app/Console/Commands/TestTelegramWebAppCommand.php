<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;
use Illuminate\Support\Facades\Http;

class TestTelegramWebAppCommand extends Command
{
    protected $signature = 'telegram:test-webapp';
    protected $description = 'Test Telegram Web App integration';

    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $this->info('🧪 Тестирование интеграции Telegram Web App');
        $this->newLine();

        // 1. Проверка подключения к боту
        $this->info('1️⃣ Проверка подключения к Telegram боту...');
        if (!$this->telegramService->testConnection()) {
            $this->error('❌ Не удалось подключиться к Telegram боту!');
            return Command::FAILURE;
        }
        $this->info('✅ Подключение к боту успешно');
        $this->newLine();

        // 2. Проверка APP_URL
        $this->info('2️⃣ Проверка APP_URL...');
        $appUrl = env('APP_URL', 'http://localhost:8000');
        $this->line("   APP_URL: {$appUrl}");
        
        if (!str_starts_with($appUrl, 'https://')) {
            $this->warn('⚠️  ВНИМАНИЕ: APP_URL должен использовать HTTPS для работы в Telegram Web App!');
            $this->warn('   Для локального тестирования используйте ngrok или другой туннель.');
        } else {
            $this->info('✅ APP_URL использует HTTPS');
        }
        $this->newLine();

        // 3. Проверка доступности приложения
        $this->info('3️⃣ Проверка доступности приложения...');
        try {
            $response = Http::timeout(5)->get($appUrl);
            if ($response->successful()) {
                $this->info('✅ Приложение доступно');
            } else {
                $this->error("❌ Приложение недоступно (HTTP {$response->status()})");
            }
        } catch (\Exception $e) {
            $this->error('❌ Не удалось подключиться к приложению: ' . $e->getMessage());
            $this->warn('   Убедитесь, что приложение запущено и доступно по указанному URL');
        }
        $this->newLine();

        // 4. Проверка настройки меню
        $this->info('4️⃣ Проверка настройки меню бота...');
        $token = $this->telegramService->getAccessToken();
        try {
            $response = Http::get("https://api.telegram.org/bot{$token}/getChatMenuButton");
            if ($response->successful() && $response->json('ok')) {
                $menuButton = $response->json('result');
                if (isset($menuButton['type']) && $menuButton['type'] === 'web_app') {
                    $this->info('✅ Меню бота настроено');
                    $this->line("   Текст кнопки: {$menuButton['text']}");
                    $this->line("   URL: {$menuButton['web_app']['url']}");
                } else {
                    $this->warn('⚠️  Меню бота не настроено или использует другой тип');
                    $this->info('   Выполните: php artisan telegram:setup-menu');
                }
            }
        } catch (\Exception $e) {
            $this->warn('⚠️  Не удалось проверить меню: ' . $e->getMessage());
        }
        $this->newLine();

        // 5. Проверка JavaScript файла
        $this->info('5️⃣ Проверка JavaScript файла...');
        $jsPath = public_path('js/telegram-web-app.js');
        if (file_exists($jsPath)) {
            $this->info('✅ Файл telegram-web-app.js найден');
        } else {
            $this->error('❌ Файл telegram-web-app.js не найден!');
        }
        $this->newLine();

        // 6. Инструкции по тестированию
        $this->info('📋 Инструкции по тестированию:');
        $this->newLine();
        $this->line('1. Убедитесь, что приложение доступно по HTTPS');
        $this->line('2. Выполните настройку меню: php artisan telegram:setup-menu');
        $this->line('3. Откройте Telegram и найдите вашего бота');
        $this->line('4. Отправьте команду /start');
        $this->line('5. Нажмите на кнопку меню (три линии) в боте');
        $this->line('6. Должна появиться кнопка "📊 Открыть приложение"');
        $this->line('7. Нажмите на неё - приложение откроется в Telegram');
        $this->newLine();
        $this->info('💡 Для локального тестирования используйте ngrok:');
        $this->line('   ngrok http 8000');
        $this->line('   Затем установите APP_URL в .env на полученный HTTPS URL');
        $this->newLine();

        return Command::SUCCESS;
    }
}
