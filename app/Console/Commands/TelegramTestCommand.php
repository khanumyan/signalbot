<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramService;

class TelegramTestCommand extends Command
{
    protected $signature = 'telegram:test {--message= : Custom test message}';
    protected $description = 'Test Telegram bot connection and send test message';

    public function handle(TelegramService $telegramService)
    {
        $this->info('📱 Testing Telegram Bot...');
        
        // Test connection
        if (!$telegramService->testConnection()) {
            $this->error('❌ Telegram connection failed!');
            return Command::FAILURE;
        }
        
        $this->info('✅ Telegram connection successful!');
        
        // Send test message
        $message = $this->option('message') ?: '🤖 <b>Telegram Bot Test</b>' . "\n\n" . 
                   'Bot is working correctly!' . "\n" .
                   'Time: ' . now()->format('Y-m-d H:i:s');
        
        $this->info('📤 Sending test message...');
        
        if ($telegramService->sendCustomMessage($message)) {
            $this->info('✅ Test message sent successfully!');
        } else {
            $this->error('❌ Failed to send test message!');
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
