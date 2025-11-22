/**
 * Enhanced Flash Messages
 * Issue #8: Success messages persist longer and are dismissible
 *
 * Usage:
 * x-data="{ ...flashMessages() }"
 */

export function flashMessages() {
    return {
        messages: [],

        init() {
            this.loadExistingMessages();
            this.startAutoHide();
        },

        loadExistingMessages() {
            // Load server-side flash messages
            const successMsg = document.querySelector('[data-flash-success]');
            const errorMsg = document.querySelector('[data-flash-error]');
            const warningMsg = document.querySelector('[data-flash-warning]');
            const infoMsg = document.querySelector('[data-flash-info]');

            if (successMsg) {
                this.addMessage('success', successMsg.textContent, 10000); // 10 seconds
            }
            if (errorMsg) {
                this.addMessage('error', errorMsg.textContent, 0); // Never auto-hide errors
            }
            if (warningMsg) {
                this.addMessage('warning', warningMsg.textContent, 15000); // 15 seconds
            }
            if (infoMsg) {
                this.addMessage('info', infoMsg.textContent, 8000); // 8 seconds
            }
        },

        addMessage(type, message, duration = 8000) {
            const id = Date.now() + Math.random();
            this.messages.push({
                id,
                type,
                message,
                duration,
                dismissible: true,
                persistent: duration === 0
            });

            if (duration > 0) {
                setTimeout(() => this.removeMessage(id), duration);
            }
        },

        removeMessage(id) {
            this.messages = this.messages.filter(msg => msg.id !== id);
        },

        getIcon(type) {
            const icons = {
                success: 'fa-check-circle',
                error: 'fa-exclamation-circle',
                warning: 'fa-exclamation-triangle',
                info: 'fa-info-circle'
            };
            return icons[type] || 'fa-info-circle';
        },

        getColorClasses(type) {
            const classes = {
                success: 'bg-green-50 border-green-500 text-green-800',
                error: 'bg-red-50 border-red-500 text-red-800',
                warning: 'bg-yellow-50 border-yellow-500 text-yellow-800',
                info: 'bg-blue-50 border-blue-500 text-blue-800'
            };
            return classes[type] || classes.info;
        },

        startAutoHide() {
            // No action needed - auto-hide happens in addMessage
        }
    };
}
