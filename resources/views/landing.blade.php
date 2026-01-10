<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Traiding Helper Pro - Автоматизированные торговые сигналы</title>
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

        /* Header */
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

        .logo img {
            height: 60px;
            width: auto;
            filter: drop-shadow(0 2px 8px rgba(168, 85, 247, 0.4));
        }

        .logo-text {
            font-size: 20px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-actions {
            display: flex;
            gap: 12px;
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

        .btn-primary {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
        }

        /* Hero Section */
        .hero {
            padding: 140px 20px 80px;
            text-align: center;
            background: linear-gradient(180deg, rgba(10, 10, 10, 0.5) 0%, rgba(26, 10, 46, 0.3) 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(168, 85, 247, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(236, 72, 153, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            max-width: 900px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: bold;
            margin-bottom: 24px;
            background: linear-gradient(to right, #a855f7, #ec4899, #fbbf24);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .hero p {
            font-size: 20px;
            color: #cbd5e1;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero .btn {
            padding: 16px 32px;
            font-size: 16px;
        }

        /* Features Section */
        .features {
            padding: 80px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            font-size: 40px;
            font-weight: bold;
            margin-bottom: 16px;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            font-size: 18px;
            color: #94a3b8;
            margin-bottom: 60px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 32px;
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            padding: 32px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            border-color: rgba(168, 85, 247, 0.6);
            box-shadow: 0 12px 32px rgba(168, 85, 247, 0.2);
        }

        .feature-icon {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .feature-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #ffffff;
        }

        .feature-description {
            color: #cbd5e1;
            line-height: 1.6;
        }

        /* About Section */
        .about {
            padding: 80px 20px;
            background: rgba(15, 23, 42, 0.4);
        }

        .about-content {
            max-width: 1000px;
            margin: 0 auto;
        }

        .about-text {
            font-size: 18px;
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 32px;
        }

        .about-list {
            list-style: none;
            padding: 0;
        }

        .about-list li {
            padding: 16px 0;
            padding-left: 32px;
            position: relative;
            color: #cbd5e1;
            font-size: 16px;
            line-height: 1.6;
        }

        .about-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #10b981;
            font-weight: bold;
            font-size: 20px;
        }

        /* Terms Section */
        .terms {
            padding: 80px 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .terms-content {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 20px;
            padding: 40px;
        }

        .terms-title {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #fca5a5;
        }

        .terms-text {
            color: #fca5a5;
            line-height: 1.8;
            font-size: 16px;
        }

        .terms-text p {
            margin-bottom: 16px;
        }

        .terms-text strong {
            color: #ffffff;
        }

        /* Strategies Section */
        .strategies {
            padding: 80px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .strategies-content {
            width: 100%;
        }

        .strategies-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-top: 40px;
        }

        .strategy-item {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s ease;
        }

        .strategy-item:hover {
            transform: translateY(-4px);
            border-color: rgba(168, 85, 247, 0.6);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.2);
        }

        .strategy-icon {
            font-size: 40px;
            margin-bottom: 16px;
        }

        .strategy-name {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 12px;
        }

        .strategy-desc {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
        }

        @media (max-width: 768px) {
            .strategies-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Footer */
        .footer {
            padding: 40px 20px;
            text-align: center;
            border-top: 1px solid rgba(168, 85, 247, 0.2);
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 36px;
            }

            .hero p {
                font-size: 18px;
            }

            .section-title {
                font-size: 32px;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }

            .header-actions {
                flex-direction: column;
                gap: 8px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <a href="/" class="logo">
                <img src="{{ asset('images/Traiding (2).svg') }}" alt="Traiding Helper Pro Logo" onerror="this.style.display='none';">
                <span class="logo-text">Traiding Helper Pro</span>
            </a>
            <div class="header-actions">
                <a href="{{ route('login') }}" class="btn btn-outline">Войти</a>
                <a href="{{ route('register') }}" class="btn btn-primary">Регистрация</a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Автоматизированные торговые сигналы для криптовалют</h1>
            <p>Получайте торговые сигналы на основе технического анализа. Наша система анализирует рынок 24/7 и отправляет вам лучшие возможности для торговли.</p>
            <div class="hero-actions">
                <a href="{{ route('register') }}" class="btn btn-primary">Начать бесплатно</a>
                <a href="#about" class="btn btn-outline">Узнать больше</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <h2 class="section-title">Что мы предлагаем</h2>
        <p class="section-subtitle">Мощные инструменты для успешной торговли</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3 class="feature-title">История сигналов</h3>
                <p class="feature-description">
                    Просматривайте все торговые сигналы с детальной информацией. Фильтруйте по времени, анализируйте результаты и изучайте паттерны.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3 class="feature-title">Live аналитика</h3>
                <p class="feature-description">
                    Настройте параметры торговых стратегий под себя. Получайте персонализированный анализ криптовалют с вероятностями LONG/SHORT.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📈</div>
                <h3 class="feature-title">Множество стратегий</h3>
                <p class="feature-description">
                    SuperTrend+VWAP, Ichimoku+RSI, EMA+RSI+MACD, Bollinger Bands и другие. Выбирайте стратегию, которая подходит вам.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3 class="feature-title">Автоматизация</h3>
                <p class="feature-description">
                    Наша система работает круглосуточно, анализируя рынок и отправляя вам сигналы в реальном времени через Telegram.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3 class="feature-title">Telegram интеграция</h3>
                <p class="feature-description">
                    Получайте сигналы прямо в Telegram. Быстро, удобно и всегда под рукой.
                </p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📰</div>
                <h3 class="feature-title">Крипто новости</h3>
                <p class="feature-description">
                    Даем новости про крипторынков из самых вероятных каналов. Будьте в курсе всех важных событий и трендов.
                </p>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="about-content">
            <h2 class="section-title">О нас</h2>
            <p class="section-subtitle">Мы помогаем трейдерам принимать обоснованные решения</p>
            
            <div class="about-text">
                <p>
                    Traiding Helper Pro — это автоматизированная система анализа криптовалютного рынка, 
                    которая использует передовые алгоритмы технического анализа для генерации торговых сигналов.
                </p>
                <p>
                    Наша команда разработала комплексную платформу, которая:
                </p>
            </div>

            <ul class="about-list">
                <li>Анализирует рынок 24/7 с использованием множества технических индикаторов</li>
                <li>Генерирует торговые сигналы на основе проверенных стратегий</li>
                <li>Отправляет уведомления в реальном времени через Telegram</li>
                <li>Предоставляет детальную аналитику с графиками TradingView</li>
                <li>Позволяет настраивать параметры стратегий под ваши предпочтения</li>
                <li>Отслеживает историю всех сигналов для анализа эффективности</li>
            </ul>
        </div>
    </section>

    <!-- Strategies Section -->
    <section class="strategies" id="strategies">
        <div class="strategies-content">
            <h2 class="section-title">Торговые стратегии</h2>
            <p class="section-subtitle">Мы используем 6 проверенных стратегий для генерации сигналов</p>
            
            <div class="strategies-grid">
                <div class="strategy-item">
                    <div class="strategy-icon">📈</div>
                    <h3 class="strategy-name">MTF Strategy</h3>
                    <p class="strategy-desc">Multi-TimeFrame - Мультитаймфреймовая стратегия с анализом на нескольких таймфреймах (5м, 15м, 1ч) для точного определения точек входа.</p>
                </div>

                <div class="strategy-item">
                    <div class="strategy-icon">🧠</div>
                    <h3 class="strategy-name">EMA + RSI + MACD</h3>
                    <p class="strategy-desc">Универсальная трендовая стратегия, использующая пересечения EMA, импульс MACD и фильтр RSI для определения направления тренда.</p>
                </div>

                <div class="strategy-item">
                    <div class="strategy-icon">💥</div>
                    <h3 class="strategy-name">Bollinger Bands + RSI</h3>
                    <p class="strategy-desc">Контртрендовая стратегия для боковых рынков, использующая отскоки от границ Bollinger Bands с подтверждением RSI.</p>
                </div>

                <div class="strategy-item">
                    <div class="strategy-icon">⚡</div>
                    <h3 class="strategy-name">EMA + Stochastic</h3>
                    <p class="strategy-desc">Скальпинговая стратегия для быстрой торговли, использует пересечения EMA и выход Stochastic из зон перекупленности/перепроданности.</p>
                </div>

                <div class="strategy-item">
                    <div class="strategy-icon">📊</div>
                    <h3 class="strategy-name">SuperTrend + VWAP</h3>
                    <p class="strategy-desc">Внутридневная трендовая стратегия, сочетающая индикатор SuperTrend для определения тренда и VWAP для справедливой цены.</p>
                </div>

                <div class="strategy-item">
                    <div class="strategy-icon">🔥</div>
                    <h3 class="strategy-name">Ichimoku + RSI</h3>
                    <p class="strategy-desc">Трендовая стратегия с поддержкой облака Ишимоку, использует облако для определения тренда и RSI для фильтрации сигналов.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Terms Section -->
    <section class="terms" id="terms">
        <div class="terms-content">
            <h2 class="terms-title">⚠️ Важные условия использования</h2>
            <div class="terms-text">
                <p>
                    <strong>Внимание!</strong> Используя наш сервис, вы понимаете и соглашаетесь со следующими условиями:
                </p>
                <p>
                    <strong>Автоматизация и торговые сигналы могут быть не всегда правильными.</strong> 
                    Наша система использует технический анализ для генерации сигналов, но рынок криптовалют 
                    является высоковолатильным и непредсказуемым. Сигналы предоставляются исключительно в 
                    информационных целях и не являются финансовой консультацией.
                </p>
                <p>
                    <strong>Мы не гарантируем прибыльность торговли.</strong> Все торговые решения вы принимаете 
                    на свой собственный риск. Рекомендуем всегда проводить собственный анализ и использовать 
                    управление рисками (stop-loss, take-profit).
                </p>
                <p>
                    <strong>Мы не несем ответственности за финансовые потери,</strong> возникшие в результате 
                    использования наших сигналов или автоматизации. Торговля криптовалютами сопряжена с рисками, 
                    и вы должны быть готовы к возможным потерям.
                </p>
                <p>
                    Используя наш сервис, вы подтверждаете, что ознакомились с этими условиями и принимаете 
                    на себя всю ответственность за свои торговые решения.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2025 Traiding Helper Pro. Все права защищены.</p>
    </footer>
</body>
</html>

