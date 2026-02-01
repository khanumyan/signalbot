<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StrategyController extends Controller
{
    /**
     * Display strategy detail page
     */
    public function show(string $strategy)
    {
        $strategies = [
            'mtf' => [
                'name' => 'MTF (Multi-Timeframe) Strategy',
                'icon' => '📈',
                'slug' => 'mtf',
                'description' => 'Мультитаймфреймовая стратегия с анализом на нескольких таймфреймах',
                'timeframe' => '15m (основной), 1h (HTF), 5m (LTF)',
                'indicators' => 'RSI, EMA(20/50), Bollinger Bands',
                'type' => 'Мультитаймфреймовая',
            ],
            'ema-rsi-macd' => [
                'name' => 'EMA + RSI + MACD Strategy',
                'icon' => '🧠',
                'slug' => 'ema-rsi-macd',
                'description' => 'Универсальная трендовая стратегия',
                'timeframe' => '15m',
                'indicators' => 'EMA(20), EMA(50), RSI(14), MACD(12,26,9)',
                'type' => 'Трендовая',
            ],
            'bollinger-rsi' => [
                'name' => 'Bollinger Bands + RSI Strategy',
                'icon' => '💥',
                'slug' => 'bollinger-rsi',
                'description' => 'Контртрендовая стратегия для боковых рынков',
                'timeframe' => '15m',
                'indicators' => 'Bollinger Bands(20, 2), RSI(14)',
                'type' => 'Контртрендовая',
            ],
            'ema-stochastic' => [
                'name' => 'EMA + Stochastic Strategy',
                'icon' => '⚡',
                'slug' => 'ema-stochastic',
                'description' => 'Скальпинговая стратегия для быстрой торговли',
                'timeframe' => '5m',
                'indicators' => 'EMA(9), EMA(21), Stochastic(14,3,3)',
                'type' => 'Скальпинг',
            ],
            'supertrend-vwap' => [
                'name' => 'SuperTrend + VWAP Strategy',
                'icon' => '📊',
                'slug' => 'supertrend-vwap',
                'description' => 'Внутридневная трендовая стратегия',
                'timeframe' => '15m',
                'indicators' => 'SuperTrend(10, 3.0), VWAP, ADX(14), RSI(14), ATR',
                'type' => 'Трендовая',
            ],
            'ichimoku-rsi' => [
                'name' => 'Ichimoku + RSI Strategy',
                'icon' => '☁️',
                'slug' => 'ichimoku-rsi',
                'description' => 'Долгосрочная трендовая стратегия с облачной поддержкой',
                'timeframe' => '1h',
                'indicators' => 'Ichimoku(9,26,52), RSI(14)',
                'type' => 'Долгосрочная трендовая',
            ],
            'smart-money-concepts' => [
                'name' => 'Smart Money Concepts (SMC)',
                'icon' => '💎',
                'slug' => 'smart-money-concepts',
                'description' => 'Продвинутая стратегия на основе Order Blocks и Market Structure',
                'timeframe' => '15m (основной), H4 (для тренда)',
                'indicators' => 'Order Blocks, Market Structure (BOS/CHOCH), Fair Value Gaps, Liquidity Areas, RSI, EMA',
                'type' => 'Продвинутая',
            ],
        ];

        if (!isset($strategies[$strategy])) {
            abort(404);
        }

        return view('strategies.show', [
            'strategy' => $strategies[$strategy],
        ]);
    }
}

