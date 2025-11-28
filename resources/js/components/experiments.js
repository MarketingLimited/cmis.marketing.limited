/**
 * Experiments Management Component (Phase 15)
 *
 * Alpine.js component for A/B testing experiment management
 */

export default function experiments() {
    return {
        // State
        experiments: [],
        selectedExperiment: null,
        variants: [],
        results: null,
        stats: {},
        loading: false,
        showCreateModal: false,
        showVariantModal: false,
        showResultsModal: false,
        activeTab: 'list', // 'list', 'running', 'completed', 'stats'

        // Form
        form: {
            name: '',
            description: '',
            experiment_type: 'campaign',
            entity_type: '',
            entity_id: null,
            metric: 'conversion_rate',
            metrics: [],
            hypothesis: '',
            duration_days: 14,
            sample_size_per_variant: 1000,
            confidence_level: 95.00,
            minimum_detectable_effect: 5.00,
            traffic_allocation: 'equal',
            control_config: {}
        },

        // Variant form
        variantForm: {
            name: '',
            description: '',
            traffic_percentage: 50.00,
            config: {}
        },

        // Filters
        filters: {
            status: null,
            experiment_type: null
        },

        // Pagination
        currentPage: 1,
        totalPages: 1,

        // Initialize
        async init() {
            this.orgId = this.$el.dataset.orgId;
            await this.loadExperiments();
            await this.loadStats();
        },

        // Load experiments
        async loadExperiments() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 15
                });

                if (this.filters.status) params.append('status', this.filters.status);
                if (this.filters.experiment_type) params.append('experiment_type', this.filters.experiment_type);

                const response = await fetch(
                    `/api/orgs/${this.orgId}/experiments?${params}`,
                    { headers: { 'Authorization': `Bearer ${this.getAuthToken()}` } }
                );

                const data = await response.json();
                if (data.success) {
                    this.experiments = data.experiments.data;
                    this.currentPage = data.experiments.current_page;
                    this.totalPages = data.experiments.last_page;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_experiments'), error);
            } finally {
                this.loading = false;
            }
        },

        // Load statistics
        async loadStats() {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/stats`, {
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

        // Create experiment
        async createExperiment() {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.form)
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.experiment_created'));
                    this.showCreateModal = false;
                    this.resetForm();
                    await this.loadExperiments();
                    await this.loadStats();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_create_experiment'));
            } finally {
                this.loading = false;
            }
        },

        // View experiment details
        async viewExperiment(experimentId) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}`, {
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                const data = await response.json();
                if (data.success) {
                    this.selectedExperiment = data.experiment;
                    this.variants = data.experiment.variants;
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_load_experiment'));
            }
        },

        // Add variant
        async addVariant(experimentId) {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}/variants`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(this.variantForm)
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.variant_added'));
                    this.showVariantModal = false;
                    this.resetVariantForm();
                    await this.viewExperiment(experimentId);
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_add_variant'));
            } finally {
                this.loading = false;
            }
        },

        // Start experiment
        async startExperiment(experimentId) {
            if (!confirm(__('javascript.confirm_start_experiment'))) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}/start`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.experiment_started'));
                    await this.loadExperiments();
                    await this.loadStats();
                } else {
                    this.showError(data.message);
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_start_experiment'));
            }
        },

        // Pause experiment
        async pauseExperiment(experimentId) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}/pause`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.experiment_paused'));
                    await this.loadExperiments();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_pause_experiment'));
            }
        },

        // Resume experiment
        async resumeExperiment(experimentId) {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}/resume`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.experiment_resumed'));
                    await this.loadExperiments();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_resume_experiment'));
            }
        },

        // Complete experiment
        async completeExperiment(experimentId) {
            if (!confirm(__('javascript.confirm_complete_experiment'))) return;

            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}/complete`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();
                if (data.success) {
                    this.showSuccess(__('javascript.experiment_completed'));
                    await this.loadExperiments();
                    await this.loadStats();

                    // Show results
                    if (data.winner) {
                        alert(__('javascript.experiment_winner_found', {
                            name: data.winner.name,
                            improvement: data.winner.improvement_over_control
                        }));
                    } else {
                        alert(__('javascript.no_significant_winner'));
                    }
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_complete_experiment'));
            } finally {
                this.loading = false;
            }
        },

        // Delete experiment
        async deleteExperiment(experimentId) {
            if (!confirm(__('javascript.confirm_delete_experiment'))) return;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}`, {
                    method: 'DELETE',
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                if (response.ok) {
                    this.showSuccess(__('javascript.experiment_deleted'));
                    await this.loadExperiments();
                    await this.loadStats();
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_delete_experiment'));
            }
        },

        // View results
        async viewResults(experimentId) {
            this.loading = true;
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/experiments/${experimentId}/results`, {
                    headers: { 'Authorization': `Bearer ${this.getAuthToken()}` }
                });

                const data = await response.json();
                if (data.success) {
                    this.results = data;
                    this.showResultsModal = true;
                }
            } catch (error) {
                this.showError(__('javascript.failed_to_load_results'));
            } finally {
                this.loading = false;
            }
        },

        // Switch tabs
        async switchTab(tab) {
            this.activeTab = tab;

            if (tab === 'running') {
                this.filters.status = 'running';
                await this.loadExperiments();
            } else if (tab === 'completed') {
                this.filters.status = 'completed';
                await this.loadExperiments();
            } else if (tab === 'list') {
                this.filters.status = null;
                await this.loadExperiments();
            } else if (tab === 'stats') {
                await this.loadStats();
            }
        },

        // Utilities
        resetForm() {
            this.form = {
                name: '',
                description: '',
                experiment_type: 'campaign',
                entity_type: '',
                entity_id: null,
                metric: 'conversion_rate',
                metrics: [],
                hypothesis: '',
                duration_days: 14,
                sample_size_per_variant: 1000,
                confidence_level: 95.00,
                minimum_detectable_effect: 5.00,
                traffic_allocation: 'equal',
                control_config: {}
            };
        },

        resetVariantForm() {
            this.variantForm = {
                name: '',
                description: '',
                traffic_percentage: 50.00,
                config: {}
            };
        },

        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        getStatusColor(status) {
            const colors = {
                'draft': 'gray',
                'running': 'blue',
                'paused': 'yellow',
                'completed': 'green',
                'cancelled': 'red'
            };
            return colors[status] || 'gray';
        },

        getStatusIcon(status) {
            const icons = {
                'draft': '📝',
                'running': '▶️',
                'paused': '⏸️',
                'completed': '✅',
                'cancelled': '❌'
            };
            return icons[status] || '•';
        },

        formatNumber(num) {
            return new Intl.NumberFormat().format(num);
        },

        formatPercentage(num) {
            return `${parseFloat(num).toFixed(2)}%`;
        },

        formatDate(date) {
            return new Date(date).toLocaleDateString();
        },

        calculateProgress(experiment) {
            if (!experiment.started_at || !experiment.duration_days) {
                return 0;
            }

            const start = new Date(experiment.started_at);
            const now = new Date();
            const daysPassed = Math.floor((now - start) / (1000 * 60 * 60 * 24));
            const progress = (daysPassed / experiment.duration_days) * 100;

            return Math.min(100, Math.max(0, progress));
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
