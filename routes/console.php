<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*
||--------------------------------------------------------------------------
|| Available Trading Strategy Commands
||--------------------------------------------------------------------------
||
|| 1. crypto:analyze           - Original MTF (Multi-TimeFrame) strategy
|| 2. crypto:ema-rsi-macd      - 🧠 EMA + RSI + MACD (universal trend-following)
|| 3. crypto:bollinger-rsi     - 💥 Bollinger Bands + RSI (counter-trend bounces)
|| 4. crypto:ema-stochastic    - ⚡ EMA(9/21) + Stochastic (impulse scalping)
||| 5. crypto:supertrend-vwap   - 📊 SuperTrend + VWAP (intraday trending) [ВРЕМЕННО ОТКЛЮЧЕНО]
|| 6. crypto:ichimoku-rsi      - 🔥 Ichimoku + RSI (trend with cloud support)
|| 7. crypto:smart-money-concepts - 💎 Smart Money Concepts (SMC with Order Blocks, BOS/CHOCH, FVG)
||
|| All commands support options: --symbol, --interval, --telegram, --telegram-only
||
*/

// Основное расписание - каждые 12 минут (MTF strategy)
Schedule::command('crypto:analyze --telegram-only')
    ->cron('*/12 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// EMA + RSI + MACD strategy - каждые 15 минут
Schedule::command('crypto:ema-rsi-macd --telegram-only')
    ->cron('*/15 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// Bollinger + RSI strategy - каждые 20 минут
Schedule::command('crypto:bollinger-rsi --telegram-only')
    ->cron('*/20 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// EMA + Stochastic strategy - каждые 10 минут (скальпинг)
Schedule::command('crypto:ema-stochastic --telegram-only --interval=5m')
    ->cron('*/10 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// SuperTrend + VWAP strategy - каждые 30 минут
// ВРЕМЕННО ОТКЛЮЧЕНО
// Schedule::command('crypto:supertrend-vwap --telegram-only')
//     ->cron('*/30 * * * *')
//     ->withoutOverlapping()
//     ->runInBackground()
//     ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// Ichimoku + RSI strategy - каждый час
//Schedule::command('crypto:ichimoku-rsi --telegram-only --interval=1h')
//    ->hourly()
//    ->withoutOverlapping()
//    ->runInBackground()
//    ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// Smart Money Concepts strategy - каждые 25 минут
Schedule::command('crypto:smart-money-concepts --telegram-only --interval=15m')
    ->cron('*/25 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_analysis.log'));

// Crypto News (Russian) - каждые 30 минут
Schedule::command('crypto:fetch-news')
    ->cron('*/30 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_news.log'));

// Crypto News (English) - каждые 30 минут
Schedule::command('crypto:fetch-news-en')
    ->cron('*/30 * * * *')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crypto_news.log'));

// Check Signal Status - каждый день в 12:00 и 00:00
Schedule::command('signals:check-status')
    ->dailyAt('12:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/signal_status.log'));

Schedule::command('signals:check-status')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/signal_status.log'));
