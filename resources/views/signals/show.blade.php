<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $signal->symbol }} - Crypto AI Trading Bot</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0a2e 50%, #0a0a0a 100%);
            color: #ffffff;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px 16px 100px 16px;
        }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
            padding: 16px;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title {
            font-size: 20px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .back-button {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .back-button:hover {
            background: rgba(30, 41, 59, 0.8);
        }

        /* Cards */
        .card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Analysis Card */
        .analysis-card {
            background: linear-gradient(135deg, rgba(88, 28, 135, 0.4), rgba(157, 23, 77, 0.4));
            border: 2px solid rgba(168, 85, 247, 0.5);
        }

        .crypto-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .crypto-price {
            font-size: 32px;
            font-weight: bold;
        }

        .signal-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }

        .signal-buy {
            background: #10b981;
        }

        .signal-sell {
            background: #ef4444;
        }

        .signal-strength {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        .strength-weak {
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .strength-medium {
            background: rgba(251, 191, 36, 0.3);
            color: #fde047;
        }

        .strength-strong {
            background: rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
        }

        .signal-status {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 12px;
            text-transform: uppercase;
        }

        .status-done {
            background: rgba(16, 185, 129, 0.3);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.5);
        }

        .status-missed {
            background: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.5);
        }

        .status-processing {
            background: rgba(251, 191, 36, 0.3);
            color: #fde047;
            border: 1px solid rgba(251, 191, 36, 0.5);
        }

        /* Trade Levels */
        .trade-levels {
            margin: 16px 0;
        }

        .level {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            padding: 12px;
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .level-entry {
            border: 1px solid rgba(168, 85, 247, 0.4);
        }

        .level-sl {
            border: 2px solid rgba(239, 68, 68, 0.5);
        }

        .level-tp {
            border: 2px solid rgba(16, 185, 129, 0.5);
        }

        .level-label {
            font-size: 12px;
            font-weight: 600;
        }

        .level-value {
            font-size: 20px;
            font-weight: bold;
        }

        /* TradingView Chart */
        .chart-container {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
            overflow: hidden;
        }

        .tradingview-widget-container {
            width: 100%;
            height: 610px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin: 16px 0;
        }

        .stat-item {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .stat-label {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: bold;
        }

        /* Info Section */
        .info-section {
            margin-top: 16px;
            padding: 12px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 8px;
        }

        .info-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #a855f7;
        }

        .info-text {
            font-size: 13px;
            color: #cbd5e1;
            line-height: 1.5;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .tradingview-widget-container {
                height: 400px;
            }
            
            .crypto-header {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="{{ route('signals.index') }}" class="back-button">← Назад к списку</a>
                    <img src="{{ asset('images/erasebg-transformed (1).png') }}" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                    <div>
                        <div class="header-title">📊 Детали сигнала</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">{{ $signal->symbol }} - {{ $signal->signal_time->format('d.m.Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Analysis Card -->
        <div class="card analysis-card">
            <div class="crypto-header">
                <div>
                    <div style="font-size: 20px; font-weight: bold; color: #a855f7;">{{ $signal->symbol }}</div>
                    <div style="font-size: 12px; color: #cbd5e1; margin-top: 4px;">Текущая цена</div>
                    <div class="crypto-price">${{ number_format($signal->price, 2, '.', ' ') }}</div>
                </div>
                <div>
                    <div class="signal-badge signal-{{ strtolower($signal->type) }}">
                        {{ $signal->type === 'BUY' ? '📈 LONG' : '📉 SHORT' }}
                    </div>
                    @if($signal->status)
                        <div style="text-align: center; margin-top: 8px;">
                            <span class="signal-status status-{{ strtolower($signal->status) }}">
                                @if($signal->status === 'DONE') ✅ Выполнен
                                @elseif($signal->status === 'MISSED') ❌ Пропущен
                                @elseif($signal->status === 'PROCESSING') ⏳ В обработке
                                @endif
                            </span>
                        </div>
                    @endif
                    <div style="text-align: center; font-size: 11px; color: #cbd5e1; margin-top: 4px;">
                        Сила: 
                        <span class="signal-strength strength-{{ strtolower($signal->strength) }}">
                            {{ $signal->strength }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-label">RSI</div>
                    <div class="stat-value" style="color: #a855f7;">{{ number_format($signal->rsi, 2) }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">EMA</div>
                    <div class="stat-value" style="color: #3b82f6;">${{ number_format($signal->ema, 2, '.', ' ') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Объем</div>
                    <div class="stat-value" style="color: #ec4899;">{{ number_format($signal->volume_ratio, 2) }}x</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Тренд HTF</div>
                    <div class="stat-value" style="color: #10b981;">{{ $signal->htf_trend }}</div>
                </div>
            </div>

            <!-- Trade Levels -->
            <div class="trade-levels">
                <div style="font-size: 16px; font-weight: bold; margin-bottom: 12px;">🎯 ТОРГОВЫЕ УРОВНИ</div>
                
                <div class="level level-entry">
                    <span class="level-label" style="color: #a855f7;">💰 Вход</span>
                    <span class="level-value">${{ number_format($signal->price, 2, '.', ' ') }}</span>
                </div>

                <div class="level level-sl">
                    <span class="level-label" style="color: #f87171;">🛡️ Stop Loss</span>
                    <span class="level-value" style="color: #f87171;">${{ number_format($signal->stop_loss, 2, '.', ' ') }}</span>
                </div>

                <div class="level level-tp">
                    <span class="level-label" style="color: #34d399;">🎯 Take Profit</span>
                    <span class="level-value" style="color: #34d399;">${{ number_format($signal->take_profit, 2, '.', ' ') }}</span>
                </div>
            </div>

            <!-- Additional Info -->
            <div class="info-section">
                <div class="info-label">📊 Технический анализ</div>
                <div class="info-text">
                    <strong>RSI HTF:</strong> {{ number_format($signal->htf_rsi, 2) }} | 
                    <strong>RSI LTF:</strong> {{ number_format($signal->ltf_rsi, 2) }}<br>
                    <strong>Стратегия:</strong> {{ $signal->strategy }}<br>
                    <strong>Время сигнала:</strong> {{ $signal->signal_time->format('d.m.Y H:i:s') }}
                </div>
            </div>

            @if($signal->reason)
            <div class="info-section" style="margin-top: 12px;">
                <div class="info-label">💡 Обоснование сигнала</div>
                <div class="info-text">{{ $signal->reason }}</div>
            </div>
            @endif
        </div>

        <!-- TradingView Chart -->
        <div class="card">
            <div class="card-title">📈 График {{ $signal->symbol }}</div>
            <div class="chart-container">
                <div class="tradingview-widget-container">
                    <div id="tradingview_chart"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://s3.tradingview.com/tv.js"></script>
    <script>
        new TradingView.widget({
            "width": "100%",
            "height": 610,
            "symbol": "{{ $tradingViewSymbol }}",
            "interval": "60",
            "timezone": "Etc/UTC",
            "theme": "dark",
            "style": "1",
            "locale": "ru",
            "toolbar_bg": "#1e293b",
            "enable_publishing": false,
            "allow_symbol_change": true,
            "container_id": "tradingview_chart",
            "hide_side_toolbar": false,
            "studies": [
                "RSI@tv-basicstudies",
                "EMA@tv-basicstudies"
            ]
        });
    </script>
    
    <!-- Modal Script -->
    <script src="{{ asset('js/modal.js') }}"></script>
    
    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>



