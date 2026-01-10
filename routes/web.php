<?php

use Illuminate\Support\Facades\Route;

// Auth routes (public)
Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\LoginController::class, 'login']);
    Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);
    Route::post('/register/check', [App\Http\Controllers\Auth\RegisterController::class, 'checkVerification'])->name('register.check');
    
    // Phone authentication routes
    Route::get('/phone', [App\Http\Controllers\PhoneAuthController::class, 'showPhoneForm'])->name('phone.auth.show');
    Route::post('/phone/submit', [App\Http\Controllers\PhoneAuthController::class, 'submitPhone'])->name('phone.auth.submit');
    Route::post('/phone/check', [App\Http\Controllers\PhoneAuthController::class, 'checkVerification'])->name('phone.auth.check');
});

// Logout route
Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Public landing page
Route::get('/', [App\Http\Controllers\LandingController::class, 'index'])->name('landing');

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    // Dashboard routes (old home)
    Route::get('/dashboard', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::get('/api/signals/stats', [App\Http\Controllers\HomeController::class, 'stats'])->name('signals.stats');

    // Crypto API routes
    Route::get('/api/crypto/rsi/all', [App\Http\Controllers\CryptoController::class, 'getAllRsi']);
    Route::get('/api/crypto/rsi/{symbol}', [App\Http\Controllers\CryptoController::class, 'getRsiForSymbol']);
    Route::get('/api/crypto/klines/{symbol}', [App\Http\Controllers\CryptoController::class, 'getKlinesData']);

    // Signal routes
    Route::get('/signals', [App\Http\Controllers\SignalController::class, 'index'])->name('signals.index');
    Route::get('/signals/{id}', [App\Http\Controllers\SignalController::class, 'show'])->name('signals.show');
    Route::post('/api/signals/free-trial', [App\Http\Controllers\SignalController::class, 'startFreeTrial'])->name('signals.free-trial');

    // Order routes
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');

    // Strategy Settings routes
    Route::get('/strategy-settings', [App\Http\Controllers\StrategySettingsController::class, 'index'])->name('strategy-settings.index');
    Route::put('/strategy-settings/{strategyName}', [App\Http\Controllers\StrategySettingsController::class, 'update'])->name('strategy-settings.update');
    Route::post('/strategy-settings/{strategyName}/reset', [App\Http\Controllers\StrategySettingsController::class, 'reset'])->name('strategy-settings.reset');
    Route::get('/api/strategy-settings/{strategyName}', [App\Http\Controllers\StrategySettingsController::class, 'get'])->name('strategy-settings.get');
    Route::post('/api/strategy-analysis/analyze', [App\Http\Controllers\StrategySettingsController::class, 'analyze'])->name('strategy-analysis.analyze');
    Route::post('/api/strategy-settings/free-trial', [App\Http\Controllers\StrategySettingsController::class, 'startFreeTrial'])->name('strategy-settings.free-trial');
    Route::post('/api/strategy-settings/buy-subscription', [App\Http\Controllers\StrategySettingsController::class, 'buySubscription'])->name('strategy-settings.buy-subscription');

    // Chart Analysis routes
    Route::get('/chart-analysis', [App\Http\Controllers\ChartAnalysisController::class, 'index'])->name('chart-analysis.index');
    Route::post('/api/chart-analysis/analyze', [App\Http\Controllers\ChartAnalysisController::class, 'analyze'])->name('chart-analysis.analyze');

    // Crypto News routes
    Route::get('/crypto-news', [App\Http\Controllers\CryptoNewsController::class, 'index'])->name('crypto-news.index');
    Route::get('/crypto-news/{id}', [App\Http\Controllers\CryptoNewsController::class, 'show'])->name('crypto-news.show');
});

// Telegram Webhook routes (public, no auth required)
Route::post('/telegram/webhook', [App\Http\Controllers\TelegramWebhookController::class, 'webhook'])->name('telegram.webhook');
Route::post('/telegram/setup-menu', [App\Http\Controllers\TelegramWebhookController::class, 'setupMenu'])->name('telegram.setup-menu');
