/**
 * Modal System - Replace all console.log, alert, confirm with modals
 */

// Create modal HTML if it doesn't exist
function initModal() {
    if (document.getElementById('modal')) {
        return; // Modal already exists
    }

    const modalHTML = `
        <div id="modal" class="modal-overlay hidden" style="display: none;">
            <div id="modalContent" class="modal">
                <div class="modal-header">
                    <span id="modalIcon" class="modal-icon">⚠️</span>
                    <h3 id="modalTitle" class="modal-title">Заголовок</h3>
                </div>
                <div id="modalBody" class="modal-body">Сообщение</div>
                <div id="modalFooter" class="modal-footer">
                    <button class="modal-btn modal-btn-primary" onclick="closeModal()">ОК</button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Add modal styles if not already added
    if (!document.getElementById('modal-styles')) {
        const styles = document.createElement('style');
        styles.id = 'modal-styles';
        styles.textContent = `
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.8);
                backdrop-filter: blur(8px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                padding: 20px;
            }

            .modal-overlay.hidden {
                display: none !important;
            }

            .modal {
                background: rgba(30, 41, 59, 0.95);
                border: 1px solid rgba(168, 85, 247, 0.3);
                border-radius: 20px;
                padding: 24px;
                max-width: 400px;
                width: 100%;
                max-height: 90vh;
                overflow-y: auto;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            }

            .modal.modal-success {
                border-color: rgba(16, 185, 129, 0.5);
            }

            .modal.modal-error {
                border-color: rgba(239, 68, 68, 0.5);
            }

            .modal.modal-warning {
                border-color: rgba(251, 191, 36, 0.5);
            }

            .modal.modal-info {
                border-color: rgba(59, 130, 246, 0.5);
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
                background: linear-gradient(to right, #a855f7, #ec4899);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                margin: 0;
            }

            .modal-body {
                color: #cbd5e1;
                font-size: 14px;
                line-height: 1.6;
                margin-bottom: 20px;
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
                background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
                color: #ffffff;
            }

            .modal-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(168, 85, 247, 0.4);
            }

            .modal-btn-secondary {
                background: rgba(30, 41, 59, 0.8);
                border: 1px solid rgba(168, 85, 247, 0.3);
                color: #cbd5e1;
            }

            .modal-btn-secondary:hover {
                background: rgba(30, 41, 59, 1);
                border-color: rgba(168, 85, 247, 0.5);
            }
        `;
        document.head.appendChild(styles);
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
            if (modal && !modal.classList.contains('hidden')) {
                closeModal();
            }
        }
    });
}

// Show modal
function showModal(type, title, message, onConfirm = null, singleButton = false) {
    initModal();

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
            const btn = document.getElementById('modalConfirmBtn');
            btn.onclick = () => {
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

// Close modal
function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
}

// Replace alert
window.alert = function(message) {
    showModal('info', 'Уведомление', message, null, true);
};

// Replace confirm - Returns a Promise (async)
// For legacy code that expects synchronous confirm, use showConfirmModalSync
let confirmResolve = null;

window.confirm = function(message) {
    return new Promise((resolve) => {
        confirmResolve = resolve;
        showModal('warning', 'Подтверждение', message, () => {
            if (confirmResolve) {
                confirmResolve(true);
                confirmResolve = null;
            }
        }, false);
        // Handle cancel button
        setTimeout(() => {
            const cancelBtn = document.querySelector('.modal-btn-secondary');
            if (cancelBtn) {
                cancelBtn.onclick = () => {
                    closeModal();
                    if (confirmResolve) {
                        confirmResolve(false);
                        confirmResolve = null;
                    }
                };
            }
        }, 100);
    });
};

// Synchronous confirm alternative (for legacy code that can't use async)
window.showConfirmModalSync = function(message, onConfirm, onCancel) {
    showModal('warning', 'Подтверждение', message, onConfirm || (() => {}), false);
    setTimeout(() => {
        const cancelBtn = document.querySelector('.modal-btn-secondary');
        if (cancelBtn) {
            cancelBtn.onclick = () => {
                closeModal();
                if (onCancel) onCancel();
            };
        }
    }, 100);
};

// Replace console.error with modal
const originalConsoleError = console.error;
console.error = function(...args) {
    originalConsoleError.apply(console, args); // Still log to console
    const message = args.map(arg => typeof arg === 'object' ? JSON.stringify(arg) : String(arg)).join(' ');
    showModal('error', 'Ошибка', message, null, true);
};

// Make functions globally available
window.showModal = showModal;
window.closeModal = closeModal;

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModal);
} else {
    initModal();
}

