<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class TelegramInfoCommand extends Command
{
    protected $signature = 'telegram:info';
    protected $description = 'Get Telegram bot information and setup instructions';

    public function handle(TelegramService $telegramService)
    {
        $this->info('🤖 Telegram Bot Information');
        $this->newLine();
        
        // Test connection
        if (!$telegramService->testConnection()) {
            $this->error('❌ Telegram connection failed!');
            return Command::FAILURE;
        }
        
        $this->info('✅ Telegram connection successful!');
        $this->newLine();
        
        $this->info('📋 Setup Instructions:');
        $this->line('1. Users need to start a conversation with the bot first');
        $this->line('2. Send /start command to the bot');
        $this->line('3. Bot will be able to send messages after that');
        $this->newLine();
        
        $this->info('🔗 Bot Information:');
        $this->line('Bot Token: 8367673646:AAGsRdFKuJuOlHHEz6aP83VBze7y8GTYouc');
        $this->line('Target Chat IDs: 6058842416, 5480079445, 637800420');
        $this->newLine();
        
        $this->info('📱 To start the bot:');
        $this->line('1. Open Telegram');
        $this->line('2. Search for your bot using the token');
        $this->line('3. Send /start command');
        $this->line('4. Bot will be ready to receive signals');
        $this->newLine();
        
        $this->info('🧪 Test Commands:');
        $this->line('php artisan telegram:test');
        $this->line('php artisan crypto:analyze --telegram');
        $this->line('php artisan crypto:analyze --telegram-only');
        
        return Command::SUCCESS;
    }
}
