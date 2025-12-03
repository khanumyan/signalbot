<?php

namespace App\Services;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected Api $telegram;
    protected Api $instantTelegram; // Дополнительный бот для мгновенных сигналов
    protected array $chatIds;

    public function __construct()
    {
        $token = '7828142924:AAFwcIOy7zS5PYZcZMFvmEKN7K2Pou7DY3k';
        $this->telegram = new Api($token);

        // Дополнительный бот для мгновенных сигналов
        $instantToken = '8299475505:AAEErEGhxriO9rmBlFE0MiMYAi6vKcbgN84';
        $this->instantTelegram = new Api($instantToken);

        $this->chatIds = [6058842416, 5480079445]; // Два пользователя

        // Дополнительные чаты для мгновенных сигналов
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

        $message .= "_{$signal['reason']}_\n";
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
        $successCount = 0;
        $totalChats = count($this->instantChatIds);

        foreach ($this->instantChatIds as $chatId) {
            try {
                $this->instantTelegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown'
                ]);
                Log::info("Instant signal sent successfully to chat {$chatId}");
                $successCount++;
            } catch (TelegramSDKException $e) {
                Log::warning("Failed to send instant signal to chat {$chatId}: " . $e->getMessage());
                // Не прерываем работу, продолжаем отправку другим чатам
            }
        }

        // Возвращаем true если хотя бы один чат получил сообщение
        return $successCount > 0;
    }
}
