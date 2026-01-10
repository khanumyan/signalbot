<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramService;
use App\Models\CryptoSignal;

class CryptoAnalysisCommand extends Command
{
    protected $signature = 'crypto:analyze
                            {--symbol= : Analyze specific symbol}
                            {--interval=15m : Time interval (1m, 5m, 15m, 1h)}
                            {--limit=100 : Number of candles to fetch}
                            {--rsi-period=14 : RSI period}
                            {--ema-period=50 : EMA period for trend filter}
                            {--bb-period=20 : Bollinger Bands period}
                            {--bb-std=2 : Bollinger Bands standard deviation}
                            {--atr-period=14 : ATR period}
                            {--volume-period=20 : Volume average period}
                            {--min-atr=0.5 : Minimum ATR threshold}
                            {--mtf-5m-candles=100 : Number of 5m candles for MTF analysis}
                            {--mtf-1h-candles=100 : Number of 1h candles for MTF analysis}
                            {--output=table : Output format (table, json, csv)}
                            {--telegram : Send signals to Telegram}
                            {--telegram-only : Only send to Telegram, no console output}';

    protected $description = 'Analyze cryptocurrency signals using RSI and multiple filters';

    protected array $analysisSignals = [];
    protected array $analysisErrors = [];
    protected TelegramService $telegramService;

    public function __construct(TelegramService $telegramService)
    {
        parent::__construct();
        $this->telegramService = $telegramService;
    }

    public function handle(): int
    {
        $this->info('🚀 Starting Cryptocurrency Analysis...');
        $this->newLine();
        $this->telegramService->sendAnalysisStartMessage(560);
        $this->info('✅ Start notification sent!');

        // Get parameters
        $symbol = $this->option('symbol');
        $interval = $this->option('interval');
        $limit = (int) $this->option('limit');
        $outputFormat = $this->option('output');
        $sendTelegram = $this->option('telegram');
        $telegramOnly = $this->option('telegram-only');

        // Test Telegram connection if needed
        if ($sendTelegram || $telegramOnly) {
            $this->info('📱 Testing Telegram connection...');
            if (!$this->telegramService->testConnection()) {
                $this->error('❌ Telegram connection failed! Check bot token and network.');
                return Command::FAILURE;
            }
            $this->info('✅ Telegram connection successful!');
            $this->newLine();
        }

        // Get symbols to analyze
        if ($symbol) {
            // Если указан символ, разделяем по запятой и очищаем от пробелов
            $symbols = array_map('trim', explode(',', $symbol));
        } else {
            $symbols = config('crypto_symbols');
        }

        $this->info("📊 Analyzing " . count($symbols) . " symbols with interval: {$interval}");
        $this->newLine();

        $progressBar = $this->output->createProgressBar(count($symbols));
        $progressBar->start();

        foreach ($symbols as $cryptoSymbol) {
            try {
                $this->analyzeSymbol($cryptoSymbol, $interval, $limit);
            } catch (\Exception $e) {
                $this->analysisErrors[$cryptoSymbol] = $e->getMessage();
                Log::error("Analysis error for {$cryptoSymbol}: " . $e->getMessage());
            }

            $progressBar->advance();
            usleep(100000); // 0.1 second
        }

        $progressBar->finish();
        $this->newLine(2);

        // Calculate totals
        $totalSignals = 0;
        if (!empty($this->analysisSignals)) {
            $totalSignals = array_sum(array_map('count', $this->analysisSignals));
        }

        $symbolsWithSignals = count($this->analysisSignals);
        $totalSymbols = count($symbols);

        // Send to Telegram - ONLY notifications to main bot
        if ($sendTelegram || $telegramOnly) {
            // 1. Отправляем данные об ошибках (если есть)
            if (!empty($this->analysisErrors)) {
                $this->info('📱 Sending errors report to Telegram...');
                $this->telegramService->sendErrorsReport($this->analysisErrors);
                $this->info('✅ Errors report sent!');
                $this->newLine();
            }

            // 2. Отправляем summary в основной бот (сигналы уже отправлены в instant bot)
            if (!empty($this->analysisSignals)) {
                $this->info('📱 Sending summary to main bot...');
                // Send summary
                $this->telegramService->sendAnalysisSummary($totalSymbols, $totalSignals, count($this->analysisErrors), $this->analysisErrors);
                $this->info('✅ Summary sent to main bot!');
                $this->info('ℹ️ Signals were already sent to instant signal bot');
            } else {
                // Send "no signals found" message
                $this->telegramService->sendNoSignalsMessage($totalSymbols, count($this->analysisErrors), $this->analysisErrors);
                $this->info('✅ No signals message sent!');
            }
            $this->newLine();
        }

        if (!$telegramOnly) {
            $this->displayResults($outputFormat, $totalSymbols, $symbolsWithSignals, $totalSignals);
        }
        $this->info('📱 Sending completion notification to Telegram...');
        $this->telegramService->sendAnalysisCompleteMessage($totalSymbols, $symbolsWithSignals, $totalSignals, count($this->analysisErrors));
        $this->info('✅ Completion notification sent!');
        return Command::SUCCESS;
    }

    private function analyzeSymbol(string $symbol, string $interval, int $limit): void
    {
        try {
            // Получаем настройки количества свечей
            $candles5m = (int) $this->option('mtf-5m-candles');
            $candles1h = (int) $this->option('mtf-1h-candles');
            $candles15m = (int) $this->option('limit');

            // Получаем данные для всех трех таймфреймов
            $klines15m = $this->fetchKlinesData($symbol, '15m', $candles15m); // Основной ТФ
            $klines1h = $this->fetchKlinesData($symbol, '1h', $candles1h);   // Старший ТФ (HTF)
            $klines5m = $this->fetchKlinesData($symbol, '5m', $candles5m);  // Младший ТФ (LTF)

            if (empty($klines15m) || count($klines15m) < 50) {
                $this->analysisErrors[$symbol] = "Insufficient 15m data";
                return;
            }

            if (empty($klines1h) || count($klines1h) < 50) {
                $this->analysisErrors[$symbol] = "Insufficient 1h data";
                return;
            }

            if (empty($klines5m) || count($klines5m) < 50) {
                $this->analysisErrors[$symbol] = "Insufficient 5m data";
                return;
            }

            // Рассчитываем индикаторы для всех ТФ
            $indicators15m = $this->calculateIndicators($klines15m);
            $indicators1h = $this->calculateIndicators($klines1h);
            $indicators5m = $this->calculateIndicators($klines5m);

            // Применяем MTF логику
            $signals = $this->generateMTFSignals($symbol, $klines15m, $indicators15m, $indicators1h, $indicators5m);

            if (!empty($signals)) {
                $this->analysisSignals[$symbol] = $signals;

                foreach ($signals as $signal) {
                    // Проверяем, нужно ли отправить мгновенный сигнал (только MEDIUM и STRONG)
                    if (in_array($signal['strength'], ['STRONG']) && CryptoSignal::shouldSendSignal($symbol, $signal['type'], $signal['strength'], 'MTF')) {
                        // Отправляем мгновенный сигнал в дополнительный бот
                        $this->telegramService->sendInstantSignal($signal, $symbol, 'MTF');

                        // Сохраняем сигнал в базу данных
                        $this->saveSignalToDatabase($signal, $symbol);
                    } else {
                        if ($signal['strength'] === 'WEAK') {
                            $this->info("⏭️ Skipping WEAK signal for {$symbol}: {$signal['type']} ({$signal['strength']}) - MTF strategy");
                        } else {
                            $this->info("⏭️ Skipping duplicate instant signal for {$symbol}: {$signal['type']} ({$signal['strength']}) - MTF strategy");
                        }
                    }
                    usleep(200000); // 0.2 секунды задержка между сигналами
                }
            }
        } catch (\Exception $e) {
            $this->analysisErrors[$symbol] = $e->getMessage();
            Log::error("MTF Analysis error for {$symbol}: " . $e->getMessage());
        }
    }

    private function fetchKlinesData(string $symbol, string $interval, int $limit): array
    {
        try {
            $response = Http::timeout(10)->get('https://fapi.binance.com/fapi/v1/klines', [
                'symbol' => $symbol . 'USDT',
                'interval' => $interval,
                'limit' => $limit
            ]);

            if (!$response->successful()) {
                $errorBody = $response->body();
                $errorData = json_decode($errorBody, true);
                $errorMsg = $errorData['msg'] ?? 'Unknown API error';
                throw new \Exception("API Error: {$errorMsg}");
            }

            $data = $response->json();
            if (empty($data) || !is_array($data)) {
                throw new \Exception("Empty or invalid response data");
            }

            return $data;
        } catch (\Exception $e) {
            // Перебрасываем исключение для обработки в analyzeSymbol
            throw $e;
        }
    }

    private function calculateIndicators(array $klines): array
    {
        $closes = array_map(fn($kline) => (float) $kline[4], $klines);
        $highs = array_map(fn($kline) => (float) $kline[2], $klines);
        $lows = array_map(fn($kline) => (float) $kline[3], $klines);
        $volumes = array_map(fn($kline) => (float) $kline[5], $klines);

        $rsiPeriod = (int) $this->option('rsi-period');
        $emaPeriod = (int) $this->option('ema-period');
        $bbPeriod = (int) $this->option('bb-period');
        $bbStd = (float) $this->option('bb-std');
        $atrPeriod = (int) $this->option('atr-period');
        $volumePeriod = (int) $this->option('volume-period');

        return [
            'rsi' => $this->calculateRSI($closes, $rsiPeriod),
            'ema' => $this->calculateEMA($closes, $emaPeriod),
            'bb' => $this->calculateBollingerBands($closes, $bbPeriod, $bbStd),
            'atr' => $this->calculateATR($highs, $lows, $closes, $atrPeriod),
            'volume_avg' => $this->calculateVolumeAverage($volumes, $volumePeriod),
            'current_price' => end($closes),
            'current_volume' => end($volumes)
        ];
    }

    private function calculateRSI(array $closes, int $period): float
    {
        if (count($closes) < $period + 1) {
            return 50.0;
        }

        $deltas = [];
        for ($i = 1; $i < count($closes); $i++) {
            $deltas[] = $closes[$i] - $closes[$i - 1];
        }

        $gains = array_map(fn($delta) => max(0, $delta), $deltas);
        $losses = array_map(fn($delta) => max(0, -$delta), $deltas);

        $avgGain = array_sum(array_slice($gains, 0, $period)) / $period;
        $avgLoss = array_sum(array_slice($losses, 0, $period)) / $period;

        for ($i = $period; $i < count($gains); $i++) {
            $avgGain = (($avgGain * ($period - 1)) + $gains[$i]) / $period;
            $avgLoss = (($avgLoss * ($period - 1)) + $losses[$i]) / $period;
        }

        if ($avgLoss == 0) {
            return 100.0;
        }

        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    }

    private function calculateEMA(array $closes, int $period): float
    {
        if (count($closes) < $period) {
            return (float) end($closes);
        }

        $multiplier = 2 / ($period + 1);
        $ema = array_sum(array_slice($closes, 0, $period)) / $period;

        for ($i = $period; $i < count($closes); $i++) {
            $ema = ($closes[$i] * $multiplier) + ($ema * (1 - $multiplier));
        }

        return $ema;
    }

    private function calculateBollingerBands(array $closes, int $period, float $std): array
    {
        if (count($closes) < $period) {
            $price = end($closes);
            return ['upper' => $price, 'middle' => $price, 'lower' => $price];
        }

        $sma = array_sum(array_slice($closes, -$period)) / $period;
        $variance = 0.0;

        for ($i = count($closes) - $period; $i < count($closes); $i++) {
            $variance += pow($closes[$i] - $sma, 2);
        }

        $stdDev = sqrt($variance / $period);

        return [
            'upper' => $sma + ($stdDev * $std),
            'middle' => $sma,
            'lower' => $sma - ($stdDev * $std)
        ];
    }

    private function calculateATR(array $highs, array $lows, array $closes, int $period): float
    {
        if (count($highs) < $period + 1) {
            return 0.0;
        }

        $trueRanges = [];
        for ($i = 1; $i < count($highs); $i++) {
            $tr1 = $highs[$i] - $lows[$i];
            $tr2 = abs($highs[$i] - $closes[$i - 1]);
            $tr3 = abs($lows[$i] - $closes[$i - 1]);
            $trueRanges[] = max($tr1, $tr2, $tr3);
        }

        return array_sum(array_slice($trueRanges, -$period)) / $period;
    }

    private function calculateVolumeAverage(array $volumes, int $period): float
    {
        if (count($volumes) < $period) {
            return (float) end($volumes);
        }
        return array_sum(array_slice($volumes, -$period)) / $period;
    }

    private function generateMTFSignals(
        string $symbol,
        array $klines15m,
        array $indicators15m,
        array $indicators1h,
        array $indicators5m
    ): array {
        $signals = [];

        // Данные 15m (основной ТФ)
        $price15m = $indicators15m['current_price'];
        $rsi15m = $indicators15m['rsi'];
        $ema15m = $indicators15m['ema'];
        $bb15m = $indicators15m['bb'];
        $atr15m = $indicators15m['atr'];
        $volumeRatio15m = $indicators15m['current_volume'] / $indicators15m['volume_avg'];

        // Данные 1h (старший ТФ - HTF)
        $ema1h = $indicators1h['ema'];
        $rsi1h = $indicators1h['rsi'];
        $price1h = $indicators1h['current_price'];

        // Данные 5m (младший ТФ - LTF)
        $rsi5m = $indicators5m['rsi'];
        $ema5m = $indicators5m['ema'];
        $price5m = $indicators5m['current_price'];

        // 1. HTF фильтр тренда
        $htfTrend = $this->getHTFTrend($ema1h, $rsi1h, $price1h);

        // 2. Основной сигнал на 15m
        $baseSignal = $this->getBaseSignal15m($rsi15m, $price15m, $bb15m);

        // 3. Проверяем совместимость с HTF трендом
        $htfAllowed = $this->isSignalAllowedByHTF($baseSignal, $htfTrend);

        // 4. Проверяем подтверждение на 5m
        $ltfConfirmed = $baseSignal ? $this->isSignalConfirmedByLTF($baseSignal, $rsi5m, $price5m, $ema5m) : false;

        // 5. Новая логика: разрешаем слабые сигналы даже без полного MTF подтверждения
        $canSendSignal = false;
        $signalStrength = 'WEAK';

        // Экстремальные RSI (≤20 или ≥80) - отправляем как WEAK, но учитываем HTF тренд
        if (($baseSignal === 'BUY' && $rsi15m <= 20) || ($baseSignal === 'SELL' && $rsi15m >= 80)) {
            // Проверяем совместимость с HTF трендом даже для экстремальных RSI
            if ($this->isSignalAllowedByHTF($baseSignal, $htfTrend)) {
                $canSendSignal = true;
                $signalStrength = 'WEAK';
            }
        }

        // Полное MTF подтверждение - может быть MEDIUM или STRONG
        if ($htfAllowed && $ltfConfirmed) {
            $canSendSignal = true;
            $signalStrength = 'MEDIUM'; // Будет пересчитано в calculateMTFStrength
        }

        if (!$canSendSignal) {
            return $signals; // Не отправляем сигнал
        }

        // 6. Рассчитываем финальную силу сигнала с учетом новой логики
        $finalStrength = $this->calculateMTFStrength(
            $baseSignal,
            $htfTrend,
            $indicators15m,
            $indicators1h,
            $indicators5m,
            $htfAllowed,
            $ltfConfirmed
        );

        // 6. Рассчитываем SL/TP по 15m
        $slTp = $this->calculateStopLossTakeProfit(
            $baseSignal,
            $finalStrength,
            $price15m,
            $atr15m,
            $bb15m
        );

        $signals[] = [
            'type' => $baseSignal,
            'strength' => $finalStrength,
            'rsi' => $rsi15m,
            'price' => $price15m,
            'ema' => $ema15m,
            'bb_upper' => $bb15m['upper'],
            'bb_middle' => $bb15m['middle'],
            'bb_lower' => $bb15m['lower'],
            'atr' => $atr15m,
            'volume_ratio' => $volumeRatio15m,
            'stop_loss' => $slTp['stop_loss'],
            'take_profit' => $slTp['take_profit'],
            'htf_trend' => $htfTrend,
            'htf_rsi' => $rsi1h,
            'ltf_rsi' => $rsi5m,
            'reason' => $this->generateMTFReason($baseSignal, $htfTrend, $rsi15m, $rsi1h, $rsi5m, $price15m, $bb15m, $ema15m)
        ];

        return $signals;
    }

    private function getHTFTrend(float $ema1h, float $rsi1h, float $price1h): string
    {
        // EMA50 ↑ и RSI > 50 → бычий тренд
        if ($price1h > $ema1h && $rsi1h > 50) {
            return 'BULLISH';
        }

        // EMA50 ↓ и RSI < 50 → медвежий тренд
        if ($price1h < $ema1h && $rsi1h < 50) {
            return 'BEARISH';
        }

        // RSI 40–60 и цена рядом с EMA50 → неясный тренд
        if ($rsi1h >= 40 && $rsi1h <= 60 && abs($price1h - $ema1h) / $ema1h < 0.02) {
            return 'UNCLEAR';
        }

        return 'NEUTRAL';
    }

    private function getBaseSignal15m(float $rsi15m, float $price15m, array $bb15m): ?string
    {
        // BUY сигнал на 15m - расширенные условия
        if ($rsi15m <= 30) {
            return 'BUY';
        }

        // SELL сигнал на 15m - расширенные условия
        if ($rsi15m >= 70) {
            return 'SELL';
        }

        return null; // Нет сигнала
    }

    private function isSignalAllowedByHTF(?string $baseSignal, string $htfTrend): bool
    {
        if (!$baseSignal) return false;

        // BUY разрешен только в бычьем или нейтральном тренде
        if ($baseSignal === 'BUY' && ($htfTrend === 'BULLISH' || $htfTrend === 'NEUTRAL')) {
            return true;
        }

        // SELL разрешен только в медвежьем или нейтральном тренде
        if ($baseSignal === 'SELL' && ($htfTrend === 'BEARISH' || $htfTrend === 'NEUTRAL')) {
            return true;
        }

        return false;
    }

    private function isSignalConfirmedByLTF(string $baseSignal, float $rsi5m, float $price5m, float $ema5m): bool
    {
        if ($baseSignal === 'BUY') {
            // Ждём подтверждения: RSI > 30 и цена выше EMA5
            return $rsi5m > 30 && $price5m > $ema5m;
        }

        if ($baseSignal === 'SELL') {
            // Ждём подтверждения: RSI < 70 и цена ниже EMA5
            return $rsi5m < 70 && $price5m < $ema5m;
        }

        return false;
    }

    private function calculateMTFStrength(
        string $baseSignal,
        string $htfTrend,
        array $indicators15m,
        array $indicators1h,
        array $indicators5m,
        bool $htfAllowed = false,
        bool $ltfConfirmed = false
    ): string {
        $rsi15m = $indicators15m['rsi'];
        $rsi5m = $indicators5m['rsi'];

        // Новая логика силы сигнала

        // STRONG: Экстремальные RSI + полное MTF подтверждение
        if (($rsi15m <= 15 || $rsi15m >= 85) && $htfAllowed && $ltfConfirmed) {
            return 'STRONG';
        }

        // STRONG: Экстремальные RSI + HTF подтверждение
        if (($rsi15m <= 15 || $rsi15m >= 85) && $htfAllowed) {
            return 'STRONG';
        }

        // MEDIUM: Умеренные RSI + полное MTF подтверждение
        if (($rsi15m <= 25 || $rsi15m >= 75) && $htfAllowed && $ltfConfirmed) {
            return 'MEDIUM';
        }

        // MEDIUM: Экстремальные RSI без MTF подтверждения
        if ($rsi15m <= 20 || $rsi15m >= 80) {
            return 'MEDIUM';
        }

        // WEAK: Все остальные случаи
        return 'WEAK';
    }

    private function generateMTFReason(
        string $signalType,
        string $htfTrend,
        float $rsi15m,
        float $rsi1h,
        float $rsi5m,
        float $price15m,
        array $bb15m,
        float $ema15m
    ): string {
        $reason = "MTF Signal: ";

        if ($signalType === 'BUY') {
            $reason .= "15m RSI {$rsi15m} ≤ 30 (перепроданность)";
        } else {
            $reason .= "15m RSI {$rsi15m} ≥ 70 (перекупленность)";
        }

        $reason .= " | HTF: {$htfTrend} (RSI {$rsi1h})";
        $reason .= " | LTF: RSI {$rsi5m}";

        if ($price15m <= $bb15m['lower']) {
            $reason .= " + Price ≤ BB Lower";
        } elseif ($price15m >= $bb15m['upper']) {
            $reason .= " + Price ≥ BB Upper";
        }

        return $reason;
    }

    private function getBaseStrengthByRSI(float $rsi): string
    {
        if ($rsi <= 20) return 'STRONG';
        if ($rsi >= 80) return 'STRONG';
        if ($rsi >= 21 && $rsi <= 30) return 'MEDIUM';
        if ($rsi >= 71 && $rsi <= 79) return 'MEDIUM';
        return 'WEAK';
    }

    private function getSignalTypeByRSI(float $rsi): string
    {
        if ($rsi <= 30) return 'BUY';
        if ($rsi >= 71) return 'SELL';
        return 'HOLD';
    }

    private function calculateAdvancedSignalStrength(
        string $signalType,
        string $baseStrength,
        float $currentPrice,
        array $bb,
        float $volumeRatio,
        float $atr,
        float $ema
    ): string {
        $strength = $baseStrength;

        // Bollinger Bands усиливает сигнал
        if ($signalType === 'BUY' && $currentPrice <= $bb['lower']) {
            $strength = $this->upgradeStrength($strength);
        } elseif ($signalType === 'SELL' && $currentPrice >= $bb['upper']) {
            $strength = $this->upgradeStrength($strength);
        }

        // Volume подтверждает сигнал
        if ($volumeRatio >= 2.0) {
            $strength = $this->upgradeStrength($strength);
        } elseif ($volumeRatio < 1.0) {
            $strength = $this->downgradeStrength($strength);
        }

        // ATR показывает волатильность
        $atrLevel = $this->getATRLevel($atr, $currentPrice);
        if ($atrLevel === 'LOW') {
            $strength = $this->downgradeStrength($strength);
        }

        // EMA50 — глобальный тренд
        if ($signalType === 'BUY' && $currentPrice < $ema) {
            $strength = $this->downgradeStrength($strength);
        } elseif ($signalType === 'SELL' && $currentPrice > $ema) {
            $strength = $this->downgradeStrength($strength);
        }

        return $strength;
    }

    private function upgradeStrength(string $strength): string
    {
        return match ($strength) {
            'WEAK' => 'MEDIUM',
            'MEDIUM' => 'STRONG',
            default => 'STRONG'
        };
    }

    private function downgradeStrength(string $strength): string
    {
        return match ($strength) {
            'STRONG' => 'MEDIUM',
            'MEDIUM' => 'WEAK',
            default => 'WEAK'
        };
    }

    private function getATRLevel(float $atr, float $price): string
    {
        $atrPercent = ($atr / $price) * 100;
        if ($atrPercent >= 3.0) return 'HIGH';
        if ($atrPercent >= 1.5) return 'MEDIUM';
        return 'LOW';
    }

    private function calculateStopLossTakeProfit(
        string $signalType,
        string $strength,
        float $entryPrice,
        float $atr,
        array $bb
    ): array {
        if ($signalType === 'BUY') {
            return $this->calculateBuySLTP($strength, $entryPrice, $atr, $bb);
        } else {
            return $this->calculateSellSLTP($strength, $entryPrice, $atr, $bb);
        }
    }

    private function calculateBuySLTP(string $strength, float $entryPrice, float $atr, array $bb): array
    {
        $multipliers = match ($strength) {
            'STRONG' => ['sl' => 2.0, 'tp' => 3.0],
            'MEDIUM' => ['sl' => 1.5, 'tp' => 2.0],
            default => ['sl' => 1.0, 'tp' => 1.0]
        };

        $sl = min($entryPrice - ($multipliers['sl'] * $atr), $bb['lower']);
        $tp = $entryPrice + ($multipliers['tp'] * $atr);

        return [
            'stop_loss' => $sl,
            'take_profit' => $tp
        ];
    }

    private function calculateSellSLTP(string $strength, float $entryPrice, float $atr, array $bb): array
    {
        $multipliers = match ($strength) {
            'STRONG' => ['sl' => 2.0, 'tp' => 3.0],
            'MEDIUM' => ['sl' => 1.5, 'tp' => 2.0],
            default => ['sl' => 1.0, 'tp' => 1.0]
        };

        $sl = max($entryPrice + ($multipliers['sl'] * $atr), $bb['upper']);
        $tp = $entryPrice - ($multipliers['tp'] * $atr);

        return [
            'stop_loss' => (float)$sl,
            'take_profit' => (float)$tp
        ];
    }

    private function generateSignalReason(string $signalType, float $rsi, float $price, array $bb, float $ema): string
    {
        $reason = '';

        if ($signalType === 'BUY') {
            $reason = "RSI {$rsi} ≤ 30 (перепроданность)";
            if ($price <= $bb['lower']) {
                $reason .= " + Цена ≤ Bollinger Lower";
            }
            if ($price < $ema) {
                $reason .= " + Краткосрочный сигнал (цена < EMA50)";
            }
        } else {
            $reason = "RSI {$rsi} ≥ 71 (перекупленность)";
            if ($price >= $bb['upper']) {
                $reason .= " + Цена ≥ Bollinger Upper";
            }
            if ($price > $ema) {
                $reason .= " + Фиксация прибыли (цена > EMA50)";
            }
        }

        return $reason;
    }

    private function displayResults(string $format, int $totalSymbols, int $symbolsWithSignals, int $totalSignals): void
    {
        $this->info("📈 Analysis Complete!");
        $this->info("Total symbols analyzed: {$totalSymbols}");
        $this->info("Symbols with signals: {$symbolsWithSignals}");
        $this->info("Total signals found: {$totalSignals}");
        $this->info("Errors: " . count($this->analysisErrors));
        $this->newLine();

        if ($format === 'json') {
            $this->displayJsonResults();
        } elseif ($format === 'csv') {
            $this->displayCsvResults();
        } else {
            $this->displayTableResults();
        }
    }

    private function displayTableResults(): void
    {
        if (empty($this->analysisSignals)) {
            $this->warn('No signals found!');
            return;
        }

        $headers = ['Symbol', 'Type', 'Strength', 'RSI', 'Price', 'EMA', 'BB Level', 'ATR', 'Volume Ratio', 'Reason'];
        $rows = [];

        foreach ($this->analysisSignals as $symbol => $signals) {
            foreach ($signals as $signal) {
                $rows[] = [
                    $symbol,
                    $signal['type'],
                    $signal['strength'],
                    $signal['rsi'],
                    number_format($signal['price'], 2),
                    number_format($signal['ema'], 2),
                    number_format($signal['bb_lower'] ?? $signal['bb_upper'], 2),
                    $signal['atr'],
                    $signal['volume_ratio'],
                    substr($signal['reason'], 0, 50) . '...'
                ];
            }
        }

        $this->table($headers, $rows);
    }

    private function displayJsonResults(): void
    {
        $output = [
            'timestamp' => now()->addHours(4)->toISOString(),
            'total_signals' => array_sum(array_map('count', $this->analysisSignals)),
            'signals' => $this->analysisSignals,
            'errors' => $this->analysisErrors
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    private function displayCsvResults(): void
    {
        $this->line('Symbol,Type,Strength,RSI,Price,EMA,BB_Level,ATR,Volume_Ratio,Reason');

        foreach ($this->analysisSignals as $symbol => $signals) {
            foreach ($signals as $signal) {
                $this->line(sprintf(
                    '%s,%s,%s,%.2f,%.2f,%.2f,%.2f,%.4f,%.2f,"%s"',
                    $symbol,
                    $signal['type'],
                    $signal['strength'],
                    $signal['rsi'],
                    $signal['price'],
                    $signal['ema'],
                    $signal['bb_lower'] ?? $signal['bb_upper'],
                    $signal['atr'],
                    $signal['volume_ratio'],
                    $signal['reason']
                ));
            }
        }
    }

    /**
     * Сохраняет сигнал в базу данных
     */
    private function saveSignalToDatabase(array $signal, string $symbol): void
    {
        try {
            CryptoSignal::saveSignal([
                'symbol' => $symbol,
                'strategy' => 'MTF',
                'type' => $signal['type'],
                'strength' => $signal['strength'],
                'price' => $signal['price'],
                'rsi' => $signal['rsi'],
                'ema' => $signal['ema'],
                'stop_loss' => $signal['stop_loss'],
                'take_profit' => $signal['take_profit'],
                'volume_ratio' => $signal['volume_ratio'],
                'htf_trend' => $signal['htf_trend'],
                'htf_rsi' => $signal['htf_rsi'],
                'ltf_rsi' => $signal['ltf_rsi'],
                'reason' => $signal['reason']
            ]);

            $this->info("💾 Signal saved to database: {$symbol} {$signal['type']} ({$signal['strength']})");
        } catch (\Exception $e) {
            $this->error("❌ Failed to save signal to database: " . $e->getMessage());
            Log::error("Failed to save signal to database", [
                'symbol' => $symbol,
                'signal' => $signal,
                'error' => $e->getMessage()
            ]);
        }
    }
}
