/**
 * Data Exports Management Component (Phase 14)
 *
 * Alpine.js component for managing data export configurations, API tokens, and export logs
 */

export default function dataExports() {
    return {
        // State
        configs: [],
        logs: [],
        tokens: [],
        stats: {},
        selectedConfig: null,
        selectedLog: null,
        loading: false,
        showCreateModal: false,
        showTokenModal: false,
        showTokenValue: false,
        newToken: null,
        activeTab: 'configs', // 'configs', 'logs', 'tokens', 'stats'

        // Form
        form: {
            name: '',
            description: '',
            export_type: 'analytics',
            format: 'json',
            delivery_method: 'download',
            data_config: {
                date_range: {
                    start: '',
                    end: ''
                },
                status: []
            },
            delivery_config: {},
            schedule: null,
            is_active: true
        },

        // Token form
        tokenForm: {
            name: '',
            scopes: ['analytics:read'],
            expires_at: null
        },

        // Pagination
        currentPage: 1,
        totalPages: 1,

        // Filters
        filters: {
            export_type: null,
            format: null,
            delivery_method: null,
            status: null,
            active: null
        },

        // Initialize
        async init() {
            this.orgId = this.$el.dataset.orgId;
            await this.loadConfigs();
            await this.loadStats();
        },

        // Load configurations
        async loadConfigs() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 15
                });

                if (this.filters.export_type) params.append('export_type', this.filters.export_type);
                if (this.filters.format) params.append('format', this.filters.format);
                if (this.filters.delivery_method) params.append('delivery_method', this.filters.delivery_method);
                if (this.filters.active !== null) params.append('active', this.filters.active);

                const response = await fetch(
                    `/api/orgs/${this.orgId}/exports/configs?${params}`,
                    { headers: { 'Authorization': `Bearer ${this.getAuthToken()}` } }
                );

                const data = await response.json();
                if (data.success) {
                    this.configs = data.configs.data;
                    this.currentPage = data.configs.current_page;
                    this.totalPages = data.configs.last_page;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_configs'), error);
            } finally {
                this.loading = false;
            }
        },

        // Load export logs
        async loadLogs() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 20
                });

                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.format) params.append('format', this.filters.format);

                const response = await fetch(
                    `/api/orgs/${this.orgId}/exports/logs?${params}`,
                    { headers: { 'Authorization': `Bearer ${this.getAuthToken()}` } }
                );

                const data = await response.json();
                if (data.success) {
                    this.logs = data.logs.data;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_logs'), error);
            } finally {
                this.loading = false;
            }
        },

        // Load API tokens
        async loadTokens() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/api-tokens`, {
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                const data = await response.json();
                if (data.success) {
                    this.tokens = data.tokens.data;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_tokens'), error);
            } finally {
                this.loading = false;
            }
        },

        // Load statistics
        async loadStats() {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/exports/stats?days=30`, {
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                const data = await response.json();
                if (data.success) {
                    this.stats = data.stats;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_stats'), error);
            }
        },

        // Create configuration
        async createConfig() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/exports/configs`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.export_config_created'));
                    this.showCreateModal = false;
                    this.resetForm();
                    await this.loadConfigs();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_create_config'));
            } finally {
                this.loading = false;
            }
        },

        // Update configuration
        async updateConfig(config) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/exports/configs/${config.config_id}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ is_active: !config.is_active })
                });

                if (response.ok) {
                    config.is_active = !config.is_active;
                    this.showSuccess(`Configuration ${config.is_active ? 'activated' : 'deactivated'}`);
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_update_config'));
            }
        },

        // Delete configuration
        async deleteConfig(configId) {
            if (!confirm(__('javascript.confirm_delete_export_config'))) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/exports/configs/${configId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                if (response.ok) {
                    this.showSuccess(__('javascript.export_config_deleted'));
                    await this.loadConfigs();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_delete_config'));
            }
        },

        // Execute export
        async executeExport(configId = null, async = true) {
            try {
                const payload = configId
                    ? { config_id: configId, async }
                    : {
                        export_type: this.form.export_type,
                        format: this.form.format,
                        data_config: this.form.data_config,
                        async
                    };

                const response = await fetch(`/api/orgs/${this.orgId}/exports/execute`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();
                if (data.success) {
                    if (async) {
                        this.showSuccess(__('javascript.export_queued'));
                    } else {
                        this.showSuccess(__('javascript.export_completed'));
                        if (data.download_url) {
                            window.location.href = data.download_url;
                        }
                    }
                    await this.loadLogs();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_execute_export'));
            }
        },

        // Download export
        downloadExport(logId) {
            const url = `/api/orgs/${this.orgId}/exports/download/${logId}`;
            const a = document.createElement('a');
            a.href = url;
            a.download = true;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        // Create API token
        async createToken() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/api-tokens`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.tokenForm)
                });

                const data = await response.json();
                if (data.success) {
                    this.newToken = data.plaintext_token;
                    this.showTokenValue = true;
                    this.showSuccess(__('javascript.api_token_created'));
                    await this.loadTokens();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_create_token'));
            } finally {
                this.loading = false;
            }
        },

        // Revoke API token
        async revokeToken(tokenId) {
            if (!confirm(__('javascript.confirm_revoke_token'))) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/api-tokens/${tokenId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                if (response.ok) {
                    this.showSuccess(__('javascript.token_revoked'));
                    await this.loadTokens();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_revoke_token'));
            }
        },

        // Copy to clipboard
        copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                this.showSuccess(__('javascript.copied_to_clipboard'));
            }).catch(() => {
                this.showError(__('javascript.failed_to_copy'));
            });
        },

        // Switch tabs
        async switchTab(tab) {
            this.activeTab = tab;
            if (tab === 'logs') {
                await this.loadLogs();
            } else if (tab === 'tokens') {
                await this.loadTokens();
            } else if (tab === 'stats') {
                await this.loadStats();
            }
        },

        // Schedule helpers
        enableSchedule() {
            this.form.schedule = {
                frequency: 'daily',
                time: '09:00',
                day_of_week: null,
                day_of_month: null
            };
        },

        disableSchedule() {
            this.form.schedule = null;
        },

        // Delivery method changed
        onDeliveryMethodChange() {
            this.form.delivery_config = {};

            switch (this.form.delivery_method) {
                case 'webhook':
                    this.form.delivery_config = { url: '', headers: {} };
                    break;
                case 'sftp':
                    this.form.delivery_config = { host: '', port: 22, username: '', path: '' };
                    break;
                case 's3':
                    this.form.delivery_config = { bucket: '', region: 'us-east-1', prefix: '' };
                    break;
            }
        },

        // Utilities
        resetForm() {
            this.form = {
                name: '',
                description: '',
                export_type: 'analytics',
                format: 'json',
                delivery_method: 'download',
                data_config: {
                    date_range: { start: '', end: '' },
                    status: []
                },
                delivery_config: {},
                schedule: null,
                is_active: true
            };
        },

        resetTokenForm() {
            this.tokenForm = {
                name: '',
                scopes: ['analytics:read'],
                expires_at: null
            };
            this.newToken = null;
            this.showTokenValue = false;
        },

        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        getStatusColor(status) {
            const colors = {
                'completed': 'green',
                'processing': 'blue',
                'failed': 'red',
                'queued': 'yellow'
            };
            return colors[status] || 'gray';
        },

        getFormatIcon(format) {
            const icons = {
                'json': '{ }',
                'csv': 'CSV',
                'xlsx': 'XLS',
                'parquet': 'PQT'
            };
            return icons[format] || format.toUpperCase();
        },

        formatBytes(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        formatNumber(num) {
            return new Intl.NumberFormat().format(num);
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
