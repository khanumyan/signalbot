<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Авторизация - Trading Helper Bot</title>
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
            font-size: 28px;
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

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            color: #cbd5e1;
            margin-bottom: 8px;
        }

        .form-input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 10px;
            color: #ffffff;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #a855f7;
            box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.1);
        }

        .form-input::placeholder {
            color: #64748b;
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            border: none;
            border-radius: 10px;
            color: #ffffff;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.4);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .error-message {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            color: #fca5a5;
            font-size: 14px;
        }

        .error-list {
            list-style: none;
            padding: 0;
        }

        .error-list li {
            margin-bottom: 4px;
        }

        .info-text {
            color: #94a3b8;
            font-size: 13px;
            margin-top: 8px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-container">
            <img src="{{ asset('images/erasebg-transformed (1).png') }}" alt="Trading Helper Bot Logo" class="logo-image" onerror="this.style.display='none';">
        </div>

        <div class="auth-card">
            <h1 class="auth-title">Авторизация</h1>
            <p class="auth-subtitle">Введите номер телефона для входа через Telegram</p>

            @if ($errors->any())
                <div class="error-message">
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('phone.auth.submit') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="phone">Номер телефона</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-input" 
                        placeholder="+1234567890"
                        value="{{ old('phone') }}"
                        required 
                        autofocus
                    >
                    <p class="info-text">Формат: +1234567890</p>
                </div>

                <button type="submit" class="btn-primary">
                    Продолжить
                </button>
            </form>
        </div>
    </div>
</body>
</html>


