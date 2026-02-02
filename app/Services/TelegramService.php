<?php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\FileUpload\InputFile;

class TelegramService
{
    protected Api $telegram;
    protected Api $instantTelegram; // Дополнительный бот для мгновенных сигналов
    protected array $chatIds;
    protected array $instantChatIds;

    public function __construct()
    {
        // Основной бот для системных сообщений (сводки, уведомления, новости)
        $token = '8397094934:AAFu68lLwMXew_kuL8puegZkz0WC_-0rlbk';
        $this->telegram = new Api($token);

        // Отдельный бот для трейдинговых сигналов (BUY/SELL)
        $instantToken = '8367673646:AAGsRdFKuJuOlHHEz6aP83VBze7y8GTYouc';
        $this->instantTelegram = new Api($instantToken);

        // Чаты для системных сообщений (старые)
        $this->chatIds = [6058842416, 5480079445];

        // Чаты для трейдинговых сигналов
        $this->instantChatIds = [
            6058842416,
            5480079445,
            637800420
        ];
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->telegram->getMe();
            Log::info('Telegram bot connection test successful: ' . $response->getUsername());
            return true;
        } catch (TelegramSDKException $e) {
            Log::error('Telegram connection failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get Telegram bot access token
     */
    public function getAccessToken(): string
    {
        return $this->telegram->getAccessToken();
    }

    /**
     * Get Telegram bot username
     */
    public function getBotUsername(): string
    {
        // Use configured username or get from API
        $configuredUsername = env('TELEGRAM_BOT_USERNAME', 'traidinghelperbestbot');

        if (!empty($configuredUsername)) {
            // Remove @ if present
            return ltrim($configuredUsername, '@');
        }

        // Fallback: try to get from API
        try {
            $response = $this->telegram->getMe();
            return $response->getUsername();
        } catch (TelegramSDKException $e) {
            Log::error("Failed to get bot username: " . $e->getMessage());
            return 'traidinghelperbestbot'; // Default fallback
        }
    }

    public function sendSignalNotification(array $signal, string $symbol, string $strategy = 'MTF'): bool
    {
        $message = $this->formatSignalMessage($signal, $symbol, $strategy);
        return $this->sendToMultipleChats($message);
    }

    public function sendInstantSignal(array $signal, string $symbol, string $strategy = 'MTF'): bool
    {
        $message = $this->formatInstantSignalMessage($signal, $symbol, $strategy);
        return $this->sendToInstantBotSafe($message);
    }

    public function sendAnalysisSummary(int $totalSymbols, int $totalSignals, int $totalErrors, array $errorSymbols = []): bool
    {
        $message = "📊 *Crypto Analysis Summary*\n\n";
        $message .= "Total symbols analyzed: `{$totalSymbols}`\n";
        $message .= "Symbols with signals: `{$totalSignals}`\n";
        $message .= "Errors encountered: `{$totalErrors}`\n\n";

        if (!empty($errorSymbols)) {
            $message .= "🚫 *Symbols with errors:*\n";
            foreach ($errorSymbols as $symbol => $error) {
                $message .= "• `{$symbol}`: " . substr($error, 0, 40) . "\n";
            }
            $message .= "\n";
        }

        $message .= "Time: `" . now()->addHours(4)->format('Y-m-d H:i:s') . "`";

        return $this->sendToMultipleChats($message);
    }

    public function sendNoSignalsMessage(int $totalSymbols, int $totalErrors, array $errorSymbols = []): bool
    {
        $message = "🔍 *Crypto Analysis Complete*\n\n";
        $message .= "📊 Analyzed: `{$totalSymbols}` symbols\n";
        $message .= "❌ Signals found: `0`\n";
        $message .= "⚠️ Errors: `{$totalErrors}`\n\n";

        if (!empty($errorSymbols)) {
            $message .= "🚫 *Symbols with errors:*\n";
            foreach ($errorSymbols as $symbol => $error) {
                $message .= "• `{$symbol}`: " . substr($error, 0, 40) . "\n";
            }
            $message .= "\n";
        }

        $message .= "💡 *No trading signals detected*\n";
        $message .= "Market conditions don't meet our criteria for BUY/SELL signals.\n\n";
        $message .= "⏰ Next analysis in 12 minutes\n";
        $message .= "Time: `" . now()->addHours(4)->format('Y-m-d H:i:s') . "`";

        return $this->sendToMultipleChats($message);
    }

    public function sendCustomMessage(string $message): bool
    {
        return $this->sendToMultipleChats($message);
    }

    public function sendAnalysisStartMessage(int $totalSymbols): bool
    {
        $message = "🚀 *CRYPTO ANALYSIS STARTED*\n\n";
        $message .= "📊 Analyzing: `{$totalSymbols}` symbols\n";
        $message .= "🔄 Using: Multi-Timeframe (5m/15m/1h)\n";
        $message .= "⏰ Started: `" . now()->addHours(4)->format('H:i:s') . "`\n\n";
        $message .= "🔍 Searching for MTF signals...";

        return $this->sendToMultipleChats($message);
    }

    public function sendErrorsReport(array $errors): bool
    {
        $message = "⚠️ *ERRORS DETECTED*\n\n";
        $message .= "🚫 Symbols with errors: `" . count($errors) . "`\n\n";

        $errorCount = 0;
        foreach ($errors as $symbol => $error) {
            if ($errorCount >= 10) { // Ограничиваем количество ошибок в сообщении
                $remaining = count($errors) - $errorCount;
                $message .= "... and {$remaining} more errors\n";
                break;
            }

            $shortError = strlen($error) > 30 ? substr($error, 0, 30) . '...' : $error;
            $message .= "• `{$symbol}`: {$shortError}\n";
            $errorCount++;
        }

        $message .= "\n⏰ Time: `" . now()->addHours(4)->format('H:i:s') . "`";

        return $this->sendToMultipleChats($message);
    }

    public function sendAnalysisCompleteMessage(int $totalSymbols, int $symbolsWithSignals, int $totalSignals, int $totalErrors): bool
    {
        $message = "✅ *CRYPTO ANALYSIS COMPLETED*\n\n";
        $message .= "📊 Total analyzed: `{$totalSymbols}` symbols\n";
        $message .= "🎯 Signals found: `{$totalSignals}` in `{$symbolsWithSignals}` symbols\n";
        $message .= "⚠️ Errors: `{$totalErrors}`\n\n";

        if ($totalSignals > 0) {
            $message .= "🟢 Signals sent to instant bot\n";
            $message .= "📈 Summary sent to main bot\n";
        } else {
            $message .= "🔍 No MTF signals detected\n";
        }

        $message .= "\n⏰ Completed: `" . now()->addHours()->format('H:i:s') . "`\n";
        $message .= "🔄 Next analysis in 12 minutes";

        return $this->sendToMultipleChats($message);
    }

    protected function formatSignalMessage(array $signal, string $symbol, string $strategy = 'MTF'): string
    {
        $emoji = $signal['type'] === 'BUY' ? '🟢' : '🔴';
        $strengthEmoji = match ($signal['strength']) {
            'STRONG' => '💪',
            default => '🤏',
        };

        // Emoji для стратегий
        $strategyEmoji = match($strategy) {
            'EMA+RSI+MACD' => '🧠',
            'Bollinger+RSI' => '💥',
            'EMA+Stochastic' => '⚡',
            'SuperTrend+VWAP' => '📊',
            'Ichimoku+RSI' => '🔥',
            'Smart Money Concepts' => '💎',
            default => '🔄'
        };

        $message = "{$emoji} *CRYPTO SIGNAL* {$strengthEmoji}\n\n";
        $message .= "📌 *Strategy:* {$strategyEmoji} `{$strategy}`\n";
        $message .= "Symbol: `{$symbol}`\n";
        $message .= "Type: *{$signal['type']}*\n";
        $message .= "Strength: `{$signal['strength']}`\n\n";

        // Основные индикаторы
        $message .= "📊 *TECHNICAL INDICATORS:*\n";
        $message .= "RSI: `" . rtrim(rtrim($signal['rsi'], '0'), '.') . "` (14)\n";
        $message .= "  ↳ " . ($signal['rsi'] <= 20 ? "Перепроданность" : "Перекупленность") . "\n\n";

        $message .= "Price: `$" . rtrim(rtrim($signal['price'], '0'), '.') . "`\n";
        $message .= "EMA: `$" . rtrim(rtrim($signal['ema'], '0'), '.') . "` (50)\n";
        $message .= "  ↳ " . ($signal['price'] > $signal['ema'] ? "Выше EMA = Восходящий тренд" : "Ниже EMA = Нисходящий тренд") . "\n\n";

        // Bollinger Bands
        $message .= "Bollinger Bands:\n";
        $message .= "  Upper: `$" . rtrim(rtrim($signal['bb_upper'], '0'), '.') . "`\n";
        $message .= "  Middle: `$" . rtrim(rtrim($signal['bb_middle'], '0'), '.') . "`\n";
        $message .= "  Lower: `$" . rtrim(rtrim($signal['bb_lower'], '0'), '.') . "`\n";
        $bbPosition = $signal['price'] > $signal['bb_upper'] ? "Выше верхней полосы (перекупленность)" :
                     ($signal['price'] < $signal['bb_lower'] ? "Ниже нижней полосы (перепроданность)" : "В пределах полос");
        $message .= "  ↳ {$bbPosition}\n\n";

        // ATR и Volume
        $message .= "ATR: `" . rtrim(rtrim($signal['atr'], '0'), '.') . "`\n";
        $message .= "  ↳ Средняя волатильность за 14 периодов\n";
        $message .= "Volume: `" . rtrim(rtrim($signal['volume_ratio'], '0'), '.') . "x`\n";
        $message .= "  ↳ " . ($signal['volume_ratio'] > 1.5 ? "Высокий объем" : "Низкий объем") . "\n\n";

        // Стоп-лосс и тейк-профит
        $message .= "🎯 *TRADING LEVELS:*\n";
        $message .= "Stop Loss: `$" . rtrim(rtrim($signal['stop_loss'], '0'), '.') . "`\n";
        $message .= "Take Profit: `$" . rtrim(rtrim($signal['take_profit'], '0'), '.') . "`\n";
        $slPercent = (($signal['stop_loss'] - $signal['price']) / $signal['price']) * 100;
        $tpPercent = (($signal['take_profit'] - $signal['price']) / $signal['price']) * 100;
        $message .= "  ↳ SL: " . rtrim(rtrim($slPercent, '0'), '.') . "% | TP: +" . rtrim(rtrim($tpPercent, '0'), '.') . "%\n\n";

        $message .= "Reason: _{$signal['reason']}_\n\n";
        $message .= "Time: `" . now()->addHours(4)->format('Y-m-d H:i:s') . "`";

        return $message;
    }

    protected function sendToMultipleChats(string $message): bool
    {
        $success = true;
        foreach ($this->chatIds as $chatId) {
            try {
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);
                Log::info("Message sent successfully to chat {$chatId}");
            } catch (TelegramSDKException $e) {
                Log::error("Failed to send message to chat {$chatId}: " . $e->getMessage());
                $success = false;
            }
        }
        return $success;
    }

    /**
     * Экранирует специальные символы Markdown для безопасной отправки в Telegram
     */
    protected function escapeMarkdown(string $text): string
    {
        // Экранируем специальные символы Markdown, но НЕ экранируем точки в числах
        // Сначала защищаем числа с точками, потом экранируем, потом возвращаем числа
        $placeholders = [];
        $counter = 0;
        
        // Заменяем числа с точками на плейсхолдеры
        $text = preg_replace_callback('/\d+\.\d+/', function($matches) use (&$placeholders, &$counter) {
            $placeholder = "___NUMBER_{$counter}___";
            $placeholders[$placeholder] = $matches[0];
            $counter++;
            return $placeholder;
        }, $text);
        
        // Экранируем специальные символы Markdown (кроме точки, чтобы не ломать числа)
        $text = str_replace(
            ['_', '*', '[', ']', '(', ')', '~', '`', '>', '#', '+', '-', '=', '|', '{', '}', '!'],
            ['\\_', '\\*', '\\[', '\\]', '\\(', '\\)', '\\~', '\\`', '\\>', '\\#', '\\+', '\\-', '\\=', '\\|', '\\{', '\\}', '\\!'],
            $text
        );
        
        // Возвращаем числа на место
        foreach ($placeholders as $placeholder => $number) {
            $text = str_replace($placeholder, $number, $text);
        }
        
        return $text;
    }

    /**
     * Конвертирует Markdown сообщение в HTML для Telegram
     */
    protected function convertMarkdownToHtml(string $markdown): string
    {
        // Простая конвертация основных элементов Markdown в HTML
        $html = $markdown;
        
        // Заголовки и жирный текст
        $html = preg_replace('/\*\*(.+?)\*\*/', '<b>$1</b>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<i>$1</i>', $html);
        
        // Курсив
        $html = preg_replace('/_(.+?)_/', '<i>$1</i>', $html);
        
        // Код
        $html = preg_replace('/`(.+?)`/', '<code>$1</code>', $html);
        
        // Ссылки
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
        
        return $html;
    }

    protected function formatInstantSignalMessage(array $signal, string $symbol, string $strategy = 'MTF'): string
    {
        $emoji = $signal['type'] === 'BUY' ? '🟢' : '🔴';
        $strengthEmoji = match ($signal['strength']) {
            'STRONG' => '💪',
            default => '🤏',
        };

        // Emoji для стратегий
        $strategyEmoji = match($strategy) {
            'EMA+RSI+MACD' => '🧠',
            'Bollinger+RSI' => '💥',
            'EMA+Stochastic' => '⚡',
            'SuperTrend+VWAP' => '📊',
            'Ichimoku+RSI' => '🔥',
            'Smart Money Concepts' => '💎',
            default => '🔄'
        };

        $message = "{$emoji} *{$strategyEmoji} {$strategy}* {$strengthEmoji}\n\n";
        $message .= "`{$symbol}` *{$signal['type']}* ({$signal['strength']})\n";
        $message .= "Price: `$" . rtrim(rtrim($signal['price'], '0'), '.') . "`\n";
        $message .= "📊 [Open on Binance Futures](https://www.binance.com/en/futures/{$symbol}USDT)\n\n";

        // MTF данные
        $message .= "📊 *MULTI-TIMEFRAME:*\n";
        $message .= "15m RSI: `" . rtrim(rtrim($signal['rsi'], '0'), '.') . "`\n";
        if (isset($signal['htf_rsi'])) {
            $message .= "1h RSI: `" . rtrim(rtrim($signal['htf_rsi'], '0'), '.') . "`\n";
        }
        if (isset($signal['ltf_rsi'])) {
            $message .= "5m RSI: `" . rtrim(rtrim($signal['ltf_rsi'], '0'), '.') . "`\n";
        }
        if (isset($signal['htf_trend'])) {
            $htfEmoji = match($signal['htf_trend']) {
                'BULLISH' => '🟢',
                'BEARISH' => '🔴',
                'NEUTRAL' => '🟡',
                default => '⚪'
            };
            $message .= "HTF Trend: {$htfEmoji} {$signal['htf_trend']}\n";
        }
        $message .= "\n";

        // Стоп-лосс и тейк-профит
        $message .= "🎯 *TRADING LEVELS:*\n";
        $message .= "SL: `$" . rtrim(rtrim($signal['stop_loss'], '0'), '.') . "`\n";
        $message .= "TP: `$" . rtrim(rtrim($signal['take_profit'], '0'), '.') . "`\n";
        $slPercent = (($signal['stop_loss'] - $signal['price']) / $signal['price']) * 100;
        $tpPercent = (($signal['take_profit'] - $signal['price']) / $signal['price']) * 100;
        $message .= "Risk/Reward: " . rtrim(rtrim($slPercent, '0'), '.') . "% / +" . rtrim(rtrim($tpPercent, '0'), '.') . "%\n\n";

        // Краткий обзор
        $message .= "📈 *ANALYSIS:*\n";

        // EMA или VWAP
        if (isset($signal['ema'])) {
            $trend = $signal['price'] > $signal['ema'] ? "↑" : "↓";
            $message .= "EMA50: `$" . rtrim(rtrim($signal['ema'], '0'), '.') . "` {$trend}\n";
        }

        // Bollinger Bands (только если есть)
        if (isset($signal['bb_upper']) && isset($signal['bb_lower'])) {
            $bbPosition = $signal['price'] > $signal['bb_upper'] ? "Above BB" :
                         ($signal['price'] < $signal['bb_lower'] ? "Below BB" : "Inside BB");
            $message .= "BB: {$bbPosition}\n";
        }

        // SuperTrend (только если есть)
        if (isset($signal['supertrend'])) {
            $stEmoji = $signal['supertrend_trend'] === 'UP' ? "🟢" : "🔴";
            $message .= "SuperTrend: {$stEmoji} `$" . rtrim(rtrim($signal['supertrend'], '0'), '.') . "`\n";
        }

        // Smart Money Concepts данные (только если есть)
        if (isset($signal['order_block_high']) && isset($signal['order_block_low'])) {
            $message .= "Order Block: `$" . rtrim(rtrim($signal['order_block_low'], '0'), '.') . "` - `$" . rtrim(rtrim($signal['order_block_high'], '0'), '.') . "`\n";
        }
        if (isset($signal['market_structure'])) {
            // Экранируем специальные символы в market_structure (но не внутри backticks)
            $message .= "Market Structure: `{$signal['market_structure']}`\n";
        }

        // VWAP (только если есть)
        if (isset($signal['vwap'])) {
            $vwapDistance = abs((($signal['price'] - $signal['vwap']) / $signal['vwap']) * 100);
            $message .= "VWAP: `$" . rtrim(rtrim($signal['vwap'], '0'), '.') . "` (Distance: " . rtrim(rtrim($vwapDistance, '0'), '.') . "%)\n";
        }

        // Volume
        if (isset($signal['volume_ratio'])) {
            $volume = $signal['volume_ratio'] > 1.5 ? "High Vol" : "Low Vol";
            $message .= "Volume: {$volume} (" . rtrim(rtrim($signal['volume_ratio'], '0'), '.') . "x)\n";
        }

        $message .= "\n";

        // Экранируем специальные символы Markdown в reason
        // Важно: экранируем ВСЕ символы, так как reason будет внутри курсива (_text_)
        $reason = $signal['reason'] ?? '';
        // Экранируем все специальные символы, включая подчеркивания
        $escapedReason = $this->escapeMarkdown($reason);
        $message .= "_{$escapedReason}_\n";
        $message .= "⚡ `" . now()->addHours(4)->format('H:i:s') . "`";

        return $message;
    }

    protected function sendToInstantBot(string $message): bool
    {
        $success = true;
        foreach ($this->chatIds as $chatId) {
            try {
                $this->instantTelegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);
                Log::info("Instant signal sent successfully to chat {$chatId}");
            } catch (TelegramSDKException $e) {
                Log::error("Failed to send instant signal to chat {$chatId}: " . $e->getMessage());
                $success = false;
            }
        }
        return $success;
    }

    protected function sendToInstantBotSafe(string $message): bool
    {
        // Получаем пользователей с активными подписками (product_id = 1, 2, 4, 5)
        // Проверяем: status = 'active', product_id IN (1, 2, 4, 5), date_from <= now(), date_to >= now()
        $activeUsers = User::whereHas('subscriptions', function ($query) {
                $query->where('status', 'active')
                      ->whereIn('product_id', [1, 2, 4, 5])
                      ->where('date_from', '<=', now())
                      ->where('date_to', '>=', now());
            })
            ->whereNotNull('telegram_chat_id')
            ->where('telegram_chat_id', '!=', '')
            ->pluck('telegram_chat_id')
            ->unique()
            ->values()
            ->toArray();

        if (empty($activeUsers)) {
            Log::info('No active subscriptions found for signal delivery');
            return false;
        }

        $successCount = 0;
        $totalChats = count($activeUsers);

        Log::info("Sending signal to {$totalChats} users with active subscriptions");

        foreach ($activeUsers as $chatId) {
            try {
                // Пробуем сначала с Markdown
                try {
                    $this->instantTelegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $message,
                        'parse_mode' => 'Markdown'
                    ]);
                    Log::info("Instant signal sent successfully to chat {$chatId} (Markdown)");
                    $successCount++;
                } catch (TelegramSDKException $e) {
                    // Если Markdown не работает, пробуем HTML
                    Log::warning("Markdown parse failed for chat {$chatId}, trying HTML: " . $e->getMessage());
                    $htmlMessage = $this->convertMarkdownToHtml($message);
                    $this->instantTelegram->sendMessage([
                        'chat_id' => $chatId,
                        'text' => $htmlMessage,
                        'parse_mode' => 'HTML'
                    ]);
                    Log::info("Instant signal sent successfully to chat {$chatId} (HTML)");
                    $successCount++;
                }
                // Небольшая задержка между отправками, чтобы не превысить лимиты API
                usleep(50000); // 50ms задержка
            } catch (TelegramSDKException $e) {
                Log::warning("Failed to send instant signal to chat {$chatId}: " . $e->getMessage());
                // Не прерываем работу, продолжаем отправку другим чатам
            }
        }

        Log::info("Signal delivery completed: {$successCount}/{$totalChats} successful");

        // Возвращаем true если хотя бы один чат получил сообщение
        return $successCount > 0;
    }

    /**
     * Send welcome message with Web App button
     */
    public function sendWelcomeMessage(int $chatId): bool
    {
        try {
            $webAppUrl = env('APP_URL', 'http://localhost:8000');

            $message = "🤖 *Добро пожаловать в Trading Helper Pro!*\n\n";
            $message .= "📊 *Автоматизированная система анализа криптовалютных рынков*\n\n";
            $message .= "✨ *Возможности бота:*\n";
            $message .= "📈 История торговых сигналов с фильтрацией\n";
            $message .= "⚙️ Настройка параметров стратегий\n";
            $message .= "📊 Live анализ криптовалют в реальном времени\n";
            $message .= "🤖 AI анализ графиков\n";
            $message .= "📱 Удобный веб-интерфейс\n\n";
            $message .= "🚀 *Нажмите кнопку ниже, чтобы открыть приложение и начать работу!*";

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'reply_markup' => json_encode([
                    'inline_keyboard' => [
                        [
                            [
                                'text' => '🚀 Открыть приложение',
                                'web_app' => ['url' => $webAppUrl]
                            ]
                        ]
                    ]
                ])
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send welcome message: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send verification code request message
     */
    public function sendVerificationCodeRequest(int $chatId, string $code): bool
    {
        try {
            $message = "🔐 *Подтверждение номера телефона*\n\n";
            $message .= "Пожалуйста, введите код подтверждения, который показан на странице веб-приложения.\n\n";
            $message .= "Код состоит из 4 цифр.";

            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            return true;
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send verification code request: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send message to specific chat
     */
    public function sendMessageToChat(int $chatId, string $message, string $parseMode = 'Markdown'): bool
    {
        try {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => $parseMode
            ]);
            return true;
        } catch (TelegramSDKException $e) {
            Log::error("Failed to send message to chat {$chatId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send crypto news to Telegram chat
     */
    public function sendCryptoNews(\App\Models\CryptoNews $news): bool
    {
        $chatId = -1003511743710; // News channel chat ID
        
        try {
            // Use instantTelegram bot for sending news (it has access to the channel)
            $bot = $this->instantTelegram;

            // Format message
            $message = "📰 *" . $this->escapeMarkdown($news->title) . "*\n\n";

            if ($news->description) {
                $description = mb_substr($news->description, 0, 300);
                if (mb_strlen($news->description) > 300) {
                    $description .= '...';
                }
                $message .= $this->escapeMarkdown($description) . "\n\n";
            }

            // Determine language for labels
            $isEnglish = $news->language === 'en' || (isset($news->language) && strtolower($news->language) === 'en');
            $coinsLabel = $isEnglish ? 'Coins' : 'Монеты';
            $authorLabel = $isEnglish ? 'Author' : 'Автор';
            $sourceLabel = $isEnglish ? 'Source' : 'Источник';
            $dateLabel = $isEnglish ? 'Date' : 'Дата';
            $readMoreLabel = $isEnglish ? 'Read more' : 'Читать далее';
            $dateFormat = $isEnglish ? 'Y-m-d H:i' : 'd.m.Y H:i';

            // Add coins if available
            if ($news->coin && is_array($news->coin) && !empty($news->coin)) {
                $coins = implode(', ', array_filter($news->coin)); // Убираем пустые значения
                if (!empty($coins)) {
                    $message .= "🪙 *{$coinsLabel}:* `{$coins}`\n";
                }
            }

            // Add creator if available
            if ($news->creator && is_array($news->creator) && !empty($news->creator)) {
                $creators = array_map(function($c) {
                    return strip_tags($c); // Remove HTML tags from creator
                }, array_filter($news->creator)); // Убираем пустые значения
                
                if (!empty($creators)) {
                    $creator = implode(', ', $creators);
                    $creator = mb_substr($creator, 0, 100);
                    $message .= "✍️ *{$authorLabel}:* {$creator}\n";
                }
            }

            // Add source if available
            if ($news->source_name) {
                $message .= "📡 *{$sourceLabel}:* {$news->source_name}\n";
            }

            // Add date
            if ($news->pub_date) {
                $message .= "📅 *{$dateLabel}:* " . $news->pub_date->format($dateFormat) . "\n";
            }

            $message .= "\n🔗 [{$readMoreLabel}]({$news->link})";

            // Check if image is accessible before trying to send it
            $imageUrl = null;
            if ($news->image_url) {
                $imageUrl = $this->checkImageAccessibility($news->image_url);
            }

            // Try to send with photo if available and accessible
            if ($imageUrl) {
                try {
                    $photo = InputFile::create($imageUrl);
                    $bot->sendPhoto([
                        'chat_id' => $chatId,
                        'photo' => $photo,
                        'caption' => $message,
                        'parse_mode' => 'Markdown',
                        'disable_web_page_preview' => false,
                    ]);
                    return true;
                } catch (\Exception $e) {
                    // If photo fails, fallback to text message
                    Log::debug("Failed to send photo for news {$news->id}: " . $e->getMessage());
                }
            }

            // Send text message (with or without photo)
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => false,
            ]);

            return true;

        } catch (TelegramSDKException $e) {
            Log::error("Failed to send crypto news to Telegram: " . $e->getMessage(), [
                'news_id' => $news->id,
                'article_id' => $news->article_id,
                'chat_id' => $chatId
            ]);
            return false;
        } catch (\Exception $e) {
            Log::error("Error sending crypto news: " . $e->getMessage(), [
                'news_id' => $news->id,
                'article_id' => $news->article_id,
                'chat_id' => $chatId
            ]);
            return false;
        }
    }

    /**
     * Check if image URL is accessible
     */
    protected function checkImageAccessibility(string $url): ?string
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 5,
                    'user_agent' => 'Mozilla/5.0 (compatible; TelegramBot/1.0)',
                    'follow_location' => true,
                    'max_redirects' => 3
                ]
            ]);

            $headers = @get_headers($url, 1, $context);
            
            if ($headers === false) {
                return null;
            }

            $statusCode = 0;
            if (is_array($headers[0])) {
                $statusCode = (int) substr($headers[0][0], 9, 3);
            } else {
                $statusCode = (int) substr($headers[0], 9, 3);
            }

            // Check if status code is 200 (OK)
            if ($statusCode === 200) {
                return $url;
            }

            return null;
        } catch (\Exception $e) {
            Log::debug("Failed to check image accessibility for {$url}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Setup bot menu with Web App buttons
     */
    public function setupMenu(array $menuButtons): bool
    {
        try {
            // Set bot commands
            $this->telegram->setMyCommands([
                'commands' => [
                    ['command' => 'start', 'description' => 'Начать работу с ботом'],
                ]
            ]);

            // Set menu button - используем прямой HTTP запрос к Telegram API
            $webAppUrl = env('APP_URL', 'http://localhost:8000');

            // Проверка на HTTPS
            if (!str_starts_with($webAppUrl, 'https://')) {
                throw new \Exception("APP_URL должен использовать HTTPS! Текущий URL: {$webAppUrl}. Для локального тестирования используйте ngrok.");
            }

            $token = $this->telegram->getAccessToken();

            // Используем Http facade для прямого вызова API
            $response = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$token}/setChatMenuButton", [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => '📊 Открыть приложение',
                    'web_app' => [
                        'url' => $webAppUrl
                    ]
                ]
            ]);

            if ($response->successful() && $response->json('ok')) {
                Log::info('Telegram bot menu configured successfully');
                return true;
            } else {
                Log::error('Failed to setup menu: ' . $response->body());
                return false;
            }
        } catch (TelegramSDKException $e) {
            Log::error("Failed to setup menu: " . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error("Failed to setup menu: " . $e->getMessage());
            return false;
        }
    }
}
