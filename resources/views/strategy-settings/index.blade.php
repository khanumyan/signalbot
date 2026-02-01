<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Live аналитика - Trading Helper Pro</title>
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

        /* Strategy Tabs */
        .strategy-tabs {
            display: flex;
            gap: 8px;
            margin: 16px 0;
            flex-wrap: wrap;
            padding: 0 16px;
            overflow-x: auto;
        }

        .strategy-tab {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .strategy-tab:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .strategy-tab.active {
            background: linear-gradient(to right, #9333ea, #db2777);
            border-color: #9333ea;
        }

        /* Cards */
        .card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 20px;
            margin: 16px 0;
        }

        .card-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(71, 85, 105, 0.8);
            transition: .4s;
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .toggle-slider {
            background-color: #9333ea;
        }

        input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Form Groups */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .input-group {
            display: flex;
            flex-direction: column;
        }

        .input-label {
            font-size: 13px;
            color: #cbd5e1;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .input-label .default-value {
            font-size: 11px;
            color: #94a3b8;
            font-weight: normal;
            margin-left: 8px;
        }

        .input {
            width: 100%;
            background: rgba(51, 65, 85, 0.6);
            border: 1px solid rgba(100, 116, 139, 0.5);
            border-radius: 8px;
            padding: 10px 12px;
            color: white;
            font-size: 14px;
        }

        .input:focus {
            outline: none;
            border-color: #9333ea;
        }

        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(to right, #9333ea, #db2777);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(147, 51, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: white;
        }

        .btn-secondary:hover {
            background: rgba(71, 85, 105, 1);
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 80px;
            right: 16px;
            padding: 16px 20px;
            border-radius: 12px;
            backdrop-filter: blur(12px);
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
            max-width: 400px;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .notification.success {
            background: rgba(16, 185, 129, 0.9);
            border: 1px solid #10b981;
        }

        .notification.error {
            background: rgba(239, 68, 68, 0.9);
            border: 1px solid #ef4444;
        }

        .hidden {
            display: none;
        }

        .strategy-content {
            display: none;
        }

        .strategy-content.active {
            display: block;
        }

        .info-box {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            font-size: 13px;
            color: #93c5fd;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(4px);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay:not(.hidden) {
            display: flex;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal {
            background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.95));
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 24px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }

        .modal-overlay:not(.hidden) .modal {
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .modal-icon {
            font-size: 32px;
        }

        .modal-title {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
        }

        .modal-body {
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn-primary {
            background: linear-gradient(to right, #9333ea, #db2777);
            color: white;
        }

        .modal-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(147, 51, 234, 0.4);
        }

        .modal-btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: white;
        }

        .modal-btn-secondary:hover {
            background: rgba(71, 85, 105, 1);
        }

        .modal-success .modal-icon {
            color: #10b981;
        }

        .modal-error .modal-icon {
            color: #ef4444;
        }

        .modal-warning .modal-icon {
            color: #fbbf24;
        }

        .modal-info .modal-icon {
            color: #3b82f6;
        }

        /* Main Tabs */
        .main-tabs {
            display: flex;
            gap: 8px;
            margin: 16px 0;
            padding: 0 16px;
        }

        .main-tab {
            flex: 1;
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            color: white;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }

        .main-tab:hover {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .main-tab.active {
            background: linear-gradient(to right, #9333ea, #db2777);
            border-color: #9333ea;
        }

        .main-content {
            display: none;
        }

        .main-content.active {
            display: block;
        }

        /* Analysis Form */
        .analysis-form {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 20px;
            margin: 16px 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        .select {
            width: 100%;
            background: rgba(51, 65, 85, 0.6);
            border: 1px solid rgba(100, 116, 139, 0.5);
            border-radius: 8px;
            padding: 12px;
            color: white;
            font-size: 14px;
        }

        .select:focus {
            outline: none;
            border-color: #9333ea;
        }

        /* Analysis Result */
        .analysis-result {
            margin-top: 20px;
        }

        .probability-card {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 12px;
            padding: 16px;
            margin: 12px 0;
        }

        .probability-bar {
            width: 100%;
            height: 30px;
            background: rgba(51, 65, 85, 0.6);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 8px;
            position: relative;
        }

        .probability-fill {
            height: 100%;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            transition: width 0.5s ease;
        }

        .probability-long {
            background: linear-gradient(to right, #10b981, #059669);
        }

        .probability-short {
            background: linear-gradient(to right, #ef4444, #dc2626);
        }

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
            height: 500px;
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
                    <img src="{{ asset('images/Traiding (2).svg') }}" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                    <div>
                        <div class="header-title">📊 Live аналитика</div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Настройка стратегий и живой анализ криптовалют</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Main Tabs - показываем только если есть подписка -->
        @if($hasActiveSubscription)
        <div class="main-tabs">
            <button class="main-tab active" onclick="switchMainTab('settings')">
                ⚙️ Настройки стратегий
            </button>
            <button class="main-tab" onclick="switchMainTab('analysis')">
                📊 Анализ
            </button>
        </div>
        @endif
    </div>

    <!-- Notification -->
    <div id="notification" class="notification hidden"></div>

    <!-- Modal -->
    <div id="modal" class="modal-overlay hidden">
        <div class="modal" id="modalContent">
            <div class="modal-header">
                <div class="modal-icon" id="modalIcon">⚠️</div>
                <div class="modal-title" id="modalTitle">Подтверждение</div>
            </div>
            <div class="modal-body" id="modalBody">
                Вы уверены, что хотите выполнить это действие?
            </div>
            <div class="modal-footer" id="modalFooter">
                <button class="modal-btn modal-btn-secondary" onclick="closeModal()">Отмена</button>
                <button class="modal-btn modal-btn-primary" id="modalConfirmBtn">Подтвердить</button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        @if(!$hasActiveSubscription)
            <!-- Subscription Banner - показываем только баннер, если нет подписки -->
            <div class="card" style="background: linear-gradient(135deg, rgba(147, 51, 234, 0.3) 0%, rgba(236, 72, 153, 0.3) 100%); border: 2px solid rgba(168, 85, 247, 0.5); margin-bottom: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 24px 16px;">
                    <div style="font-size: 24px; font-weight: bold; margin-bottom: 16px; background: linear-gradient(to right, #a855f7, #ec4899); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        🔒 Премиум доступ к Live аналитике
                    </div>
                    <div style="font-size: 16px; color: #94a3b8; margin-bottom: 24px; line-height: 1.6;">
                        Получите полный доступ к настройке стратегий и анализу криптовалют<br>
                        Настройте параметры торговых стратегий под себя и получайте персонализированные сигналы
                    </div>
                    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                        @if(!$hasFreeTrialUsed)
                            <button id="startFreeTrialBtn" onclick="handleStartFreeTrial(event)" style="background: linear-gradient(to right, #9333ea, #db2777); border: none; border-radius: 12px; padding: 14px 28px; color: white; font-weight: bold; font-size: 16px; cursor: pointer; transition: all 0.3s ease;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                                🎁 Start Free Trial
                            </button>
                        @endif
                        <a href="{{ route('orders.index', ['from' => 'strategy-settings']) }}" style="background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(168, 85, 247, 0.5); border-radius: 12px; padding: 14px 28px; color: white; font-weight: bold; font-size: 16px; cursor: pointer; transition: all 0.3s ease; text-decoration: none; display: inline-block;" onmouseover="this.style.background='rgba(30, 41, 59, 1)'" onmouseout="this.style.background='rgba(30, 41, 59, 0.8)'">
                            💳 Купить подписку
                        </a>
                    </div>
                    <div id="subscriptionMessage" style="margin-top: 16px; font-size: 14px; color: #10b981; display: none;"></div>
                </div>
            </div>
        @else
            <!-- Settings Tab Content - показываем только если есть подписка -->
            <div id="main-settings" class="main-content active">
                <!-- Strategy Tabs -->
                <div class="strategy-tabs">
                    @foreach($strategies as $strategyName => $strategyTitle)
                        <button class="strategy-tab {{ $loop->first ? 'active' : '' }}" 
                                data-strategy="{{ $strategyName }}" 
                                onclick="switchStrategy('{{ $strategyName }}')">
                            {{ $strategyTitle }}
                        </button>
                    @endforeach
                </div>

                @foreach($settings as $strategyName => $setting)
                    <div class="strategy-content {{ $loop->first ? 'active' : '' }}" id="strategy-{{ $strategyName }}">
                    <div class="card">
                        <div class="card-title">
                            <span>{{ $strategies[$strategyName] }}</span>
                            <label class="toggle-switch">
                                <input type="checkbox" 
                                       id="toggle-{{ $strategyName }}" 
                                       {{ $setting['is_active'] ? 'checked' : '' }}
                                       onchange="toggleStrategy('{{ $strategyName }}', this.checked)">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="info-box">
                            ⚠️ Эти параметры используются для генерации пользовательских сигналов. Системные параметры (например, RSI 20 для аналитики) остаются без изменений.
                        </div>

                        <form id="form-{{ $strategyName }}" onsubmit="saveSettings(event, '{{ $strategyName }}')">
                            <div class="form-grid">
                                @foreach($setting['parameters'] as $key => $value)
                                    <div class="input-group">
                                        <label class="input-label">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                            <span class="default-value">
                                                (по умолчанию: {{ $setting['defaults'][$key] ?? 'N/A' }})
                                            </span>
                                        </label>
                                        <input type="number" 
                                               class="input" 
                                               name="parameters[{{ $key }}]"
                                               value="{{ $value }}"
                                               step="0.01"
                                               placeholder="{{ $setting['defaults'][$key] ?? '' }}">
                                    </div>
                                @endforeach
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    💾 Сохранить настройки
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetStrategy('{{ $strategyName }}')">
                                    🔄 Сбросить к умолчанию
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Analysis Tab Content -->
            <div id="main-analysis" class="main-content">
            <div class="analysis-form">
                <div class="card-title">📊 Анализ криптовалюты</div>
                
                <form id="analysisForm" onsubmit="runAnalysis(event)">
                    <div class="form-row">
                        <div class="input-group">
                            <label class="input-label">Стратегия</label>
                            <select id="analysisStrategy" class="select" required>
                                <option value="">Выберите стратегию</option>
                                @foreach($strategies as $strategyName => $strategyTitle)
                                    <option value="{{ $strategyName }}">{{ $strategyTitle }}</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="input-group">
                            <label class="input-label">Монета (например: BTC)</label>
                            <input type="text" id="analysisCoin" class="input" placeholder="BTC" required style="text-transform: uppercase;">
                        </div>
                    </div>

                    <div id="strategyParams" style="display: none;">
                        <div class="card-title" style="font-size: 16px; margin-top: 20px;">Параметры стратегии</div>
                        <div class="form-grid" id="paramsContainer"></div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary" id="analyzeButton">
                            🚀 Запустить анализ
                        </button>
                    </div>
                </form>
            </div>

            <!-- Analysis Result -->
            <div id="analysisResult" class="hidden"></div>
        </div>
        @endif
    </div>

    <script src="https://s3.tradingview.com/tv.js"></script>
    <script>
        let currentStrategy = '{{ array_key_first($settings) }}';

        function switchStrategy(strategyName) {
            // Hide all strategy contents
            document.querySelectorAll('.strategy-content').forEach(el => {
                el.classList.remove('active');
            });
            
            // Remove active from all tabs
            document.querySelectorAll('.strategy-tab').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected strategy
            document.getElementById(`strategy-${strategyName}`).classList.add('active');
            document.querySelector(`[data-strategy="${strategyName}"]`).classList.add('active');
            
            currentStrategy = strategyName;
        }

        function toggleStrategy(strategyName, isActive) {
            fetch(`/strategy-settings/${strategyName}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    is_active: isActive
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`Стратегия ${isActive ? 'включена' : 'выключена'}`, 'success');
                }
            })
            .catch(error => {
                showModal('error', 'Ошибка', 'Произошла ошибка при обновлении настроек. Попробуйте еще раз.', null, true);
            });
        }

        function saveSettings(event, strategyName) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const parameters = {};
            
            // Collect all parameters
            formData.forEach((value, key) => {
                if (key.startsWith('parameters[')) {
                    const paramKey = key.match(/parameters\[(.*?)\]/)[1];
                    parameters[paramKey] = parseFloat(value) || value;
                }
            });

            fetch(`/strategy-settings/${strategyName}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    parameters: parameters
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Настройки успешно сохранены!', 'success');
                } else {
                    showModal('error', 'Ошибка', 'Не удалось сохранить настройки. Проверьте введенные данные и попробуйте еще раз.', null, true);
                }
            })
            .catch(error => {
                showModal('error', 'Ошибка', 'Произошла ошибка при сохранении настроек. Проверьте введенные данные и попробуйте еще раз.', null, true);
            });
        }

        function resetStrategy(strategyName) {
            showModal(
                'warning',
                'Сброс настроек',
                'Вы уверены, что хотите сбросить настройки к значениям по умолчанию? Все ваши изменения будут потеряны.',
                () => {
                    fetch(`/strategy-settings/${strategyName}/reset`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showModal('success', 'Успешно', 'Настройки успешно сброшены к значениям по умолчанию.', () => {
                                location.reload();
                            }, true);
                        } else {
                            showModal('error', 'Ошибка', 'Не удалось сбросить настройки. Попробуйте еще раз.', null, true);
                        }
                    })
                    .catch(error => {
                        showModal('error', 'Ошибка', 'Произошла ошибка при сбросе настроек. Попробуйте еще раз.', null, true);
                    });
                }
            );
        }

        function showNotification(message, type = 'success') {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.className = `notification ${type}`;
            setTimeout(() => notif.className = 'notification hidden', 3000);
        }

        // Modal functions
        function showModal(type, title, message, onConfirm = null, singleButton = false) {
            const modal = document.getElementById('modal');
            const modalContent = document.getElementById('modalContent');
            const modalIcon = document.getElementById('modalIcon');
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            const modalFooter = document.getElementById('modalFooter');

            // Set modal type class
            modalContent.className = `modal modal-${type}`;

            // Set icon based on type
            const icons = {
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'info': 'ℹ️'
            };
            modalIcon.textContent = icons[type] || '⚠️';

            modalTitle.textContent = title;
            modalBody.textContent = message;

            // Configure footer
            if (singleButton) {
                modalFooter.innerHTML = `
                    <button class="modal-btn modal-btn-primary" onclick="closeModal()">ОК</button>
                `;
            } else {
                modalFooter.innerHTML = `
                    <button class="modal-btn modal-btn-secondary" onclick="closeModal()">Отмена</button>
                    <button class="modal-btn modal-btn-primary" id="modalConfirmBtn">Подтвердить</button>
                `;
                
                // Set confirm handler
                if (onConfirm) {
                    document.getElementById('modalConfirmBtn').onclick = () => {
                        closeModal();
                        onConfirm();
                    };
                } else {
                    document.getElementById('modalConfirmBtn').onclick = closeModal;
                }
            }

            // Show modal
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
        }

        function closeModal() {
            const modal = document.getElementById('modal');
            modal.classList.add('hidden');
            modal.style.display = 'none';
        }

        // Close modal on overlay click
        document.getElementById('modal').addEventListener('click', (e) => {
            if (e.target.id === 'modal') {
                closeModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const modal = document.getElementById('modal');
                if (!modal.classList.contains('hidden')) {
                    closeModal();
                }
            }
        });

        // Main tab switching
        function switchMainTab(tab) {
            document.querySelectorAll('.main-tab').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.main-content').forEach(el => el.classList.remove('active'));
            
            if (tab === 'settings') {
                document.querySelector('.main-tab:first-child').classList.add('active');
                document.getElementById('main-settings').classList.add('active');
            } else {
                document.querySelector('.main-tab:last-child').classList.add('active');
                document.getElementById('main-analysis').classList.add('active');
            }
        }

        // Load strategy parameters when strategy is selected
        document.getElementById('analysisStrategy').addEventListener('change', function() {
            const strategyName = this.value;
            if (!strategyName) {
                document.getElementById('strategyParams').style.display = 'none';
                return;
            }

            // Get strategy settings
            fetch(`/api/strategy-settings/${strategyName}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.setting) {
                        const params = data.setting.parameters || {};
                        const container = document.getElementById('paramsContainer');
                        container.innerHTML = '';

                        Object.keys(params).forEach(key => {
                            const div = document.createElement('div');
                            div.className = 'input-group';
                            div.innerHTML = `
                                <label class="input-label">${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</label>
                                <input type="number" 
                                       class="input" 
                                       name="params[${key}]"
                                       value="${params[key]}"
                                       step="0.01"
                                       placeholder="${params[key]}">
                            `;
                            container.appendChild(div);
                        });

                        document.getElementById('strategyParams').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error loading strategy params:', error);
                    showModal('error', 'Ошибка', 'Не удалось загрузить параметры стратегии.', null, true);
                });
        });

        // Run analysis
        async function runAnalysis(event) {
            event.preventDefault();
            
            const strategy = document.getElementById('analysisStrategy').value;
            const coin = document.getElementById('analysisCoin').value.toUpperCase();
            const formData = new FormData(event.target);
            const params = {};

            formData.forEach((value, key) => {
                if (key.startsWith('params[')) {
                    const paramKey = key.match(/params\[(.*?)\]/)[1];
                    params[paramKey] = parseFloat(value) || value;
                }
            });

            const analyzeButton = document.getElementById('analyzeButton');
            analyzeButton.disabled = true;
            analyzeButton.textContent = '⏳ Анализирую...';

            showNotification('🤖 Запускаю анализ...', 'info');

            try {
                const response = await fetch('/api/strategy-analysis/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        strategy: strategy,
                        symbol: coin,
                        parameters: params
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Ошибка анализа');
                }

                renderAnalysisResult(data.result, coin);
                showNotification('✅ Анализ завершен!', 'success');

            } catch (error) {
                console.error('Analysis error:', error);
                showModal('error', 'Ошибка', 'Не удалось выполнить анализ: ' + error.message, null, true);
            } finally {
                analyzeButton.disabled = false;
                analyzeButton.textContent = '🚀 Запустить анализ';
            }
        }

        // Render analysis result
        function renderAnalysisResult(result, coin) {
            const container = document.getElementById('analysisResult');
            container.classList.remove('hidden');

            const longProb = result.long_probability || 0;
            const shortProb = result.short_probability || 0;

            container.innerHTML = `
                <div class="card analysis-card">
                    <div class="card-title">📊 Результаты анализа: ${coin}</div>
                    
                    <!-- Probabilities -->
                    <div style="margin: 20px 0;">
                        <div class="probability-card">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #10b981;">📈 Вероятность LONG</span>
                                <span style="font-weight: bold; font-size: 18px; color: #10b981;">${longProb}%</span>
                            </div>
                            <div class="probability-bar">
                                <div class="probability-fill probability-long" style="width: ${longProb}%">
                                    ${longProb >= 50 ? longProb + '%' : ''}
                                </div>
                            </div>
                        </div>

                        <div class="probability-card">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <span style="font-weight: 600; color: #ef4444;">📉 Вероятность SHORT</span>
                                <span style="font-weight: bold; font-size: 18px; color: #ef4444;">${shortProb}%</span>
                            </div>
                            <div class="probability-bar">
                                <div class="probability-fill probability-short" style="width: ${shortProb}%">
                                    ${shortProb >= 50 ? shortProb + '%' : ''}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Technical Data -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin: 20px 0;">
                        ${result.price ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">Цена</div>
                                <div style="font-size: 20px; font-weight: bold; color: #10b981;">$${result.price.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.rsi !== undefined ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">RSI (15m)</div>
                                <div style="font-size: 20px; font-weight: bold; color: #a855f7;">${result.rsi.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.rsi_htf !== undefined ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">RSI HTF (1h)</div>
                                <div style="font-size: 20px; font-weight: bold; color: #a855f7;">${result.rsi_htf.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.rsi_ltf !== undefined ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">RSI LTF (5m)</div>
                                <div style="font-size: 20px; font-weight: bold; color: #a855f7;">${result.rsi_ltf.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.ema ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">EMA (15m)</div>
                                <div style="font-size: 20px; font-weight: bold; color: #3b82f6;">$${result.ema.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.ema_htf ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">EMA HTF (1h)</div>
                                <div style="font-size: 20px; font-weight: bold; color: #3b82f6;">$${result.ema_htf.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.bb_upper && result.bb_lower ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">BB Верх/Низ</div>
                                <div style="font-size: 14px; font-weight: bold; color: #ec4899;">
                                    $${result.bb_upper.toFixed(2)} / $${result.bb_lower.toFixed(2)}
                                </div>
                            </div>
                        ` : ''}
                        ${result.atr ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">ATR</div>
                                <div style="font-size: 20px; font-weight: bold; color: #fbbf24;">${result.atr.toFixed(2)}</div>
                            </div>
                        ` : ''}
                        ${result.signal ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">Сигнал</div>
                                <div style="font-size: 20px; font-weight: bold; color: ${result.signal === 'BUY' ? '#10b981' : result.signal === 'SELL' ? '#ef4444' : '#94a3b8'};">
                                    ${result.signal === 'BUY' ? '📈 LONG' : result.signal === 'SELL' ? '📉 SHORT' : '⏸ HOLD'}
                                </div>
                            </div>
                        ` : ''}
                        ${result.strength ? `
                            <div style="background: rgba(15, 23, 42, 0.6); padding: 12px; border-radius: 8px; text-align: center;">
                                <div style="font-size: 11px; color: #94a3b8; margin-bottom: 4px;">Сила</div>
                                <div style="font-size: 20px; font-weight: bold; color: ${result.strength === 'STRONG' ? '#10b981' : result.strength === 'MEDIUM' ? '#fbbf24' : '#94a3b8'};">
                                    ${result.strength}
                                </div>
                            </div>
                        ` : ''}
                    </div>

                    ${result.reason ? `
                        <div style="margin-top: 20px; padding: 12px; background: rgba(15, 23, 42, 0.6); border-radius: 8px;">
                            <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px; color: #a855f7;">💡 Обоснование</div>
                            <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5;">${result.reason}</div>
                        </div>
                    ` : ''}
                </div>

                <!-- TradingView Chart -->
                <div class="chart-container">
                    <div class="card-title">📈 График ${coin}</div>
                    <div class="tradingview-widget-container">
                        <div id="tradingview_chart_${coin}"></div>
                    </div>
                </div>
            `;

            // Initialize TradingView chart
            setTimeout(() => {
                if (typeof TradingView !== 'undefined') {
                    new TradingView.widget({
                        "width": "100%",
                        "height": 500,
                        "symbol": `BINANCE:${coin}USDT`,
                        "interval": "60",
                        "timezone": "Etc/UTC",
                        "theme": "dark",
                        "style": "1",
                        "locale": "ru",
                        "toolbar_bg": "#1e293b",
                        "enable_publishing": false,
                        "allow_symbol_change": true,
                        "container_id": `tradingview_chart_${coin}`,
                        "hide_side_toolbar": false,
                        "studies": [
                            "RSI@tv-basicstudies",
                            "EMA@tv-basicstudies"
                        ]
                    });
                } else {
                    console.error('TradingView script not loaded');
                    showModal('warning', 'Предупреждение', 'Не удалось загрузить график TradingView. Попробуйте обновить страницу.', null, true);
                }
            }, 100);
        }

        // Start Free Trial button handler for strategy-settings
        function handleStartFreeTrial(e) {
            e.preventDefault();
            e.stopPropagation();
            console.log('Start Free Trial button clicked');
            
            const btn = document.getElementById('startFreeTrialBtn');
            const messageDiv = document.getElementById('subscriptionMessage');
            
            if (!btn) {
                console.error('Start Free Trial button not found');
                return;
            }
            
            btn.disabled = true;
            btn.style.opacity = '0.6';
            btn.style.cursor = 'not-allowed';
            if (messageDiv) {
                messageDiv.style.display = 'block';
                messageDiv.style.color = '#94a3b8';
                messageDiv.textContent = '⏳ Активация пробного периода...';
            }
            
            fetch('{{ route("strategy-settings.free-trial") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    if (messageDiv) {
                        messageDiv.style.color = '#10b981';
                        messageDiv.textContent = '✅ ' + data.message + ' Действует до ' + data.subscription.date_to;
                    }
                    
                    // Скрываем кнопку Start Free Trial
                    btn.style.display = 'none';
                    
                    // Перезагружаем страницу через 2 секунды
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
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
                    const errorMsg = error.error || error.message || 'Ошибка при активации пробного периода';
                    messageDiv.textContent = '❌ ' + errorMsg;
                }
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
            });
        }

    </script>
    
    <!-- Modal Script (if not already included) -->
    <script src="{{ asset('js/modal.js') }}"></script>
    
    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>

