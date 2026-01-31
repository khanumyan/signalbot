<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CryptoSignal extends Model
{
    protected $fillable = [
        'symbol',
        'strategy',
        'type',
        'strength',
        'price',
        'rsi',
        'ema',
        'stop_loss',
        'take_profit',
        'volume_ratio',
        'htf_trend',
        'htf_rsi',
        'ltf_rsi',
        'reason',
        'sent_to_telegram',
        'signal_time',
        'status'
    ];

    protected $casts = [
        'price' => 'decimal:10',
        'rsi' => 'decimal:4',
        'ema' => 'decimal:10',
        'stop_loss' => 'decimal:10',
        'take_profit' => 'decimal:10',
        'volume_ratio' => 'decimal:4',
        'htf_rsi' => 'decimal:4',
        'ltf_rsi' => 'decimal:4',
            'sent_to_telegram' => 'boolean',
            'signal_time' => 'datetime',
            'status' => 'string'
        ];

    /**
     * Проверяет, был ли сигнал для данного символа в последние минуты
     * Новые лимиты: STRONG - 90 минут, MEDIUM - 120 минут
     */
    public static function hasRecentSignal(string $symbol, string $strength = 'STRONG', int $customMinutes = null): ?self
    {
        // Определяем лимит по силе сигнала
        $minutes = $customMinutes ?? match($strength) {
            'STRONG' => 90,  // 90 минут для STRONG
            'MEDIUM' => 120, // 120 минут для MEDIUM
            default => 60    // 60 минут для остальных (на всякий случай)
        };

        return self::where('symbol', $symbol)
            ->where('signal_time', '>=', Carbon::now()->addHours(4)->subMinutes($minutes))
            ->orderBy('signal_time', 'desc')
            ->first();
    }

    /**
     * Проверяет, нужно ли отправить сигнал (изменилась ли сила или стратегия)
     * Улучшенная логика: разрешает повтор если RSI стал более экстремальным или усилился HTF
     */
    public static function shouldSendSignal(string $symbol, string $type, string $strength, string $strategy = 'MTF', float $currentRsi = null, string $htfTrend = null): bool
    {
        $recentSignal = self::hasRecentSignal($symbol, $strength);

        // Если нет недавних сигналов - отправляем
        if (!$recentSignal) {
            return true;
        }

        // Если тип изменился - отправляем
        if ($recentSignal->type !== $type) {
            return true;
        }

        // Если сила изменилась - отправляем
        if ($recentSignal->strength !== $strength) {
            return true;
        }

        // Если стратегия изменилась - отправляем
        if ($recentSignal->strategy !== $strategy) {
            return true;
        }

        // 🔥 НОВОЕ: Проверка на более экстремальный RSI
        if ($currentRsi !== null && $recentSignal->rsi !== null) {
            $recentRsi = (float) $recentSignal->rsi;
            
            // BUY: текущий RSI более экстремальный (меньше)
            if ($type === 'BUY' && $currentRsi < $recentRsi) {
                return true;
            }
            
            // SELL: текущий RSI более экстремальный (больше)
            if ($type === 'SELL' && $currentRsi > $recentRsi) {
                return true;
            }
        }

        // 🔥 НОВОЕ: Проверка на усиление HTF тренда
        if ($htfTrend !== null && $recentSignal->htf_trend !== null) {
            $recentHtfTrend = $recentSignal->htf_trend;
            
            // Усиление тренда: NEUTRAL → BULLISH/BEARISH
            if ($recentHtfTrend === 'NEUTRAL' && ($htfTrend === 'BULLISH' || $htfTrend === 'BEARISH')) {
                return true;
            }
            
            // Усиление для BUY: NEUTRAL → BULLISH
            if ($type === 'BUY' && $recentHtfTrend === 'NEUTRAL' && $htfTrend === 'BULLISH') {
                return true;
            }
            
            // Усиление для SELL: NEUTRAL → BEARISH
            if ($type === 'SELL' && $recentHtfTrend === 'NEUTRAL' && $htfTrend === 'BEARISH') {
                return true;
            }
        }

        // Если все одинаково (тип, сила, стратегия, RSI не улучшился, HTF не усилился) - не отправляем
        return false;
    }

    /**
     * Сохраняет новый сигнал
     */
    public static function saveSignal(array $signalData): self
    {
        return self::create([
            'symbol' => $signalData['symbol'],
            'strategy' => $signalData['strategy'] ?? 'MTF',
            'type' => $signalData['type'],
            'strength' => $signalData['strength'],
            'price' => $signalData['price'],
            'rsi' => $signalData['rsi'],
            'ema' => $signalData['ema'],
            'stop_loss' => $signalData['stop_loss'],
            'take_profit' => $signalData['take_profit'],
            'volume_ratio' => $signalData['volume_ratio'],
            'htf_trend' => $signalData['htf_trend'],
            'htf_rsi' => $signalData['htf_rsi'],
            'ltf_rsi' => $signalData['ltf_rsi'],
            'reason' => $signalData['reason'],
            'signal_time' => now()->addHours(4)
        ]);
    }
}
