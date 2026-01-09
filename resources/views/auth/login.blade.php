<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход - Trading Helper Bot</title>
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

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 24px;
        }

        .remember-me input {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .remember-me label {
            font-size: 14px;
            color: #cbd5e1;
            cursor: pointer;
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

        .auth-link {
            text-align: center;
            margin-top: 20px;
        }

        .auth-link a {
            color: #a855f7;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s ease;
        }

        .auth-link a:hover {
            color: #ec4899;
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
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="logo-container">
            <img src="{{ asset('images/erasebg-transformed (1).png') }}" alt="Trading Helper Bot Logo" class="logo-image" onerror="this.style.display='none';">
        </div>

        <div class="auth-card">
            <h1 class="auth-title">Вход</h1>
            <p class="auth-subtitle">Войдите в свой аккаунт</p>

            @if ($errors->any())
                <div class="error-message">
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        placeholder="your@email.com"
                        value="{{ old('email') }}"
                        required 
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Пароль</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-input" 
                        placeholder="••••••••"
                        required
                    >
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember" value="1">
                    <label for="remember">Запомнить меня</label>
                </div>

                <button type="submit" class="btn-primary">
                    Войти
                </button>
            </form>

            <div class="auth-link">
                Нет аккаунта? <a href="{{ route('register') }}">Зарегистрироваться</a>
            </div>
            <div class="auth-link" style="margin-top: 12px;">
                <a href="{{ route('phone.auth.show') }}" style="color: #0088cc;">📱 Войти через Telegram</a>
            </div>
        </div>
    </div>

    <!-- Modal Script -->
    <script src="{{ asset('js/modal.js') }}"></script>
    
    <!-- Telegram Web App Script -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="{{ asset('js/telegram-web-app.js') }}"></script>
</body>
</html>

