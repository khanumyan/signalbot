/**
 * Telegram Web App Integration
 * This script initializes the Telegram Web App API and provides helper functions
 */

(function() {
    'use strict';

    // Check if running in Telegram Web App
    const isTelegramWebApp = typeof window.Telegram !== 'undefined' && window.Telegram.WebApp;

    if (isTelegramWebApp) {
        const tg = window.Telegram.WebApp;
        
        // Initialize Telegram Web App
        tg.ready();
        tg.expand();

        // Set theme colors to match app design
        tg.setHeaderColor('#0f172a'); // Dark header
        tg.setBackgroundColor('#0a0a0a'); // Dark background

        // Enable closing confirmation
        tg.enableClosingConfirmation();

        // Make Telegram Web App available globally
        window.tg = tg;

        // Log user info (for debugging)
        if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
            console.log('Telegram User:', tg.initDataUnsafe.user);
        }

        // Add CSS to hide scrollbars if needed (optional)
        const style = document.createElement('style');
        style.textContent = `
            body {
                padding-top: env(safe-area-inset-top);
                padding-bottom: env(safe-area-inset-bottom);
            }
        `;
        document.head.appendChild(style);

        // Helper function to get user data
        window.getTelegramUser = function() {
            if (tg.initDataUnsafe && tg.initDataUnsafe.user) {
                return {
                    id: tg.initDataUnsafe.user.id,
                    first_name: tg.initDataUnsafe.user.first_name,
                    last_name: tg.initDataUnsafe.user.last_name,
                    username: tg.initDataUnsafe.user.username,
                    language_code: tg.initDataUnsafe.user.language_code,
                    is_premium: tg.initDataUnsafe.user.is_premium || false
                };
            }
            return null;
        };

        // Helper function to show main button
        window.showTelegramMainButton = function(text, callback) {
            tg.MainButton.setText(text);
            tg.MainButton.show();
            tg.MainButton.onClick(callback);
        };

        // Helper function to hide main button
        window.hideTelegramMainButton = function() {
            tg.MainButton.hide();
        };

        // Helper function to show back button
        window.showTelegramBackButton = function(callback) {
            tg.BackButton.show();
            tg.BackButton.onClick(callback);
        };

        // Helper function to hide back button
        window.hideTelegramBackButton = function() {
            tg.BackButton.hide();
        };

        // Helper function to show alert
        window.showTelegramAlert = function(message, callback) {
            tg.showAlert(message, callback);
        };

        // Helper function to show confirm
        window.showTelegramConfirm = function(message, callback) {
            tg.showConfirm(message, callback);
        };

        // Helper function to close app
        window.closeTelegramApp = function() {
            tg.close();
        };

        // Helper function to send data to bot
        window.sendDataToTelegram = function(data) {
            tg.sendData(JSON.stringify(data));
        };

        // Helper function to open link
        window.openTelegramLink = function(url) {
            tg.openLink(url);
        };

        // Helper function to open invoice
        window.openTelegramInvoice = function(invoiceUrl, callback) {
            tg.openInvoice(invoiceUrl, callback);
        };

        // Listen for viewport changes
        tg.onEvent('viewportChanged', function() {
            console.log('Viewport changed');
        });

        // Listen for theme changes
        tg.onEvent('themeChanged', function() {
            console.log('Theme changed');
            // Update app theme if needed
        });

        console.log('Telegram Web App initialized successfully');
    } else {
        console.log('Not running in Telegram Web App');
        // Provide fallback functions for development
        window.tg = {
            ready: function() {},
            expand: function() {},
            setHeaderColor: function() {},
            setBackgroundColor: function() {},
            enableClosingConfirmation: function() {},
            MainButton: {
                setText: function() {},
                show: function() {},
                hide: function() {},
                onClick: function() {}
            },
            BackButton: {
                show: function() {},
                hide: function() {},
                onClick: function() {}
            },
            showAlert: function(msg, cb) { 
                if (typeof showModal !== 'undefined') {
                    showModal('info', 'Уведомление', msg, cb, true);
                } else {
                    alert(msg); 
                    if (cb) cb(); 
                }
            },
            showConfirm: function(msg, cb) { 
                if (typeof showModal !== 'undefined') {
                    showModal('warning', 'Подтверждение', msg, () => {
                        if (cb) cb(true);
                    }, false);
                    setTimeout(() => {
                        const cancelBtn = document.querySelector('.modal-btn-secondary');
                        if (cancelBtn && cb) {
                            cancelBtn.onclick = () => {
                                closeModal();
                                cb(false);
                            };
                        }
                    }, 100);
                } else {
                    if (cb) cb(confirm(msg)); 
                }
            },
            close: function() {},
            sendData: function() {},
            openLink: function(url) { window.open(url, '_blank'); },
            openInvoice: function() {},
            onEvent: function() {}
        };
    }
})();

