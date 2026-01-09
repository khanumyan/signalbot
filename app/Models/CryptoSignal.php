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
     */
    public static function hasRecentSignal(string $symbol, int $minutes = 60): ?self
    {
        return self::where('symbol', $symbol)
            ->where('signal_time', '>=', Carbon::now()->addHours(4)->subMinutes($minutes))
            ->orderBy('signal_time', 'desc')
            ->first();
    }

    /**
     * Проверяет, нужно ли отправить сигнал (изменилась ли сила или стратегия)
     */
    public static function shouldSendSignal(string $symbol, string $type, string $strength, string $strategy = 'MTF'): bool
    {
        $recentSignal = self::hasRecentSignal($symbol);

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

        // Если все одинаково (тип, сила, стратегия) - не отправляем
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
