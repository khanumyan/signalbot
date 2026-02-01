<!DOCTYPE html>
<html lang="ru">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Личный кабинет - Traiding Helper Pro</title>
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
            text-align: center;
            padding: 40px 16px 30px 16px;
            position: relative;
        }

        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 8px 16px;
            color: #a855f7;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: rgba(168, 85, 247, 0.2);
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

        .header-subtitle {
            font-size: 16px;
            color: #94a3b8;
        }

        /* Profile Sections */
        .profile-section {
            background: rgba(30, 30, 30, 0.6);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .profile-section:hover {
            background: rgba(30, 30, 30, 0.8);
            border-color: rgba(168, 85, 247, 0.6);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(168, 85, 247, 0.2);
        }

        .profile-section:active {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.15);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #ffffff;
        }

        .section-content {
            min-height: 20px;
        }

        /* Wallet Modal */
        .wallet-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .wallet-modal.active {
            display: flex;
        }

        .wallet-modal-content {
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            width: 100%;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wallet-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 8px 12px;
            color: #a855f7;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .wallet-modal-close:hover {
            background: rgba(168, 85, 247, 0.2);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .wallet-info {
            margin-top: 24px;
        }

        .wallet-balance {
            text-align: center;
            margin-bottom: 32px;
        }

        .wallet-balance-label {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .wallet-balance-amount {
            font-size: 48px;
            font-weight: bold;
            background: linear-gradient(to right, #a855f7, #ec4899);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .wallet-balance-currency {
            font-size: 20px;
            color: #94a3b8;
            margin-left: 8px;
        }

        .wallet-withdrawal-info {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            color: #94a3b8;
            font-size: 14px;
            line-height: 1.6;
        }

        .wallet-loading {
            text-align: center;
            padding: 20px;
            color: #94a3b8;
        }

        /* Referral Modal - используем те же стили что и для wallet */
        .referral-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .referral-modal.active {
            display: flex;
        }

        .referral-modal-content {
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            width: 100%;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .referral-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 8px 12px;
            color: #a855f7;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .referral-modal-close:hover {
            background: rgba(168, 85, 247, 0.2);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .referral-info {
            margin-top: 24px;
        }

        .referral-text {
            text-align: center;
            font-size: 18px;
            color: #ffffff;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .referral-link-container {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .referral-link-label {
            font-size: 12px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .referral-link {
            font-size: 14px;
            color: #a855f7;
            word-break: break-all;
            font-family: monospace;
        }

        .copy-button {
            width: 100%;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 24px;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .copy-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(168, 85, 247, 0.3);
        }

        .copy-button:active {
            transform: translateY(0);
        }

        .copy-button.copied {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        /* Subscriptions Modal */
        .subscriptions-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .subscriptions-modal.active {
            display: flex;
        }

        .subscriptions-modal-content {
            background: rgba(30, 30, 30, 0.95);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 32px;
            max-width: 600px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            animation: slideIn 0.3s ease;
        }

        .subscriptions-modal-close {
            position: absolute;
            top: 16px;
            right: 16px;
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            padding: 8px 12px;
            color: #a855f7;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .subscriptions-modal-close:hover {
            background: rgba(168, 85, 247, 0.2);
            border-color: rgba(168, 85, 247, 0.5);
        }

        .subscriptions-info {
            margin-top: 24px;
        }

        .subscription-item {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .subscription-item:last-child {
            margin-bottom: 0;
        }

        .subscription-product-name {
            font-size: 18px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 12px;
        }

        .subscription-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 14px;
            color: #94a3b8;
        }

        .subscription-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .subscription-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .subscription-status.active {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
        }

        .subscription-status.in-active {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .subscriptions-empty {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }

        .subscriptions-empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .subscriptions-empty-text {
            font-size: 16px;
        }

        /* Logout Button */
        .logout-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(168, 85, 247, 0.2);
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .logout-btn {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 14px 32px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s ease;
            cursor: pointer;
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.5);
            transform: translateY(-2px);
        }

    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <a href="/" class="back-button">← Назад</a>
        <div class="header-title">👤 Личный кабинет</div>
        <div class="header-subtitle">{{ $user->name }}</div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Кошелек -->
        <div class="profile-section" id="wallet-section" onclick="loadWallet()">
            <div class="section-title">
                💰 Кошелек
            </div>
            <div class="section-content">
            </div>
        </div>

        <!-- Реферальная программа -->
        <div class="profile-section" id="referral-section" onclick="loadReferral()">
            <div class="section-title">
                🎁 Реферальная программа
            </div>
            <div class="section-content">
            </div>
        </div>

        <!-- История подписок -->
        <div class="profile-section" id="subscriptions-section" onclick="loadSubscriptions()">
            <div class="section-title">
                📋 История подписок
            </div>
            <div class="section-content">
            </div>
        </div>
    </div>

    <!-- Logout Button -->
    <div class="logout-container">
        <form method="POST" action="{{ route('logout') }}" style="width: 100%; max-width: 400px;">
            @csrf
            <button type="submit" class="logout-btn">
                🚪 Выйти
            </button>
        </form>
    </div>

    <!-- Wallet Modal -->
    <div class="wallet-modal" id="wallet-modal">
        <div class="wallet-modal-content">
            <div class="wallet-modal-close" onclick="closeWalletModal()">×</div>
            <div class="section-title" style="margin-bottom: 0;">
                💰 Кошелек
            </div>
            <div class="wallet-info" id="wallet-info">
                <div class="wallet-loading">Загрузка...</div>
            </div>
        </div>
    </div>

    <!-- Referral Modal -->
    <div class="referral-modal" id="referral-modal">
        <div class="referral-modal-content">
            <div class="referral-modal-close" onclick="closeReferralModal()">×</div>
            <div class="section-title" style="margin-bottom: 0;">
                🎁 Реферальная программа
            </div>
            <div class="referral-info" id="referral-info">
                <div class="wallet-loading">Загрузка...</div>
            </div>
        </div>
    </div>

    <!-- Subscriptions Modal -->
    <div class="subscriptions-modal" id="subscriptions-modal">
        <div class="subscriptions-modal-content">
            <div class="subscriptions-modal-close" onclick="closeSubscriptionsModal()">×</div>
            <div class="section-title" style="margin-bottom: 0;">
                📋 История подписок
            </div>
            <div class="subscriptions-info" id="subscriptions-info">
                <div class="wallet-loading">Загрузка...</div>
            </div>
        </div>
    </div>

    <script>
        function loadWallet() {
            const modal = document.getElementById('wallet-modal');
            const walletInfo = document.getElementById('wallet-info');
            
            modal.classList.add('active');
            walletInfo.innerHTML = '<div class="wallet-loading">Загрузка...</div>';
            
            fetch('{{ route("profile.wallet") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const wallet = data.wallet;
                    walletInfo.innerHTML = `
                        <div class="wallet-balance">
                            <div class="wallet-balance-label">Баланс</div>
                            <div>
                                <span class="wallet-balance-amount">${parseFloat(wallet.amount).toFixed(2)}</span>
                                <span class="wallet-balance-currency">${wallet.currency}</span>
                            </div>
                        </div>
                        <div class="wallet-withdrawal-info">
                            Для вывода средств откройте этот бот
                        </div>
                    `;
                } else {
                    walletInfo.innerHTML = '<div class="wallet-loading" style="color: #fca5a5;">Ошибка загрузки данных</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                walletInfo.innerHTML = '<div class="wallet-loading" style="color: #fca5a5;">Ошибка загрузки данных</div>';
            });
        }

        function closeWalletModal() {
            const modal = document.getElementById('wallet-modal');
            modal.classList.remove('active');
        }

        // Закрытие модального окна при клике вне его
        document.getElementById('wallet-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeWalletModal();
            }
        });

        function loadReferral() {
            const modal = document.getElementById('referral-modal');
            const referralInfo = document.getElementById('referral-info');
            
            modal.classList.add('active');
            referralInfo.innerHTML = '<div class="wallet-loading">Загрузка...</div>';
            
            fetch('{{ route("profile.referral-link") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const referralLink = data.referral_link;
                    referralInfo.innerHTML = `
                        <div class="referral-text">
                            Пригласи друзей и собери бонусы от них
                        </div>
                        <div class="referral-link-container">
                            <div class="referral-link-label">Ваша реферальная ссылка:</div>
                            <div class="referral-link" id="referral-link-text">${referralLink}</div>
                        </div>
                        <button class="copy-button" id="copy-button" onclick="copyReferralLink('${referralLink}')">
                            📋 Копировать ссылку
                        </button>
                    `;
                } else {
                    referralInfo.innerHTML = '<div class="wallet-loading" style="color: #fca5a5;">Ошибка загрузки данных</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                referralInfo.innerHTML = '<div class="wallet-loading" style="color: #fca5a5;">Ошибка загрузки данных</div>';
            });
        }

        function copyReferralLink(link) {
            const button = document.getElementById('copy-button');
            const originalText = button.textContent;
            
            // Копируем ссылку в буфер обмена
            navigator.clipboard.writeText(link).then(() => {
                button.textContent = '✓ Скопировано!';
                button.classList.add('copied');
                
                setTimeout(() => {
                    button.textContent = originalText;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(err => {
                // Fallback для старых браузеров
                const textArea = document.createElement('textarea');
                textArea.value = link;
                textArea.style.position = 'fixed';
                textArea.style.opacity = '0';
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    button.textContent = '✓ Скопировано!';
                    button.classList.add('copied');
                    
                    setTimeout(() => {
                        button.textContent = originalText;
                        button.classList.remove('copied');
                    }, 2000);
                } catch (err) {
                    console.error('Failed to copy:', err);
                    alert('Не удалось скопировать ссылку');
                }
                document.body.removeChild(textArea);
            });
        }

        function closeReferralModal() {
            const modal = document.getElementById('referral-modal');
            modal.classList.remove('active');
        }

        // Закрытие модального окна реферальной программы при клике вне его
        document.getElementById('referral-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReferralModal();
            }
        });

        function loadSubscriptions() {
            const modal = document.getElementById('subscriptions-modal');
            const subscriptionsInfo = document.getElementById('subscriptions-info');
            
            modal.classList.add('active');
            subscriptionsInfo.innerHTML = '<div class="wallet-loading">Загрузка...</div>';
            
            fetch('{{ route("profile.subscriptions") }}', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const subscriptions = data.subscriptions;
                    
                    if (subscriptions.length === 0) {
                        subscriptionsInfo.innerHTML = `
                            <div class="subscriptions-empty">
                                <div class="subscriptions-empty-icon">📭</div>
                                <div class="subscriptions-empty-text">У вас пока нет подписок</div>
                            </div>
                        `;
                    } else {
                        let subscriptionsHtml = '';
                        subscriptions.forEach(subscription => {
                            const statusClass = subscription.status === 'active' ? 'active' : 'in-active';
                            const statusText = subscription.status === 'active' ? 'Активна' : 'Неактивна';
                            
                            subscriptionsHtml += `
                                <div class="subscription-item">
                                    <div class="subscription-product-name">${subscription.product_name}</div>
                                    <div class="subscription-details">
                                        <div class="subscription-detail-row">
                                            <span>Период:</span>
                                            <span>${subscription.date_from} - ${subscription.date_to}</span>
                                        </div>
                                        <div class="subscription-detail-row">
                                            <span>Статус:</span>
                                            <span class="subscription-status ${statusClass}">${statusText}</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        subscriptionsInfo.innerHTML = subscriptionsHtml;
                    }
                } else {
                    subscriptionsInfo.innerHTML = '<div class="wallet-loading" style="color: #fca5a5;">Ошибка загрузки данных</div>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                subscriptionsInfo.innerHTML = '<div class="wallet-loading" style="color: #fca5a5;">Ошибка загрузки данных</div>';
            });
        }

        function closeSubscriptionsModal() {
            const modal = document.getElementById('subscriptions-modal');
            modal.classList.remove('active');
        }

        // Закрытие модального окна подписок при клике вне его
        document.getElementById('subscriptions-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSubscriptionsModal();
            }
        });
    </script>
</body>
</html>




