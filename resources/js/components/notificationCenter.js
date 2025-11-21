/**
 * Notification & Alert Center Component (Phase 8)
 *
 * Real-time alert management and notification display
 */

export default function notificationCenter() {
    return {
        // State
        orgId: null,
        loading: false,
        error: null,
        isOpen: false,

        // Data
        alerts: [],
        unreadCount: 0,
        filter: {
            severity: 'all',
            type: 'all',
            status: 'active'
        },

        // Pagination
        page: 1,
        limit: 20,
        hasMore: true,

        /**
         * Initialize component
         */
        init() {
            this.orgId = this.$el.dataset.orgId;

            if (!this.orgId) {
                this.error = 'Organization ID is required';
                return;
            }

            // Load alerts
            this.loadAlerts();

            // Listen for new anomaly detections
            window.addEventListener('anomaly-detected', (event) => {
                this.handleNewAnomalyAlert(event.detail);
            });

            // Poll for new alerts every 30 seconds
            setInterval(() => {
                this.loadAlerts(true);
            }, 30000);
        },

        /**
         * Load alerts
         */
        async loadAlerts(silent = false) {
            if (!silent) {
                this.loading = true;
            }
            this.error = null;

            try {
                const params = new URLSearchParams({
                    limit: this.limit,
                    status: this.filter.status
                });

                if (this.filter.severity !== 'all') {
                    params.append('severity', this.filter.severity);
                }

                if (this.filter.type !== 'all') {
                    params.append('type', this.filter.type);
                }

                const response = await fetch(
                    `/api/orgs/${this.orgId}/enterprise/alerts?${params}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.alerts = data.alerts || [];
                    this.calculateUnreadCount();
                } else {
                    this.error = data.error || 'Failed to load alerts';
                }

            } catch (err) {
                console.error('Alerts load error:', err);
                if (!silent) {
                    this.error = err.message;
                }
            } finally {
                this.loading = false;
            }
        },

        /**
         * Handle new anomaly alert
         */
        handleNewAnomalyAlert(detail) {
            // Add new alert to the list
            const newAlert = {
                alert_id: 'temp-' + Date.now(),
                type: 'anomaly_detected',
                severity: 'high',
                message: detail.message,
                status: 'active',
                created_at: new Date().toISOString(),
                data: detail.data
            };

            this.alerts.unshift(newAlert);
            this.unreadCount++;

            // Show browser notification if permitted
            this.showBrowserNotification(detail.message);

            // Reload from server
            setTimeout(() => {
                this.loadAlerts(true);
            }, 2000);
        },

        /**
         * Calculate unread count
         */
        calculateUnreadCount() {
            this.unreadCount = this.alerts.filter(alert =>
                alert.status === 'active' && !alert.acknowledged_at
            ).length;
        },

        /**
         * Acknowledge alert
         */
        async acknowledgeAlert(alertId) {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/enterprise/alerts/${alertId}/acknowledge`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        },
                        body: JSON.stringify({
                            acknowledged_by: this.getUserId(),
                            notes: 'Acknowledged via notification center'
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to acknowledge alert');
                }

                // Update local state
                const alert = this.alerts.find(a => a.alert_id === alertId);
                if (alert) {
                    alert.status = 'acknowledged';
                    alert.acknowledged_at = new Date().toISOString();
                    this.calculateUnreadCount();
                }

            } catch (err) {
                console.error('Acknowledge error:', err);
            }
        },

        /**
         * Resolve alert
         */
        async resolveAlert(alertId) {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/enterprise/alerts/${alertId}/resolve`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        },
                        body: JSON.stringify({
                            resolved_by: this.getUserId(),
                            resolution_notes: 'Resolved via notification center'
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to resolve alert');
                }

                // Remove from local state
                this.alerts = this.alerts.filter(a => a.alert_id !== alertId);
                this.calculateUnreadCount();

            } catch (err) {
                console.error('Resolve error:', err);
            }
        },

        /**
         * Show browser notification
         */
        showBrowserNotification(message) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('CMIS Alert', {
                    body: message,
                    icon: '/images/logo.png',
                    tag: 'cmis-alert'
                });
            }
        },

        /**
         * Request notification permission
         */
        async requestNotificationPermission() {
            if ('Notification' in window && Notification.permission === 'default') {
                await Notification.requestPermission();
            }
        },

        /**
         * Toggle notification center
         */
        toggle() {
            this.isOpen = !this.isOpen;

            if (this.isOpen && this.alerts.length === 0) {
                this.loadAlerts();
            }
        },

        /**
         * Get severity badge class
         */
        getSeverityBadgeClass(severity) {
            const classes = {
                'critical': 'bg-red-100 text-red-800 border-red-200',
                'high': 'bg-orange-100 text-orange-800 border-orange-200',
                'medium': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'low': 'bg-blue-100 text-blue-800 border-blue-200'
            };
            return classes[severity] || 'bg-gray-100 text-gray-800 border-gray-200';
        },

        /**
         * Get severity icon
         */
        getSeverityIcon(severity) {
            const icons = {
                'critical': '🔴',
                'high': '🟠',
                'medium': '🟡',
                'low': '🔵'
            };
            return icons[severity] || 'ℹ️';
        },

        /**
         * Format relative time
         */
        formatRelativeTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffMins = Math.floor(diffMs / 60000);
            const diffHours = Math.floor(diffMs / 3600000);
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffMins < 1) return 'Just now';
            if (diffMins < 60) return `${diffMins}m ago`;
            if (diffHours < 24) return `${diffHours}h ago`;
            return `${diffDays}d ago`;
        },

        /**
         * Get auth token
         */
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        /**
         * Get user ID
         */
        getUserId() {
            return localStorage.getItem('user_id') || '';
        }
    };
}
