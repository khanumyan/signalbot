<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $strategy['name'] }} - Trading Helper Pro</title>
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
            line-height: 1.6;
        }

        .header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(10, 10, 10, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
            z-index: 1000;
            padding: 16px 20px;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: #ffffff;
        }

        .logo-text {
            font-size: 20px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-block;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid rgba(168, 85, 247, 0.5);
            color: #a855f7;
        }

        .btn-outline:hover {
            background: rgba(168, 85, 247, 0.1);
            border-color: #a855f7;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 20px 40px;
        }

        .strategy-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .strategy-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .strategy-title {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 16px;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .strategy-meta {
            display: flex;
            gap: 32px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 24px;
            color: #cbd5e1;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .content-section {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 32px;
        }

        .section-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #a855f7;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-content {
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.8;
        }

        .section-content h3 {
            color: #ffffff;
            font-size: 20px;
            margin-top: 24px;
            margin-bottom: 12px;
        }

        .section-content ul, .section-content ol {
            margin-left: 24px;
            margin-top: 12px;
            margin-bottom: 12px;
        }

        .section-content li {
            margin-bottom: 8px;
        }

        .section-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .section-content table th,
        .section-content table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
        }

        .section-content table th {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
            font-weight: 600;
        }

        .chart-container {
            margin: 32px 0;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(15, 23, 42, 0.8);
            min-height: 500px;
        }

        .code-block {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }

        .highlight-box {
            background: rgba(168, 85, 247, 0.1);
            border-left: 4px solid #a855f7;
            padding: 16px;
            margin: 16px 0;
            border-radius: 4px;
        }

        .warning-box {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 16px;
            margin: 16px 0;
            border-radius: 4px;
        }

        .success-box {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10b981;
            padding: 16px;
            margin: 16px 0;
            border-radius: 4px;
        }

        @media (max-width: 768px) {
            .strategy-title {
                font-size: 32px;
            }

            .content-section {
                padding: 24px;
            }

            .strategy-meta {
                flex-direction: column;
                gap: 16px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <a href="{{ route('landing') }}" class="logo">
                <span class="logo-text">Trading Helper Pro</span>
            </a>
            <div>
                <a href="{{ route('landing') }}" class="btn btn-outline">← Назад</a>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="strategy-header">
            <div class="strategy-icon">{{ $strategy['icon'] }}</div>
            <h1 class="strategy-title">{{ $strategy['name'] }}</h1>
            <p style="font-size: 18px; color: #cbd5e1; max-width: 800px; margin: 0 auto;">
                {{ $strategy['description'] }}
            </p>
            <div class="strategy-meta">
                <div class="meta-item">
                    <span>📊 Тип:</span>
                    <strong>{{ $strategy['type'] }}</strong>
                </div>
                <div class="meta-item">
                    <span>⏱️ Таймфрейм:</span>
                    <strong>{{ $strategy['timeframe'] }}</strong>
                </div>
                <div class="meta-item">
                    <span>📈 Индикаторы:</span>
                    <strong>{{ $strategy['indicators'] }}</strong>
                </div>
            </div>
        </div>

        @include('strategies.details.' . $strategy['slug'])

        <div class="content-section">
            <h2 class="section-title">📊 Визуализация стратегии</h2>
            <p style="margin-bottom: 24px; color: #cbd5e1;">
                Изучите стратегию на интерактивном графике TradingView. Вы можете изменить символ, таймфрейм и добавить индикаторы для лучшего понимания логики работы.
            </p>
            <div class="chart-container">
                <div class="tradingview-widget-container">
                    <div class="tradingview-widget-container__widget"></div>
                    <script type="text/javascript" src="https://s3.tradingview.com/external-embedding/embed-widget-advanced-chart.js" async>
                    {
                        "autosize": true,
                        "symbol": "BINANCE:BTCUSDT",
                        "interval": "{{ $strategy['slug'] === 'ema-stochastic' ? '5' : ($strategy['slug'] === 'ichimoku-rsi' ? '60' : ($strategy['slug'] === 'smart-money-concepts' ? '15' : '15')) }}",
                        "timezone": "Etc/UTC",
                        "theme": "dark",
                        "style": "1",
                        "locale": "ru",
                        "backgroundColor": "rgba(15, 23, 42, 0.8)",
                        "gridColor": "rgba(168, 85, 247, 0.1)",
                        "width": "100%",
                        "height": "600",
                        "hide_side_toolbar": false,
                        "allow_symbol_change": true,
                        "save_image": false,
                        "studies": []
                    }
                    </script>
                </div>
            </div>
            <div class="highlight-box" style="margin-top: 24px;">
                <strong>💡 Совет:</strong> Используйте кнопку "Индикаторы" на графике выше, чтобы добавить индикаторы, используемые в этой стратегии:
                <ul style="margin-top: 8px;">
                    @if($strategy['slug'] === 'mtf')
                        <li>RSI (14)</li>
                        <li>EMA (20, 50)</li>
                        <li>Bollinger Bands (20, 2)</li>
                    @elseif($strategy['slug'] === 'ema-rsi-macd')
                        <li>EMA (20, 50)</li>
                        <li>RSI (14)</li>
                        <li>MACD (12, 26, 9)</li>
                    @elseif($strategy['slug'] === 'bollinger-rsi')
                        <li>Bollinger Bands (20, 2)</li>
                        <li>RSI (14)</li>
                    @elseif($strategy['slug'] === 'ema-stochastic')
                        <li>EMA (9, 21)</li>
                        <li>Stochastic (14, 3, 3)</li>
                    @elseif($strategy['slug'] === 'supertrend-vwap')
                        <li>SuperTrend (10, 3.0)</li>
                        <li>VWAP</li>
                        <li>ADX (14)</li>
                        <li>RSI (14)</li>
                    @elseif($strategy['slug'] === 'ichimoku-rsi')
                        <li>Ichimoku Cloud (9, 26, 52)</li>
                        <li>RSI (14)</li>
                    @elseif($strategy['slug'] === 'smart-money-concepts')
                        <li>RSI (14)</li>
                        <li>EMA (50)</li>
                        <li>Order Blocks (вручную на графике)</li>
                        <li>Market Structure (BOS/CHOCH)</li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</body>
</html>

