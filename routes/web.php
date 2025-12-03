<?php

use Illuminate\Support\Facades\Route;

// Home routes
Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/api/signals/stats', [App\Http\Controllers\HomeController::class, 'stats'])->name('signals.stats');

// Crypto API routes
Route::get('/api/crypto/rsi/all', [App\Http\Controllers\CryptoController::class, 'getAllRsi']);
Route::get('/api/crypto/rsi/{symbol}', [App\Http\Controllers\CryptoController::class, 'getRsiForSymbol']);
Route::get('/api/crypto/klines/{symbol}', [App\Http\Controllers\CryptoController::class, 'getKlinesData']);

// Signal routes
Route::get('/signals', [App\Http\Controllers\SignalController::class, 'index'])->name('signals.index');
Route::get('/signals/{id}', [App\Http\Controllers\SignalController::class, 'show'])->name('signals.show');

// Strategy Settings routes
Route::get('/strategy-settings', [App\Http\Controllers\StrategySettingsController::class, 'index'])->name('strategy-settings.index');
Route::put('/strategy-settings/{strategyName}', [App\Http\Controllers\StrategySettingsController::class, 'update'])->name('strategy-settings.update');
Route::post('/strategy-settings/{strategyName}/reset', [App\Http\Controllers\StrategySettingsController::class, 'reset'])->name('strategy-settings.reset');
Route::get('/api/strategy-settings/{strategyName}', [App\Http\Controllers\StrategySettingsController::class, 'get'])->name('strategy-settings.get');
Route::post('/api/strategy-analysis/analyze', [App\Http\Controllers\StrategySettingsController::class, 'analyze'])->name('strategy-analysis.analyze');

// Chart Analysis routes
Route::get('/chart-analysis', [App\Http\Controllers\ChartAnalysisController::class, 'index'])->name('chart-analysis.index');
Route::post('/api/chart-analysis/analyze', [App\Http\Controllers\ChartAnalysisController::class, 'analyze'])->name('chart-analysis.analyze');
