/**
 * Alerts Management Component (Phase 13)
 *
 * Alpine.js component for managing real-time alert rules and history
 */

export default function alertsManagement() {
    return {
        // State
        rules: [],
        alerts: [],
        templates: [],
        selectedRule: null,
        selectedAlert: null,
        loading: false,
        showCreateModal: false,
        showTemplateModal: false,
        activeTab: 'rules', // 'rules', 'history', 'templates'

        // Form
        form: {
            name: '',
            description: '',
            entity_type: 'campaign',
            entity_id: null,
            metric: 'ctr',
            condition: 'lt',
            threshold: 0,
            time_window_minutes: 60,
            severity: 'medium',
            notification_channels: ['email', 'in_app'],
            notification_config: {
                email: { recipients: [] },
                in_app: { user_ids: [] }
            },
            cooldown_minutes: 60,
            is_active: true
        },

        // Pagination
        currentPage: 1,
        totalPages: 1,

        // Filters
        filters: {
            entity_type: null,
            severity: null,
            active: null,
            status: null
        },

        // Initialize
        async init() {
            this.orgId = this.$el.dataset.orgId;
            await this.loadRules();
            await this.loadTemplates();
        },

        // Load rules
        async loadRules() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 15
                });

                if (this.filters.entity_type) params.append('entity_type', this.filters.entity_type);
                if (this.filters.severity) params.append('severity', this.filters.severity);
                if (this.filters.active !== null) params.append('active', this.filters.active);

                const response = await fetch(
                    `/api/orgs/${this.orgId}/alerts/rules?${params}`,
                    { headers: { 'Authorization': `Bearer ${this.getAuthToken()}` } }
                );

                const data = await response.json();
                if (data.success) {
                    this.rules = data.rules.data;
                    this.currentPage = data.rules.current_page;
                    this.totalPages = data.rules.last_page;
                }
            } catch (error) {
                console.error('Failed to load rules:', error);
            } finally {
                this.loading = false;
            }
        },

        // Load alert history
        async loadAlerts() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 20
                });

                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.severity) params.append('severity', this.filters.severity);

                const response = await fetch(
                    `/api/orgs/${this.orgId}/alerts/history?${params}`,
                    { headers: { 'Authorization': `Bearer ${this.getAuthToken()}` } }
                );

                const data = await response.json();
                if (data.success) {
                    this.alerts = data.alerts.data;
                }
            } catch (error) {
                console.error('Failed to load alerts:', error);
            } finally {
                this.loading = false;
            }
        },

        // Load templates
        async loadTemplates() {
            try {
                const response = await fetch('/api/alerts/templates', {
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                const data = await response.json();
                if (data.success) {
                    this.templates = data.templates;
                }
            } catch (error) {
                console.error('Failed to load templates:', error);
            }
        },

        // Create rule
        async createRule() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/alerts/rules`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess('Alert rule created');
                    this.showCreateModal = false;
                    this.resetForm();
                    await this.loadRules();
                }
            } catch (error) {
                this.showError('Failed to create rule');
            } finally {
                this.loading = false;
            }
        },

        // Toggle rule active status
        async toggleActive(rule) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/alerts/rules/${rule.rule_id}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ is_active: !rule.is_active })
                });

                if (response.ok) {
                    rule.is_active = !rule.is_active;
                    this.showSuccess(`Rule ${rule.is_active ? 'activated' : 'deactivated'}`);
                }
            } catch (error) {
                this.showError('Failed to update rule');
            }
        },

        // Delete rule
        async deleteRule(ruleId) {
            if (!confirm('Delete this alert rule?')) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/alerts/rules/${ruleId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                if (response.ok) {
                    this.showSuccess('Rule deleted');
                    await this.loadRules();
                }
            } catch (error) {
                this.showError('Failed to delete rule');
            }
        },

        // Acknowledge alert
        async acknowledgeAlert(alertId) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/alerts/${alertId}/acknowledge`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    this.showSuccess('Alert acknowledged');
                    await this.loadAlerts();
                }
            } catch (error) {
                this.showError('Failed to acknowledge alert');
            }
        },

        // Resolve alert
        async resolveAlert(alertId) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/alerts/${alertId}/resolve`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                if (response.ok) {
                    this.showSuccess('Alert resolved');
                    await this.loadAlerts();
                }
            } catch (error) {
                this.showError('Failed to resolve alert');
            }
        },

        // Snooze alert
        async snoozeAlert(alertId, minutes = 60) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/alerts/${alertId}/snooze`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ minutes })
                });

                if (response.ok) {
                    this.showSuccess(`Alert snoozed for ${minutes} minutes`);
                    await this.loadAlerts();
                }
            } catch (error) {
                this.showError('Failed to snooze alert');
            }
        },

        // Switch tabs
        async switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'history') {
                await this.loadAlerts();
            }
        },

        // Utilities
        resetForm() {
            this.form = {
                name: '',
                description: '',
                entity_type: 'campaign',
                entity_id: null,
                metric: 'ctr',
                condition: 'lt',
                threshold: 0,
                time_window_minutes: 60,
                severity: 'medium',
                notification_channels: ['email', 'in_app'],
                notification_config: { email: { recipients: [] }, in_app: { user_ids: [] } },
                cooldown_minutes: 60,
                is_active: true
            };
        },

        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        getSeverityColor(severity) {
            const colors = {
                'critical': 'red',
                'high': 'orange',
                'medium': 'yellow',
                'low': 'blue'
            };
            return colors[severity] || 'gray';
        },

        getStatusColor(status) {
            const colors = {
                'new': 'red',
                'acknowledged': 'yellow',
                'resolved': 'green',
                'snoozed': 'gray'
            };
            return colors[status] || 'gray';
        },

        showSuccess(message) {
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { type: 'success', message }
            }));
        },

        showError(message) {
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { type: 'error', message }
            }));
        }
    };
}
