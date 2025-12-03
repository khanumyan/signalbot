<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStrategySetting extends Model
{
    protected $fillable = [
        'user_identifier',
        'strategy_name',
        'is_active',
        'parameters',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'parameters' => 'array'
    ];

    /**
     * Get default parameters for a strategy
     */
    public static function getDefaultParameters(string $strategyName): array
    {
        $defaults = [
            'MTF' => [
                'rsi_period' => 14,
                'rsi_buy_threshold' => 30,
                'rsi_sell_threshold' => 70,
                'rsi_extreme_buy' => 20,
                'rsi_extreme_sell' => 80,
                'ema_period' => 50,
                'bb_period' => 20,
                'bb_std_dev' => 2,
                'atr_period' => 14,
                'stop_loss_multiplier' => 1.5,
                'take_profit_multiplier' => 2.5,
            ],
            'EMA+RSI+MACD' => [
                'rsi_period' => 14,
                'rsi_buy_max' => 70,
                'rsi_sell_min' => 30,
                'ema_fast' => 20,
                'ema_slow' => 50,
                'macd_fast' => 12,
                'macd_slow' => 26,
                'macd_signal' => 9,
                'atr_period' => 14,
                'stop_loss_multiplier' => 1.5,
                'take_profit_multiplier' => 2.0,
            ],
            'Bollinger+RSI' => [
                'rsi_period' => 14,
                'rsi_buy_threshold' => 30,
                'rsi_sell_threshold' => 70,
                'bb_period' => 20,
                'bb_std_dev' => 2,
                'atr_period' => 14,
                'stop_loss_multiplier' => 1.5,
                'take_profit_multiplier' => 2.0,
            ],
            'EMA+Stochastic' => [
                'ema_fast' => 9,
                'ema_slow' => 21,
                'stoch_k' => 14,
                'stoch_d' => 3,
                'stoch_smooth' => 3,
                'stoch_buy_exit' => 20,
                'stoch_sell_exit' => 80,
                'atr_period' => 14,
                'stop_loss_multiplier' => 1.0,
                'take_profit_multiplier' => 1.5,
            ],
            'SuperTrend+VWAP' => [
                'supertrend_period' => 10,
                'supertrend_multiplier' => 3,
                'atr_period' => 14,
                'stop_loss_multiplier' => 1.5,
                'take_profit_multiplier' => 2.0,
            ],
            'Ichimoku+RSI' => [
                'rsi_period' => 14,
                'rsi_buy_min' => 40,
                'rsi_buy_max' => 70,
                'rsi_sell_min' => 30,
                'rsi_sell_max' => 60,
                'tenkan_period' => 9,
                'kijun_period' => 26,
                'senkou_b_period' => 52,
                'atr_period' => 14,
                'stop_loss_multiplier' => 1.5,
                'take_profit_multiplier' => 2.0,
            ],
        ];

        return $defaults[$strategyName] ?? [];
    }

    /**
     * Get or create settings for a strategy for a specific user
     */
    public static function getOrCreate(string $strategyName, ?string $userIdentifier = null): self
    {
        $setting = self::where('user_identifier', $userIdentifier)
            ->where('strategy_name', $strategyName)
            ->first();
        
        if (!$setting) {
            $setting = self::create([
                'user_identifier' => $userIdentifier,
                'strategy_name' => $strategyName,
                'is_active' => true,
                'parameters' => self::getDefaultParameters($strategyName),
                'description' => "User settings for {$strategyName} strategy"
            ]);
        }
        
        return $setting;
    }

    /**
     * Get parameter value or default
     */
    public function getParameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Update parameter
     */
    public function setParameter(string $key, $value): void
    {
        $parameters = $this->parameters ?? [];
        $parameters[$key] = $value;
        $this->parameters = $parameters;
        $this->save();
    }
}
