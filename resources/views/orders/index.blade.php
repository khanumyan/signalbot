<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Оформление заказа - Trading Helper Pro</title>
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
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 16px;
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
            margin-bottom: 24px;
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

        .order-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .order-title {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 32px;
            text-align: center;
        }

        .product-section {
            margin-bottom: 40px;
        }

        .product-name {
            font-size: 24px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 16px;
        }

        .product-description {
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .benefits-list {
            list-style: none;
            padding: 0;
            margin-bottom: 32px;
        }

        .benefits-list li {
            padding: 12px 0;
            padding-left: 32px;
            position: relative;
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.6;
        }

        .benefits-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 20px;
        }

        .benefits-list li.excluded::before {
            content: '✗';
            color: #ef4444;
        }

        .price-section {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            margin-bottom: 32px;
        }

        .price-label {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .price-value {
            font-size: 48px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .price-currency {
            font-size: 24px;
            color: #94a3b8;
            margin-left: 8px;
        }

        .warning-section {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 32px;
        }

        .warning-text {
            color: #fca5a5;
            font-size: 14px;
            line-height: 1.6;
        }

        .warning-text a {
            color: #fca5a5;
            text-decoration: underline;
        }

        .warning-text a:hover {
            color: #ffffff;
        }

        .btn-pay {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            border: none;
            border-radius: 12px;
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
        }

        .btn-pay:active {
            transform: translateY(0);
        }

        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .order-card {
                padding: 24px;
            }

            .order-title {
                font-size: 24px;
            }

            .price-value {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="{{ route($backRoute) }}" class="back-button">← Назад</a>
                    <img src="{{ asset('images/Traiding (2).svg') }}" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                    <div>
                        <div class="header-title">💳 Оформление подписки</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Выбор подписки</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">

        <div class="order-card">
            <h1 class="order-title">Оформление подписки</h1>

            <div class="product-section">
                <h2 class="product-name">{{ $product->name ?? 'Подписка на 1 месяц' }}</h2>
                
                <p class="product-description">
                    С этой подпиской вы будете получать все сигналы в нашем боте в течение 1 месяца. 
                    Полный доступ к истории сигналов, включая сегодня и вчера, а также ко всем функциям платформы.
                </p>

                <ul class="benefits-list">
                    <li>Полный доступ ко всем торговым сигналам</li>
                    <li>Сигналы в реальном времени через Telegram</li>
                    <li>История сигналов без ограничений</li>
                    <li class="{{ $fromSignals ? 'excluded' : '' }}">Доступ к настройкам стратегий</li>
                    <li class="{{ $fromSignals ? 'excluded' : '' }}">Live аналитика криптовалют</li>
                    <li>Графики TradingView для анализа</li>
                </ul>
            </div>

            <div class="price-section">
                <div class="price-label">Стоимость подписки</div>
                <div class="price-value">
                    ${{ number_format($price, 2, '.', ' ') }}
                    <span class="price-currency">USD</span>
                </div>
            </div>

            <div class="warning-section">
                <p class="warning-text">
                    ⚠️ <strong>Важно:</strong> Нажимая кнопку "Оплатить", вы подтверждаете, что ознакомились с 
                    <a href="{{ route('landing') }}#terms" target="_blank">условиями использования</a> и согласны с ними. 
                    Помните, что автоматизация и торговые сигналы могут быть не всегда правильными, и торговля криптовалютами 
                    сопряжена с рисками. Мы не несем ответственности за финансовые потери.
                </p>
            </div>

            <button class="btn-pay" id="payButton">
                💳 Оплатить ${{ number_format($price, 2, '.', ' ') }}
            </button>
        </div>
    </div>

    <script>
        // TODO: Здесь будет логика оплаты
        document.getElementById('payButton').addEventListener('click', function() {
            alert('Функция оплаты будет реализована позже');
        });
    </script>
</body>
</html>

