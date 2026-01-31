<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CryptoSignal;

class ShowSignalsCommand extends Command
{
    protected $signature = 'signals:show
                            {--symbol= : Show signals for specific symbol}
                            {--limit=10 : Number of signals to show}
                            {--days=7 : Show signals from last N days}';

    protected $description = 'Show saved cryptocurrency signals from database';

    public function handle()
    {
        $symbol = $this->option('symbol');
        $limit = (int) $this->option('limit');
        $days = (int) $this->option('days');

        $query = CryptoSignal::query()
            ->where('signal_time', '>=', now()->addHours(4)->subDays($days))
            ->orderBy('signal_time', 'desc');

        if ($symbol) {
            $query->where('symbol', strtoupper($symbol));
        }

        $signals = $query->limit($limit)->get();

        if ($signals->isEmpty()) {
            $this->info("📊 No signals found" . ($symbol ? " for {$symbol}" : "") . " in the last {$days} days.");
            return;
        }

        $this->info("📊 Found {$signals->count()} signals" . ($symbol ? " for {$symbol}" : "") . ":");
        $this->newLine();

        $headers = ['Time', 'Symbol', 'Strategy', 'Type', 'Strength', 'Price', 'RSI', 'SL', 'TP', 'HTF Trend'];
        $rows = [];

        foreach ($signals as $signal) {
            // Emoji для стратегий
            $strategyEmoji = match($signal->strategy) {
                'EMA+RSI+MACD' => '🧠',
                'Bollinger+RSI' => '💥',
                'EMA+Stochastic' => '⚡',
                'SuperTrend+VWAP' => '📊',
                'Ichimoku+RSI' => '🔥',
                'Smart Money Concepts' => '💎',
                default => '🔄'
            };

            $rows[] = [
                $signal->signal_time->format('H:i:s'),
                $signal->symbol,
                $strategyEmoji . ' ' . $signal->strategy,
                $signal->type,
                $signal->strength,
                '$' . number_format($signal->price, 8),
                number_format($signal->rsi, 2),
                '$' . number_format($signal->stop_loss, 8),
                '$' . number_format($signal->take_profit, 8),
                $signal->htf_trend
            ];
        }

        $this->table($headers, $rows);

        // Статистика
        $this->newLine();
        $this->info("📈 Statistics:");
        $this->line("• Total signals: " . CryptoSignal::count());
        $this->line("• BUY signals: " . CryptoSignal::where('type', 'BUY')->count());
        $this->line("• SELL signals: " . CryptoSignal::where('type', 'SELL')->count());
        $this->line("• STRONG signals: " . CryptoSignal::where('strength', 'STRONG')->count());
        $this->line("• MEDIUM signals: " . CryptoSignal::where('strength', 'MEDIUM')->count());
        $this->line("• WEAK signals: " . CryptoSignal::where('strength', 'WEAK')->count());
        
        // Статистика по стратегиям
        $this->newLine();
        $this->info("📊 By Strategy:");
        $strategiesStats = CryptoSignal::selectRaw('strategy, COUNT(*) as count')
            ->groupBy('strategy')
            ->orderByDesc('count')
            ->get();
        
        foreach ($strategiesStats as $stat) {
            $emoji = match($stat->strategy) {
                'EMA+RSI+MACD' => '🧠',
                'Bollinger+RSI' => '💥',
                'EMA+Stochastic' => '⚡',
                'SuperTrend+VWAP' => '📊',
                'Ichimoku+RSI' => '🔥',
                default => '🔄'
            };
            $this->line("• {$emoji} {$stat->strategy}: {$stat->count}");
        }
    }
}
