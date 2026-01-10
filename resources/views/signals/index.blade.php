<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>История сигналов - Crypto AI Trading Bot</title>
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

        /* Filters */
        .filters {
            display: flex;
            gap: 8px;
            margin: 16px 0;
            flex-wrap: wrap;
            padding: 0 16px;
        }

        .filter-btn {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 10px 16px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .filter-btn:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .filter-btn.active {
            background: linear-gradient(to right, #9333ea, #db2777);
            border-color: #9333ea;
        }

        /* Cards */
        .card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .card:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
            transform: translateY(-2px);
        }

        .signal-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .signal-info {
            flex: 1;
        }

        .signal-symbol {
            font-size: 20px;
            font-weight: bold;
            color: #a855f7;
            margin-bottom: 4px;
        }

        .signal-meta {
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            gap: 12px;
            margin-top: 4px;
            flex-wrap: wrap;
        }

        .signal-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
            white-space: nowrap;
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
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
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

        /* Loading */
        .loading {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid rgba(147, 51, 234, 0.3);
            border-top: 4px solid #9333ea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .empty-state-text {
            color: #94a3b8;
            font-size: 16px;
        }

        .empty-state-subtext {
            color: #64748b;
            font-size: 14px;
            margin-top: 8px;
        }

        /* Signals container */
        #signalsContainer {
            min-height: 200px;
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <a href="/" class="back-button">← Назад</a>
                    <img src="{{ asset('images/erasebg-transformed (1).png') }}" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                    <div>
                        <div class="header-title">📊 История сигналов</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Все торговые сигналы</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <button class="filter-btn" data-filter="today" onclick="changeFilter('today')">📅 Сегодня</button>
            <button class="filter-btn" data-filter="yesterday" onclick="changeFilter('yesterday')">📅 Вчера</button>
            <button class="filter-btn" data-filter="week" onclick="changeFilter('week')">📅 Неделя</button>
            <button class="filter-btn" data-filter="month" onclick="changeFilter('month')">📅 Месяц</button>
            <button class="filter-btn active" data-filter="all" onclick="changeFilter('all')">📅 Все время</button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        @if(!$hasActiveSubscription)
            <!-- Subscription Banner -->
            <div class="card" style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.3) 0%, rgba(236, 72, 153, 0.3) 100%); border: 2px solid rgba(168, 85, 247, 0.5); margin-bottom: 20px;">
                <div style="text-align: center; padding: 8px 0;">
                    <div style="font-size: 18px; font-weight: bold; margin-bottom: 12px; background: linear-gradient(to right, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        🔒 Премиум доступ
                    </div>
                    <div style="font-size: 14px; color: #94a3b8; margin-bottom: 16px;">
                        Сигналы за сегодня и вчера доступны только подписчикам
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        @if(!$hasFreeTrialUsed)
                            <button id="startFreeTrialBtn" style="background: linear-gradient(to right, #9333ea, #db2777); border: none; border-radius: 12px; padding: 12px 24px; color: white; font-weight: bold; font-size: 14px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                🎁 Start Free Trial
                            </button>
                        @endif
                        <button id="buySubscriptionBtn" style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(168, 85, 247, 0.5); border-radius: 12px; padding: 12px 24px; color: white; font-weight: bold; font-size: 14px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(30, 41, 59, 1)'" onmouseout="this.style.background='rgba(30, 41, 59, 0.8)'">
                            💳 Купить подписку
                        </button>
                    </div>
                    <div id="subscriptionMessage" style="margin-top: 12px; font-size: 12px; color: #10b981; display: none;"></div>
                </div>
            </div>
        @endif

        <div id="signalsContainer">
            @if($signals->count() > 0)
                @foreach($signals as $signal)
                    <a href="{{ route('signals.show', $signal->id) }}" style="text-decoration: none; color: inherit;">
                        <div class="card">
                            <div class="signal-item">
                                <div class="signal-info">
                                    <div class="signal-symbol">
                                        {{ $signal->symbol }}
                                        <span class="signal-strength strength-{{ strtolower($signal->strength) }}">
                                            {{ $signal->strength }}
                                        </span>
                                        @if($signal->status)
                                            <span class="signal-status status-{{ strtolower($signal->status) }}">
                                                @if($signal->status === 'DONE') ✅ Выполнен
                                                @elseif($signal->status === 'MISSED') ❌ Пропущен
                                                @elseif($signal->status === 'PROCESSING') ⏳ В обработке
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    <div class="signal-meta">
                                        <span>💰 ${{ number_format($signal->price, 2, '.', ' ') }}</span>
                                        <span>📅 {{ $signal->signal_time->format('d.m.Y H:i') }}</span>
                                        <span>🎯 {{ $signal->strategy }}</span>
                                    </div>
                                </div>
                                <div>
                                    <span class="signal-badge signal-{{ strtolower($signal->type) }}">
                                        {{ $signal->type === 'BUY' ? '📈 LONG' : '📉 SHORT' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            @else
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <div class="empty-state-text">Нет сигналов</div>
                    <div class="empty-state-subtext">
                        @if(!$hasActiveSubscription)
                            Показываются только сигналы со статусами (завершенные/пропущенные) старше вчера. Активируйте подписку для доступа к свежим сигналам.
                        @else
                            Сигналы появятся после анализа рынка
                        @endif
                    </div>
                </div>
            @endif
        </div>
        
        <div id="loadingIndicator" class="loading hidden">
            <div class="spinner"></div>
            <div>Загрузка сигналов...</div>
        </div>
    </div>

    <script>
        let currentFilter = '{{ $filter }}';
        let currentPage = 1;
        let isLoading = false;
        let hasMore = {{ $hasMore ? 'true' : 'false' }};
        let hasActiveSubscription = {{ $hasActiveSubscription ? 'true' : 'false' }};
        let hasFreeTrialUsed = {{ $hasFreeTrialUsed ? 'true' : 'false' }};
        const perPage = 50;

        // Set active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            if (btn.dataset.filter === currentFilter) {
                btn.classList.add('active');
            }
        });

        // Change filter
        function changeFilter(filter) {
            if (filter === currentFilter && currentPage === 1) return;
            
            currentFilter = filter;
            currentPage = 1;
            hasMore = true;
            
            // Update active button
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.filter === filter) {
                    btn.classList.add('active');
                }
            });
            
            // Clear container and load new data
            document.getElementById('signalsContainer').innerHTML = '';
            loadSignals();
        }

        // Load signals via AJAX
        function loadSignals() {
            if (isLoading || !hasMore) return;
            
            isLoading = true;
            document.getElementById('loadingIndicator').classList.remove('hidden');
            
            const url = new URL('{{ route("signals.index") }}', window.location.origin);
            url.searchParams.set('filter', currentFilter);
            url.searchParams.set('page', currentPage);
            
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                // Обновляем статус подписки из ответа
                if (data.hasActiveSubscription !== undefined) {
                    hasActiveSubscription = data.hasActiveSubscription;
                }
                if (data.hasFreeTrialUsed !== undefined) {
                    hasFreeTrialUsed = data.hasFreeTrialUsed;
                }
                
                if (data.signals && data.signals.length > 0) {
                    const container = document.getElementById('signalsContainer');
                    
                    data.signals.forEach(signal => {
                        const signalCard = createSignalCard(signal);
                        container.appendChild(signalCard);
                    });
                    
                    hasMore = data.hasMore;
                    currentPage++;
                } else {
                    hasMore = false;
                    if (currentPage === 1) {
                        const emptyMessage = !hasActiveSubscription 
                            ? 'Показываются только сигналы со статусами (завершенные/пропущенные) старше вчера. Активируйте подписку для доступа к свежим сигналам.'
                            : 'Сигналы появятся после анализа рынка';
                        document.getElementById('signalsContainer').innerHTML = `
                            <div class="empty-state">
                                <div class="empty-icon">📊</div>
                                <div class="empty-state-text">Нет сигналов</div>
                                <div class="empty-state-subtext">${emptyMessage}</div>
                            </div>
                        `;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading signals:', error);
                showModal('error', 'Ошибка', 'Не удалось загрузить сигналы. Попробуйте обновить страницу.', null, true);
            })
            .finally(() => {
                isLoading = false;
                document.getElementById('loadingIndicator').classList.add('hidden');
            });
        }

        // Create signal card element
        function createSignalCard(signal) {
            const link = document.createElement('a');
            link.href = `/signals/${signal.id}`;
            link.style.textDecoration = 'none';
            link.style.color = 'inherit';
            
            const card = document.createElement('div');
            card.className = 'card';
            
            const signalTime = new Date(signal.signal_time);
            const formattedDate = signalTime.toLocaleDateString('ru-RU', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const strengthClass = `strength-${signal.strength.toLowerCase()}`;
            const typeClass = `signal-${signal.type.toLowerCase()}`;
            const typeLabel = signal.type === 'BUY' ? '📈 LONG' : '📉 SHORT';
            
            let statusHtml = '';
            if (signal.status) {
                let statusClass = `status-${signal.status.toLowerCase()}`;
                let statusText = '';
                if (signal.status === 'DONE') {
                    statusText = '✅ Выполнен';
                } else if (signal.status === 'MISSED') {
                    statusText = '❌ Пропущен';
                } else if (signal.status === 'PROCESSING') {
                    statusText = '⏳ В обработке';
                }
                statusHtml = `<span class="signal-status ${statusClass}">${statusText}</span>`;
            }
            
            card.innerHTML = `
                <div class="signal-item">
                    <div class="signal-info">
                        <div class="signal-symbol">
                            ${signal.symbol}
                            <span class="signal-strength ${strengthClass}">
                                ${signal.strength}
                            </span>
                            ${statusHtml}
                        </div>
                        <div class="signal-meta">
                            <span>💰 $${parseFloat(signal.price).toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                            <span>📅 ${formattedDate}</span>
                            <span>🎯 ${signal.strategy || 'MTF'}</span>
                        </div>
                    </div>
                    <div>
                        <span class="signal-badge ${typeClass}">
                            ${typeLabel}
                        </span>
                    </div>
                </div>
            `;
            
            link.appendChild(card);
            return link;
        }

        // Infinite scroll
        window.addEventListener('scroll', () => {
            if (isLoading || !hasMore) return;
            
            const scrollPosition = window.innerHeight + window.scrollY;
            const documentHeight = document.documentElement.scrollHeight;
            
            // Load more when 200px before bottom
            if (scrollPosition >= documentHeight - 200) {
                loadSignals();
            }
        });

        // Initial load if there are more signals
        @if($hasMore)
            // Load more signals on initial page load if needed
            window.addEventListener('load', () => {
                setTimeout(() => {
                    if (hasMore && !isLoading) {
                        loadSignals();
                    }
                }, 500);
            });
        @endif

        // Start Free Trial button handler - проверяем наличие кнопки, так как она показывается только если !$hasFreeTrialUsed
        document.addEventListener('DOMContentLoaded', function() {
            const startFreeTrialBtn = document.getElementById('startFreeTrialBtn');
            if (startFreeTrialBtn) {
                startFreeTrialBtn.addEventListener('click', function() {
                    const btn = this;
                    const messageDiv = document.getElementById('subscriptionMessage');
                    
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                    btn.style.cursor = 'not-allowed';
                    if (messageDiv) {
                        messageDiv.style.display = 'block';
                        messageDiv.style.color = '#94a3b8';
                        messageDiv.textContent = '⏳ Активация пробного периода...';
                    }
                    
                    fetch('{{ route("signals.free-trial") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (messageDiv) {
                                messageDiv.style.color = '#10b981';
                                messageDiv.textContent = '✅ ' + data.message + ' Действует до ' + data.subscription.date_to;
                            }
                            
                            // Перезагружаем страницу через 1.5 секунды, чтобы баннер исчез
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            if (messageDiv) {
                                messageDiv.style.color = '#ef4444';
                                messageDiv.textContent = '❌ ' + (data.error || 'Ошибка при активации');
                            }
                            btn.disabled = false;
                            btn.style.opacity = '1';
                            btn.style.cursor = 'pointer';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (messageDiv) {
                            messageDiv.style.color = '#ef4444';
                            messageDiv.textContent = '❌ Ошибка при активации пробного периода';
                        }
                        btn.disabled = false;
                        btn.style.opacity = '1';
                        btn.style.cursor = 'pointer';
                    });
                });
            }

            // Buy Subscription button handler (placeholder)
            const buySubscriptionBtn = document.getElementById('buySubscriptionBtn');
            if (buySubscriptionBtn) {
                buySubscriptionBtn.addEventListener('click', function() {
                    // TODO: Реализовать логику покупки подписки
                    alert('Функция покупки подписки будет реализована позже');
                });
            }
        });
    </script>
    
    <!-- Modal Script -->
    <script src="{{ asset('js/modal.js') }}"></script>
    
    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>
