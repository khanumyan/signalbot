<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Крипто Новости - Trading Helper Bot</title>
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
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 16px;
        }

        .header {
            text-align: center;
            padding: 30px 16px;
            margin-bottom: 30px;
        }

        .back-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(168, 85, 247, 0.2);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            color: #c4b5fd;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(168, 85, 247, 0.3);
            border-color: rgba(168, 85, 247, 0.5);
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

        .news-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .news-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .news-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.2);
        }

        .news-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: rgba(15, 23, 42, 0.6);
        }

        .news-card-body {
            padding: 24px;
        }

        .news-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 12px;
            color: #ffffff;
            line-height: 1.4;
        }

        .news-description {
            font-size: 14px;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 16px;
        }

        .news-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 12px;
            color: #64748b;
            margin-bottom: 16px;
        }

        .news-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .news-link {
            display: inline-block;
            background: rgba(168, 85, 247, 0.2);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 10px 20px;
            color: #c4b5fd;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .news-link:hover {
            background: rgba(168, 85, 247, 0.3);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .pagination {
            margin-top: 40px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .pagination ul {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 0;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination li {
            margin: 0;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 10px 16px;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 10px;
            color: #c4b5fd;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 14px;
            min-width: 44px;
            text-align: center;
        }

        .pagination a:hover {
            background: rgba(168, 85, 247, 0.3);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(168, 85, 247, 0.2);
        }

        .pagination .active {
            background: rgba(168, 85, 247, 0.4);
            border-color: rgba(168, 85, 247, 0.6);
            color: #ffffff;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #94a3b8;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        /* Telegram Channel Banner */
        .telegram-banner {
            margin-top: 30px;
            margin-bottom: 30px;
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
    </style>
</head>
<body>
    <div class="header" style="position: relative;">
        <a href="{{ route('home') }}" class="back-btn">← Назад</a>
        <div class="header-title">📰 Крипто Новости</div>
    </div>

    <div class="container">
        <!-- Telegram Channel Banner -->
        <div class="telegram-banner">
            <div class="telegram-banner-text">
                💡 Хотите первыми узнавать новости?<br>
                Подпишитесь на наш Telegram канал!
            </div>
            <a href="https://t.me/traidinghelpernews" target="_blank" class="telegram-banner-link">
                📢 Подписаться на канал
            </a>
        </div>

        @if($news->count() > 0)
            <div class="news-grid">
                @foreach($news as $item)
                    <a href="{{ route('crypto-news.show', $item->id) }}" class="news-card">
                        @if($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" class="news-image" onerror="this.style.display='none';">
                        @endif
                        <div class="news-card-body">
                            <div class="news-title">{{ str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $item->title) }}</div>
                            
                            @if($item->description && trim(str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $item->description)) !== '')
                                <div class="news-description">
                                    {{ Str::limit(str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $item->description), 200) }}
                                </div>
                            @endif

                            <div class="news-meta">
                                @if($item->pub_date)
                                    <div class="news-meta-item" style="color: #a855f7; font-weight: 500;">
                                        <span>📅</span>
                                        <span>{{ $item->pub_date->format('d.m.Y H:i') }}</span>
                                    </div>
                                @endif
                                
                                @if($item->source_name)
                                    <div class="news-meta-item">
                                        <span>📡</span>
                                        <span>{{ $item->source_name }}</span>
                                    </div>
                                @endif

                                @if($item->coin && is_array($item->coin) && count($item->coin) > 0)
                                    <div class="news-meta-item">
                                        <span>🪙</span>
                                        <span>{{ implode(', ', array_slice($item->coin, 0, 3)) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($news->hasPages())
            <div class="pagination">
                <ul>
                    @php
                        $currentPage = $news->currentPage();
                        $lastPage = $news->lastPage();
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($lastPage, $currentPage + 2);
                        
                        // Если в начале, показываем больше справа
                        if ($currentPage <= 3) {
                            $endPage = min($lastPage, 5);
                        }
                        
                        // Если в конце, показываем больше слева
                        if ($currentPage >= $lastPage - 2) {
                            $startPage = max(1, $lastPage - 4);
                        }
                    @endphp
                    
                    @for($page = $startPage; $page <= $endPage; $page++)
                        @if($page == $currentPage)
                            <li>
                                <span class="active">{{ $page }}</span>
                            </li>
                        @else
                            <li>
                                <a href="{{ $news->url($page) }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endfor
                </ul>
            </div>
            @endif
        @else
            <div class="empty-state">
                <div class="empty-state-icon">📰</div>
                <div style="font-size: 18px; margin-bottom: 8px;">Новостей пока нет</div>
                <div style="font-size: 14px;">Новости будут появляться здесь автоматически</div>
            </div>
        @endif
    </div>

    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>

