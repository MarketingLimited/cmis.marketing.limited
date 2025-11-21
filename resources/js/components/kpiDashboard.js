/**
 * KPI Monitoring Dashboard Component (Phase 8)
 *
 * Real-time KPI tracking with status indicators and progress visualization
 */

export default function kpiDashboard() {
    return {
        // State
        orgId: null,
        entityType: 'campaign',
        entityId: null,
        loading: false,
        error: null,

        // Data
        kpis: [],
        summary: null,
        healthScore: 0,

        // Refresh
        autoRefresh: true,
        refreshInterval: 60000, // 1 minute
        refreshTimer: null,

        /**
         * Initialize component
         */
        init() {
            this.orgId = this.$el.dataset.orgId;
            this.entityType = this.$el.dataset.entityType || 'campaign';
            this.entityId = this.$el.dataset.entityId;

            if (!this.orgId || !this.entityId) {
                this.error = 'Organization ID and Entity ID are required';
                return;
            }

            // Initial load
            this.loadKPIs();

            // Start auto-refresh
            if (this.autoRefresh) {
                this.startAutoRefresh();
            }
        },

        /**
         * Load KPI dashboard
         */
        async loadKPIs() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/kpis/dashboard?entity_type=${this.entityType}&entity_id=${this.entityId}`,
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
                    this.kpis = data.kpis || [];
                    this.summary = data.summary || {};
                    this.healthScore = this.summary.health_score || 0;
                } else {
                    this.error = data.error || 'Failed to load KPIs';
                }

            } catch (err) {
                console.error('KPI load error:', err);
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }

            this.refreshTimer = setInterval(() => {
                this.loadKPIs();
            }, this.refreshInterval);
        },

        /**
         * Stop auto-refresh
         */
        stopAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
                this.refreshTimer = null;
            }
        },

        /**
         * Get status badge classes
         */
        getStatusBadgeClass(status) {
            const classes = {
                'exceeded': 'bg-green-100 text-green-800 border-green-200',
                'on_track': 'bg-blue-100 text-blue-800 border-blue-200',
                'at_risk': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'off_track': 'bg-red-100 text-red-800 border-red-200'
            };
            return classes[status] || 'bg-gray-100 text-gray-800 border-gray-200';
        },

        /**
         * Get status icon
         */
        getStatusIcon(status) {
            const icons = {
                'exceeded': '🎯',
                'on_track': '✅',
                'at_risk': '⚠️',
                'off_track': '❌'
            };
            return icons[status] || '📊';
        },

        /**
         * Get progress bar color
         */
        getProgressBarColor(status, progress) {
            if (progress >= 100) return 'bg-green-500';

            const colors = {
                'exceeded': 'bg-green-500',
                'on_track': 'bg-blue-500',
                'at_risk': 'bg-yellow-500',
                'off_track': 'bg-red-500'
            };
            return colors[status] || 'bg-gray-500';
        },

        /**
         * Get health score color
         */
        getHealthScoreColor(score) {
            if (score >= 80) return 'text-green-600';
            if (score >= 60) return 'text-blue-600';
            if (score >= 40) return 'text-yellow-600';
            if (score >= 20) return 'text-orange-600';
            return 'text-red-600';
        },

        /**
         * Get health score label
         */
        getHealthScoreLabel(score) {
            if (score >= 80) return 'Excellent';
            if (score >= 60) return 'Good';
            if (score >= 40) return 'Fair';
            if (score >= 20) return 'Poor';
            return 'Critical';
        },

        /**
         * Format number
         */
        formatNumber(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            }
            return num.toFixed(0);
        },

        /**
         * Format value based on KPI type
         */
        formatValue(value, kpi) {
            // Add formatting logic based on unit if available
            return this.formatNumber(value);
        },

        /**
         * Get auth token
         */
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        /**
         * Render component
         */
        render() {
            return `
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">KPI Dashboard</h2>
                            <p class="text-sm text-gray-600 mt-1">Performance tracking and monitoring</p>
                        </div>

                        <button @click="loadKPIs()"
                                :disabled="loading"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                            <span x-show="!loading">Refresh</span>
                            <span x-show="loading">Loading...</span>
                        </button>
                    </div>

                    <!-- Error Message -->
                    <div x-show="error"
                         class="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-red-800" x-text="error"></p>
                    </div>

                    <!-- Health Score Card -->
                    <div x-show="summary"
                         class="bg-white p-6 rounded-lg shadow">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Overall Health Score</h3>
                                <p class="text-sm text-gray-600 mt-1" x-text="getHealthScoreLabel(healthScore)"></p>
                            </div>
                            <div class="text-right">
                                <div class="text-4xl font-bold"
                                     :class="getHealthScoreColor(healthScore)"
                                     x-text="healthScore.toFixed(1)"></div>
                                <div class="text-sm text-gray-600">out of 100</div>
                            </div>
                        </div>

                        <!-- Status Distribution -->
                        <div class="mt-6 grid grid-cols-4 gap-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600"
                                     x-text="summary?.status_counts?.exceeded || 0"></div>
                                <div class="text-xs text-gray-600">Exceeded</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600"
                                     x-text="summary?.status_counts?.on_track || 0"></div>
                                <div class="text-xs text-gray-600">On Track</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-yellow-600"
                                     x-text="summary?.status_counts?.at_risk || 0"></div>
                                <div class="text-xs text-gray-600">At Risk</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-red-600"
                                     x-text="summary?.status_counts?.off_track || 0"></div>
                                <div class="text-xs text-gray-600">Off Track</div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <template x-for="kpi in kpis" :key="kpi.kpi_id">
                            <div class="bg-white rounded-lg shadow overflow-hidden">
                                <!-- KPI Header -->
                                <div class="px-6 py-4 border-b border-gray-200">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <h4 class="text-lg font-semibold text-gray-900" x-text="kpi.kpi_name"></h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border mt-2"
                                                  :class="getStatusBadgeClass(kpi.status)">
                                                <span x-text="getStatusIcon(kpi.status)" class="mr-1"></span>
                                                <span x-text="kpi.status.replace('_', ' ')"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- KPI Body -->
                                <div class="px-6 py-4 space-y-4">
                                    <!-- Current vs Target -->
                                    <div class="flex justify-between items-baseline">
                                        <div>
                                            <div class="text-sm text-gray-600">Current</div>
                                            <div class="text-2xl font-bold text-gray-900"
                                                 x-text="formatValue(kpi.current_value, kpi)"></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm text-gray-600">Target</div>
                                            <div class="text-xl font-semibold text-gray-700"
                                                 x-text="formatValue(kpi.target_value, kpi)"></div>
                                        </div>
                                    </div>

                                    <!-- Progress Bar -->
                                    <div>
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Progress</span>
                                            <span x-text="kpi.progress_percentage.toFixed(1) + '%'"></span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all duration-500"
                                                 :class="getProgressBarColor(kpi.status, kpi.progress_percentage)"
                                                 :style="{ width: Math.min(kpi.progress_percentage, 100) + '%' }"></div>
                                        </div>
                                    </div>

                                    <!-- Gap -->
                                    <div class="pt-2 border-t border-gray-100">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm text-gray-600">Gap to Target</span>
                                            <span class="text-sm font-semibold"
                                                  :class="kpi.gap < 0 ? 'text-green-600' : 'text-orange-600'"
                                                  x-text="(kpi.gap < 0 ? '+' : '') + formatValue(Math.abs(kpi.gap), kpi)"></span>
                                        </div>
                                    </div>

                                    <!-- Period -->
                                    <div class="text-xs text-gray-500 text-center">
                                        <span x-text="'Period: ' + kpi.period"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!loading && kpis.length === 0"
                         class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No KPIs defined</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first KPI.</p>
                    </div>
                </div>
            `;
        }
    };
}
