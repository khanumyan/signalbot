<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Анализ графиков - Crypto Trading Bot</title>
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
            max-width: 600px;
            margin: 0 auto;
            padding: 0 16px 100px 16px;
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
            margin-bottom: 12px;
        }

        .header-title {
            font-size: 18px;
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 12px;
        }

        .stat-card {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            padding: 8px;
            text-align: center;
        }

        .stat-label {
            font-size: 11px;
            color: #94a3b8;
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 14px;
            font-weight: bold;
        }

        /* Upload Button */
        .upload-section {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
        }

        .upload-button {
            width: 100%;
            background: linear-gradient(to right, #9333ea, #db2777);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: transform 0.2s;
        }

        .upload-button:active {
            transform: scale(0.98);
        }

        .upload-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
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

        /* Analysis Result */
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

        .signal-long {
            background: #10b981;
        }

        .signal-short {
            background: #ef4444;
        }

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

        /* Buttons */
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 8px 0;
            transition: transform 0.2s;
        }

        .btn-primary {
            background: linear-gradient(to right, #10b981, #059669);
            color: white;
        }

        .btn-secondary {
            background: rgba(71, 85, 105, 0.8);
            color: white;
        }

        .btn:active {
            transform: scale(0.98);
        }

        /* Charts List */
        .chart-item {
            background: rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            padding: 12px;
            margin: 8px 0;
        }

        .chart-item img {
            width: 100%;
            border-radius: 8px;
            margin: 8px 0;
            max-height: 400px;
            object-fit: contain;
        }

        /* Loading */
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

        /* Notification */
        .notification {
            position: fixed;
            top: 80px;
            left: 16px;
            right: 16px;
            padding: 16px;
            border-radius: 12px;
            backdrop-filter: blur(12px);
            z-index: 1000;
            animation: slideDown 0.3s ease-out;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-100px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
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

        .notification.info {
            background: rgba(59, 130, 246, 0.9);
            border: 1px solid #3b82f6;
        }

        .hidden {
            display: none;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }

        .preview-image {
            width: 100%;
            border-radius: 8px;
            margin: 16px 0;
            max-height: 400px;
            object-fit: contain;
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
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="header-title">AI Анализ графиков</span>
                        </div>
                        <div style="font-size: 11px; color: #94a3b8; margin-top: 4px;">Загрузите график для анализа</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div id="notification" class="notification hidden"></div>

    <!-- Main Content -->
    <div class="container">
        <!-- Upload Section -->
        <div class="upload-section">
            <input type="file" id="fileInput" accept="image/*" style="display: none;">
            <button class="upload-button" id="uploadButton" onclick="document.getElementById('fileInput').click()">
                <span>📤</span>
                <span>Загрузить график</span>
            </button>
            <div style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 8px;">
                Загрузите скриншот графика с биржи
            </div>
        </div>

        <!-- Preview Image -->
        <div id="previewSection" class="hidden">
            <div class="card">
                <div class="card-title">📊 Загруженный график</div>
                <img id="previewImage" class="preview-image" alt="Preview">
                <button class="btn btn-primary" id="analyzeButton" onclick="analyzeChart()">
                    🤖 Анализировать график
                </button>
            </div>
        </div>

        <!-- Analysis Result -->
        <div id="analysisResult" class="hidden"></div>
        
        <!-- Charts List -->
        <div id="chartsList"></div>
        
        <!-- Empty State -->
        <div id="emptyState" class="empty-state">
            <div class="empty-icon">📊</div>
            <div style="color: #94a3b8; font-size: 16px;">Загрузите график для анализа</div>
            <div style="color: #64748b; font-size: 14px; margin-top: 8px;">AI даст точные рекомендации</div>
        </div>
    </div>

    <script>
        let currentImageFile = null;
        let charts = [];

        // Load charts from localStorage
        try {
            const saved = localStorage.getItem('chartAnalysis');
            if (saved) {
                charts = JSON.parse(saved);
                renderCharts();
            }
        } catch (e) {}

        // Save charts
        function saveCharts() {
            try {
                localStorage.setItem('chartAnalysis', JSON.stringify(charts));
            } catch (e) {}
        }

        // Show notification
        function showNotification(message, type = 'info') {
            const notif = document.getElementById('notification');
            notif.textContent = message;
            notif.className = `notification ${type}`;
            setTimeout(() => notif.className = 'notification hidden', 3000);
        }

        // File upload handler
        document.getElementById('fileInput').addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            if (!file.type.startsWith('image/')) {
                showNotification('⚠️ Загрузите изображение', 'error');
                return;
            }

            const reader = new FileReader();
            reader.onload = (event) => {
                currentImageFile = file;
                document.getElementById('previewImage').src = event.target.result;
                document.getElementById('previewSection').classList.remove('hidden');
                document.getElementById('emptyState').classList.add('hidden');
                showNotification('✅ График загружен', 'success');
            };
            reader.readAsDataURL(file);
        });

        // Analyze chart
        async function analyzeChart() {
            if (!currentImageFile) {
                showNotification('⚠️ Сначала загрузите график', 'error');
                return;
            }

            const analyzeButton = document.getElementById('analyzeButton');
            const uploadButton = document.getElementById('uploadButton');
            
            analyzeButton.disabled = true;
            uploadButton.disabled = true;
            analyzeButton.textContent = '🤖 Анализирую...';

            showNotification('🤖 Анализирую график...', 'info');

            const loadingHtml = '<div class="card"><div class="spinner"></div><div style="text-align: center; color: #94a3b8;">Анализирую график...</div></div>';
            document.getElementById('analysisResult').innerHTML = loadingHtml;
            document.getElementById('analysisResult').classList.remove('hidden');

            try {
                const formData = new FormData();
                formData.append('image', currentImageFile);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                const response = await fetch('{{ route("chart-analysis.analyze") }}', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    throw new Error(data.error || 'Ошибка анализа');
                }

                const analysis = data.analysis;

                // Save to charts list
                const chart = {
                    id: Date.now(),
                    name: currentImageFile.name,
                    data: document.getElementById('previewImage').src,
                    analysis: analysis,
                    analyzedAt: new Date().toLocaleTimeString('ru-RU')
                };
                charts.unshift(chart);
                saveCharts();
                renderCharts();

                // Render analysis
                renderAnalysis(analysis);
                showNotification(`✅ ${analysis.crypto}: ${analysis.signal}`, 'success');

            } catch (error) {
                console.error(error);
                showModal('error', 'Ошибка анализа', 'Не удалось проанализировать график: ' + error.message, null, true);
                showNotification('❌ Ошибка анализа: ' + error.message, 'error');
                document.getElementById('analysisResult').innerHTML = `
                    <div class="card" style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.3);">
                        <div style="text-align: center; color: #fca5a5;">
                            ❌ Не удалось проанализировать график<br>
                            <small>${error.message}</small>
                        </div>
                    </div>
                `;
            } finally {
                analyzeButton.disabled = false;
                uploadButton.disabled = false;
                analyzeButton.textContent = '🤖 Анализировать график';
            }
        }

        // Render analysis
        function renderAnalysis(analysis) {
            const profitPercent = ((parseFloat(analysis.take_profit_1) - parseFloat(analysis.entry_price)) / parseFloat(analysis.entry_price) * 100).toFixed(2);
            const lossPercent = Math.abs((parseFloat(analysis.entry_price) - parseFloat(analysis.stop_loss)) / parseFloat(analysis.entry_price) * 100).toFixed(2);
            const rr = (profitPercent / lossPercent).toFixed(2);

            document.getElementById('analysisResult').innerHTML = `
                <div class="card analysis-card">
                    <div class="crypto-header">
                        <div>
                            <div style="font-size: 20px; font-weight: bold; color: #a855f7;">${analysis.crypto}</div>
                            <div style="font-size: 12px; color: #cbd5e1; margin-top: 4px;">Текущая цена</div>
                            <div class="crypto-price">$${analysis.current_price}</div>
                        </div>
                        <div>
                            <div class="signal-badge signal-${analysis.signal.toLowerCase()}">
                                ${analysis.signal === 'LONG' ? '📈 LONG' : '📉 SHORT'}
                            </div>
                            <div style="text-align: center; font-size: 11px; color: #cbd5e1; margin-top: 4px;">
                                Сила: ${analysis.signal_strength}/10
                            </div>
                        </div>
                    </div>

                    <div class="trade-levels">
                        <div style="font-size: 16px; font-weight: bold; margin-bottom: 12px;">🎯 КУДА СТАВИТЬ</div>
                        
                        <div class="level level-entry">
                            <span class="level-label" style="color: #a855f7;">💰 Вход</span>
                            <span class="level-value">$${analysis.entry_price}</span>
                        </div>

                        <div class="level level-sl">
                            <span class="level-label" style="color: #f87171;">🛡️ Stop Loss</span>
                            <span class="level-value" style="color: #f87171;">$${analysis.stop_loss}</span>
                        </div>

                        <div style="font-size: 14px; font-weight: 600; color: #34d399; margin: 12px 0 8px 0;">
                            🎯 Take Profit цели
                        </div>

                        <div class="level level-tp">
                            <span class="level-label" style="color: #34d399;">TP1 (33%)</span>
                            <span class="level-value" style="color: #34d399;">$${analysis.take_profit_1}</span>
                        </div>

                        <div class="level level-tp">
                            <span class="level-label" style="color: #34d399;">TP2 (33%)</span>
                            <span class="level-value" style="color: #34d399;">$${analysis.take_profit_2}</span>
                        </div>

                        <div class="level level-tp">
                            <span class="level-label" style="color: #34d399;">TP3 (остаток)</span>
                            <span class="level-value" style="color: #34d399;">$${analysis.take_profit_3}</span>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin: 16px 0;">
                        <div style="background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 11px; color: #94a3b8;">Прибыль</div>
                            <div style="font-size: 14px; font-weight: bold; color: #10b981;">+${profitPercent}%</div>
                        </div>
                        <div style="background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 11px; color: #94a3b8;">R/R</div>
                            <div style="font-size: 14px; font-weight: bold; color: #10b981;">1:${rr}</div>
                        </div>
                        <div style="background: rgba(15, 23, 42, 0.6); border-radius: 8px; padding: 8px; text-align: center;">
                            <div style="font-size: 11px; color: #94a3b8;">Плечо</div>
                            <div style="font-size: 14px; font-weight: bold; color: #ec4899;">${analysis.leverage}x</div>
                        </div>
                    </div>

                    <div style="margin-top: 16px; padding: 12px; background: rgba(15, 23, 42, 0.6); border-radius: 8px;">
                        <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">📊 Анализ</div>
                        <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5;">${analysis.technical_analysis || 'Анализ недоступен'}</div>
                        <div style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-top: 8px;">${analysis.why_enter || 'Обоснование недоступно'}</div>
                    </div>
                </div>
            `;
            document.getElementById('emptyState').classList.add('hidden');
        }

        // Render charts list
        function renderCharts() {
            const container = document.getElementById('chartsList');
            if (charts.length === 0) {
                return;
            }

            container.innerHTML = `
                <div class="card">
                    <div class="card-title">📊 История анализов (${charts.length})</div>
                    ${charts.map(chart => `
                        <div class="chart-item">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <div style="flex: 1; overflow: hidden;">
                                    <div style="font-size: 14px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${chart.name}</div>
                                    <div style="font-size: 11px; color: #94a3b8;">${chart.analyzedAt}</div>
                                </div>
                                <span style="font-size: 11px; background: #10b981; padding: 4px 8px; border-radius: 4px;">✓ Проанализирован</span>
                            </div>
                            <img src="${chart.data}" alt="${chart.name}">
                            ${chart.analysis ? `
                                <div style="margin-top: 12px; padding: 12px; background: rgba(15, 23, 42, 0.6); border-radius: 8px;">
                                    <div style="font-size: 14px; font-weight: 600; margin-bottom: 8px;">
                                        ${chart.analysis.crypto} - 
                                        <span class="signal-badge signal-${chart.analysis.signal.toLowerCase()}" style="font-size: 12px; padding: 4px 8px;">
                                            ${chart.analysis.signal}
                                        </span>
                                    </div>
                                    <div style="font-size: 12px; color: #94a3b8;">
                                        Вход: $${chart.analysis.entry_price} | 
                                        SL: $${chart.analysis.stop_loss} | 
                                        TP: $${chart.analysis.take_profit_1}
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    `).join('')}
                </div>
            `;
        }

        // Initialize
        renderCharts();
    </script>
    
    <!-- Modal Script -->
    <script src="{{ asset('js/modal.js') }}"></script>
    
    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>



