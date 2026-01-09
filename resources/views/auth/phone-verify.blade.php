<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Подтверждение - Trading Helper Bot</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 420px;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-image {
            max-width: 200px;
            width: 100%;
            height: auto;
            filter: drop-shadow(0 4px 16px rgba(168, 85, 247, 0.4));
        }

        .auth-card {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 20px;
            padding: 32px;
            backdrop-filter: blur(10px);
        }

        .auth-title {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-align: center;
            margin-bottom: 8px;
        }

        .auth-subtitle {
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 24px;
        }

        .code-display {
            background: rgba(15, 23, 42, 0.6);
            border: 2px solid rgba(168, 85, 247, 0.5);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 24px;
        }

        .code-label {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .code-value {
            font-size: 32px;
            font-weight: bold;
            color: #a855f7;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
        }

        .phone-display {
            text-align: center;
            color: #cbd5e1;
            font-size: 16px;
            margin-bottom: 24px;
        }

        .telegram-button {
            width: 100%;
            padding: 14px;
            background: #0088cc;
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .telegram-button:hover {
            background: #006ba3;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 136, 204, 0.4);
        }

        .telegram-button:active {
            transform: translateY(0);
        }

        .instructions {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .instructions-title {
            font-size: 14px;
            font-weight: 600;
            color: #a855f7;
            margin-bottom: 12px;
        }

        .instructions-list {
            list-style: none;
            padding: 0;
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.6;
        }

        .instructions-list li {
            margin-bottom: 8px;
            padding-left: 24px;
            position: relative;
        }

        .instructions-list li:before {
            content: "→";
            position: absolute;
            left: 0;
            color: #a855f7;
        }

        .status-message {
            text-align: center;
            padding: 12px;
            border-radius: 10px;
            margin-top: 16px;
            font-size: 14px;
            display: none;
        }

        .status-message.success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            display: block;
        }

        .status-message.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            display: block;
        }

        .loading {
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-container">
            <img src="{{ asset('images/erasebg-transformed (1).png') }}" alt="Trading Helper Bot Logo" class="logo-image" onerror="this.style.display='none';">
        </div>

        <div class="auth-card">
            <h1 class="auth-title">Подтверждение номера</h1>
            <p class="auth-subtitle">Откройте Telegram бота и введите код подтверждения</p>

            <div class="code-display">
                <div class="code-label">Код подтверждения</div>
                <div class="code-value" id="verificationCode">{{ $verification_code }}</div>
            </div>

            <div class="phone-display">
                📱 {{ $phone }}
            </div>

            <div class="instructions">
                <div class="instructions-title">Инструкция:</div>
                <ol class="instructions-list">
                    <li>Нажмите кнопку "Открыть Telegram бота" ниже</li>
                    <li>В открывшемся боте введите код подтверждения</li>
                    <li>После подтверждения вы будете автоматически авторизованы</li>
                </ol>
            </div>

            <a href="{{ $telegram_url }}" class="telegram-button" target="_blank" id="telegramButton">
                📱 Открыть Telegram бота
            </a>

            <div class="status-message" id="statusMessage"></div>
            <div class="loading" id="loadingMessage" style="display: none;">
                Проверка статуса...
            </div>
        </div>
    </div>

    <script>
        const token = '{{ $token }}';
        const checkUrl = '{{ route("phone.auth.check") }}';
        let checkInterval = null;

        function checkVerification() {
            fetch(checkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ token: token })
            })
            .then(response => response.json())
            .then(data => {
                if (data.verified) {
                    document.getElementById('statusMessage').textContent = '✅ Авторизация успешна! Перенаправление...';
                    document.getElementById('statusMessage').className = 'status-message success';
                    document.getElementById('loadingMessage').style.display = 'none';
                    
                    if (checkInterval) {
                        clearInterval(checkInterval);
                    }
                    
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);
                }
            })
            .catch(error => {
                console.error('Error checking verification:', error);
            });
        }

        // Start checking verification status every 2 seconds
        checkInterval = setInterval(checkVerification, 2000);
        
        // Also check immediately
        checkVerification();

        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (checkInterval) {
                clearInterval(checkInterval);
            }
        });
    </script>
</body>
</html>


