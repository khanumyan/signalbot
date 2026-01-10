<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $news->title }} - Traiding Helper Pro</title>
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

        .news-article {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 0;
            overflow: hidden;
        }

        .news-image-large {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            background: rgba(15, 23, 42, 0.6);
        }

        .news-content {
            padding: 32px;
        }

        .news-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #ffffff;
            line-height: 1.4;
        }

        .news-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 24px;
            padding-bottom: 24px;
            border-bottom: 1px solid rgba(168, 85, 247, 0.2);
        }

        .news-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .news-description {
            font-size: 16px;
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .news-content-full {
            font-size: 15px;
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 24px;
        }

        .news-link-btn {
            display: inline-block;
            background: rgba(168, 85, 247, 0.2);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 12px 24px;
            color: #c4b5fd;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            margin-top: 24px;
        }

        .news-link-btn:hover {
            background: rgba(168, 85, 247, 0.3);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .related-news {
            margin-top: 40px;
        }

        .related-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 24px;
            color: #a855f7;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .related-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .related-card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-2px);
        }

        .related-image {
            width: 100%;
            height: 150px;
            object-fit: cover;
            background: rgba(15, 23, 42, 0.6);
        }

        .related-body {
            padding: 16px;
        }

        .related-title-text {
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

        .related-meta {
            font-size: 12px;
            color: #64748b;
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
        <a href="{{ route('crypto-news.index') }}" class="back-btn">← Назад к новостям</a>
        <div style="font-size: 24px; font-weight: bold; background: linear-gradient(to right, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
            📰 Новость
        </div>
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

        <article class="news-article">
            @if($news->image_url)
                <img src="{{ $news->image_url }}" alt="{{ $news->title }}" class="news-image-large" onerror="this.style.display='none';">
            @endif
            
            <div class="news-content">
                <h1 class="news-title">{{ str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $news->title) }}</h1>
                
                <div class="news-meta">
                    @if($news->pub_date)
                        <div class="news-meta-item" style="color: #a855f7; font-weight: 600; font-size: 16px;">
                            <span>📅</span>
                            <span>{{ $news->pub_date->format('d.m.Y H:i') }}</span>
                        </div>
                    @endif
                    
                    @if($news->source_name)
                        <div class="news-meta-item">
                            <span>📡</span>
                            <span>{{ $news->source_name }}</span>
                        </div>
                    @endif

                    @if($news->creator && is_array($news->creator) && count($news->creator) > 0)
                        <div class="news-meta-item">
                            <span>✍️</span>
                            <span>{{ implode(', ', array_slice($news->creator, 0, 2)) }}</span>
                        </div>
                    @endif

                    @if($news->coin && is_array($news->coin) && count($news->coin) > 0)
                        <div class="news-meta-item">
                            <span>🪙</span>
                            <span>{{ implode(', ', $news->coin) }}</span>
                        </div>
                    @endif
                </div>

                @if($news->description && trim(str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $news->description)) !== '')
                    <div class="news-description">
                        {{ str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $news->description) }}
                    </div>
                @endif

                @if($news->content && trim(str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $news->content)) !== '')
                    <div class="news-content-full">
                        {{ str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $news->content) }}
                    </div>
                @endif

                @if($news->link)
                    <a href="{{ $news->link }}" target="_blank" class="news-link-btn">
                        Читать оригинальную статью →
                    </a>
                @endif
            </div>
        </article>

        @if($relatedNews->count() > 0)
            <div class="related-news">
                <div class="related-title">📰 Похожие новости</div>
                <div class="related-grid">
                    @foreach($relatedNews as $related)
                        <a href="{{ route('crypto-news.show', $related->id) }}" class="related-card">
                            @if($related->image_url)
                                <img src="{{ $related->image_url }}" alt="{{ $related->title }}" class="related-image" onerror="this.style.display='none';">
                            @endif
                            <div class="related-body">
                                <div class="related-title-text">{{ str_replace('ДОСТУПНО ТОЛЬКО В ПЛАТНЫХ ПЛАНАХ', '', $related->title) }}</div>
                                <div class="related-meta" style="color: #a855f7; font-weight: 500;">
                                    @if($related->pub_date)
                                        📅 {{ $related->pub_date->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>

