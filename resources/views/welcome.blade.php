<!DOCTYPE html>
<html lang="ru">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Trading Helper Pro</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 40px 16px 30px 16px;
            position: relative;
        }

        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
        }

        .user-info {
            position: absolute;
            top: 20px;
            left: 20px;
            font-size: 14px;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
        }

        .user-info:hover {
            background: rgba(168, 85, 247, 0.1);
            color: #a855f7;
            transform: translateY(-2px);
        }

        .header-title {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .header-subtitle {
            font-size: 16px;
            color: #94a3b8;
        }

        .logo-container {
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .logo-image {
            max-width: 250px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 4px 16px rgba(168, 85, 247, 0.4));
            animation: fadeInScale 0.6s ease-out;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Menu Cards */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 40px;
        }

        .menu-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            padding: 24px;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .menu-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.2);
        }

        .menu-card-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .menu-card-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #ffffff;
        }

        .menu-card-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.5;
        }

        .menu-card-arrow {
            margin-top: 16px;
            font-size: 20px;
            color: #a855f7;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Stats Section */
        .stats-section {
            margin-top: 60px;
            padding: 24px;
            background: rgba(30, 41, 59, 0.4);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 16px;
        }

        .stats-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            color: #a855f7;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
        }

        .stat-item {
            text-align: center;
            padding: 16px;
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 12px;
            color: #94a3b8;
        }

        /* News Section */
        .news-section {
            margin-top: 60px;
        }

        .news-section-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: bold;
            color: #a855f7;
        }

        .news-section-link {
            font-size: 14px;
            color: #c4b5fd;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .news-section-link:hover {
            color: #a855f7;
        }

        .news-grid-home {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px;
        }

        .news-card-home {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 12px;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: block;
            transition: all 0.3s ease;
        }

        .news-card-home:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-2px);
        }

        .news-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: rgba(15, 23, 42, 0.6);
        }

        .news-card-content {
            padding: 16px;
        }

        .news-card-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #ffffff;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .news-card-meta {
            font-size: 12px;
            color: #64748b;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .telegram-banner {
            margin-top: 30px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(236, 72, 153, 0.2) 100%);
            border: 1px solid rgba(168, 85, 247, 0.4);
            border-radius: 16px;
            text-align: center;
        }

        .telegram-banner-text {
            font-size: 14px;
            color: #cbd5e1;
            margin-bottom: 12px;
            line-height: 1.6;
        }

        .telegram-banner-link {
            display: inline-block;
            background: rgba(168, 85, 247, 0.3);
            border: 1px solid rgba(168, 85, 247, 0.5);
            border-radius: 10px;
            padding: 12px 24px;
            color: #c4b5fd;
            text-decoration: none;
            font-size: 15px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .telegram-banner-link:hover {
            background: rgba(168, 85, 247, 0.4);
            border-color: rgba(168, 85, 247, 0.7);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 40px 16px;
            color: #64748b;
            font-size: 14px;
        }
            </style>
    </head>
<body>
    <!-- Header -->
    <div class="header">
        <a href="{{ route('profile.index') }}" class="user-info">
            👤 {{ Auth::user()->name }}
        </a>
        <form method="POST" action="{{ route('logout') }}" id="logoutForm" style="position: absolute; top: 20px; right: 20px;">
            @csrf
            <button type="button" class="logout-btn" onclick="handleLogout()">
                Выйти
            </button>
        </form>
        <div class="logo-container">
            <img src="{{ asset('images/Traiding (10).png') }}" alt="Trading Helper Pro Logo" class="logo-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div style="display: none; align-items: center; justify-content: center; gap: 12px;">
                <span style="font-size: 48px;">🤖</span>
            </div>
        </div>
        <div class="header-title">Trading Helper Pro</div>
        <div class="header-subtitle">Автоматизированная система анализа криптовалютных рынков</div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Menu Grid -->
        <div class="menu-grid">
            <!-- Signals History -->
            <a href="{{ route('signals.index') }}" class="menu-card">
                <div class="menu-card-icon">📊</div>
                <div class="menu-card-title">История сигналов</div>
                <div class="menu-card-description">
                    Просмотр всех торговых сигналов с фильтрацией по времени. Детальный анализ каждого сигнала с графиками TradingView.
                </div>
                <div class="menu-card-arrow">
                    Перейти <span>→</span>
                </div>
            </a>

            <!-- Live Analytics -->
            <a href="{{ route('strategy-settings.index') }}" class="menu-card">
                <div class="menu-card-icon">📊</div>
                <div class="menu-card-title">Live аналитика</div>
                <div class="menu-card-description">
                    Настройка параметров стратегий и живой анализ криптовалют. Выберите стратегию, параметры и получите анализ с вероятностями LONG/SHORT.
                </div>
                <div class="menu-card-arrow">
                    Перейти <span>→</span>
                </div>
            </a>

            <!-- Chart Analysis -->
            <a href="{{ route('chart-analysis.index') }}" class="menu-card">
                <div class="menu-card-icon">🤖</div>
                <div class="menu-card-title">AI Анализ графиков</div>
                <div class="menu-card-description">
                    Загрузите скриншот графика и получите детальный анализ от AI с торговыми рекомендациями и уровнями входа.
                </div>
                <div class="menu-card-arrow">
                    Перейти <span>→</span>
                </div>
            </a>

            <!-- Crypto News -->
            <a href="{{ route('crypto-news.index') }}" class="menu-card">
                <div class="menu-card-icon">📰</div>
                <div class="menu-card-title">Крипто Новости</div>
                <div class="menu-card-description">
                    Последние новости из мира криптовалют. Актуальная информация о рынке, событиях и трендах.
                </div>
                <div class="menu-card-arrow">
                    Перейти <span>→</span>
                </div>
            </a>
        </div>

        <!-- Stats Section -->
        <div class="stats-section">
            <div class="stats-title">📈 Быстрая статистика</div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value" style="color: #10b981;" id="totalSignals">-</div>
                    <div class="stat-label">Всего сигналов</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #a855f7;" id="activeStrategies">6</div>
                    <div class="stat-label">Активных стратегий</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value" style="color: #3b82f6;" id="todaySignals">-</div>
                    <div class="stat-label">Сигналов сегодня</div>
                </div>
            </div>
        </div>

        <!-- News Section -->
        @if(isset($latestNews) && $latestNews->count() > 0)
        <div class="news-section">
            <div class="news-section-title">
                <span>📰 Последние новости</span>
                <a href="{{ route('crypto-news.index') }}" class="news-section-link">
                    Все новости →
                </a>
            </div>
            <div class="news-grid-home">
                @foreach($latestNews as $item)
                    <a href="{{ route('crypto-news.show', $item->id) }}" class="news-card-home">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="news-image" onerror="this.style.display='none';">
                        @endif
                        <div class="news-card-content">
                            <div class="news-card-title">{{ str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $item->title) }}</div>
                            <div class="news-card-meta">
                                @if($item->pub_date)
                                    <span style="color: #a855f7; font-weight: bold;">📅 {{ $item->pub_date->format('d.m.Y H:i') }}</span>
                                @endif
                                @if($item->source_name)
                                    <span>📡 {{ Str::limit($item->source_name, 20) }}</span>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            <div class="telegram-banner" style="margin-top: 30px;">
                <div class="telegram-banner-text">
                    💡 Хотите первыми узнавать новости?<br>
                    Подпишитесь на наш Telegram канал!
                </div>
                <a href="https://t.me/traidinghelpernews" target="_blank" class="telegram-banner-link">
                    📢 Подписаться на канал
                </a>
            </div>
        </div>
        @endif
    </div>

    <!-- Footer -->
    <div class="footer">
        <div>Trading Helper Pro © 2025</div>
        <div style="margin-top: 8px; font-size: 12px;">Автоматизированная система торговых сигналов</div>
    </div>

    <!-- Modal Script (must load first) -->
    <script src="{{ asset('js/modal.js') }}"></script>
    
    <script>
        // Load stats
        async function loadStats() {
            try {
                const response = await fetch('/api/signals/stats');
                if (response.ok) {
                    const data = await response.json();
                    if (data.total !== undefined) {
                        document.getElementById('totalSignals').textContent = data.total;
                    }
                    if (data.today !== undefined) {
                        document.getElementById('todaySignals').textContent = data.today;
                    }
                }
            } catch (error) {
                console.error('Error loading stats:', error);
                showModal('error', 'Ошибка', 'Не удалось загрузить статистику. Попробуйте обновить страницу.', null, true);
            }
        }

        // Load stats on page load
        loadStats();

        // Handle logout with modal
        async function handleLogout() {
            const confirmed = await confirm('Вы уверены, что хотите выйти?');
            if (confirmed) {
                document.getElementById('logoutForm').submit();
            }
        }
    </script>
    
    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
    </body>
</html>
