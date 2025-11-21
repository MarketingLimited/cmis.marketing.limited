/**
 * Real-Time Analytics Dashboard Component (Phase 8)
 *
 * Alpine.js component for real-time campaign performance monitoring
 */

export default function realtimeDashboard() {
    return {
        // State
        orgId: null,
        loading: false,
        error: null,
        timeWindow: '5m',
        autoRefresh: true,
        refreshInterval: 30000, // 30 seconds
        refreshTimer: null,

        // Data
        dashboardData: null,
        campaigns: [],
        totals: {},
        derivedMetrics: {},

        // Time series data
        timeSeriesData: {},
        selectedMetric: 'impressions',

        // Chart instances
        charts: {},

        /**
         * Initialize component
         */
        init() {
            this.orgId = this.$el.dataset.orgId;

            if (!this.orgId) {
                this.error = 'Organization ID is required';
                return;
            }

            // Initial load
            this.loadDashboard();

            // Start auto-refresh
            if (this.autoRefresh) {
                this.startAutoRefresh();
            }

            // Cleanup on destroy
            this.$watch('autoRefresh', (value) => {
                if (value) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            });
        },

        /**
         * Load dashboard data
         */
        async loadDashboard() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/realtime/dashboard?window=${this.timeWindow}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.dashboardData = data;
                    this.campaigns = data.campaigns || [];
                    this.totals = data.totals || {};
                    this.derivedMetrics = data.derived_metrics || {};

                    // Update visualizations
                    this.updateCharts();
                } else {
                    this.error = data.error || 'Failed to load dashboard data';
                }

            } catch (err) {
                console.error('Dashboard load error:', err);
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Load time series for selected metric
         */
        async loadTimeSeries(campaignId) {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/realtime/campaign/${campaignId}/timeseries?metric=${this.selectedMetric}&window=${this.timeWindow}&points=12`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to load time series');
                }

                const data = await response.json();

                if (data.success) {
                    this.timeSeriesData[campaignId] = data.series;
                    this.renderTimeSeriesChart(campaignId);
                }

            } catch (err) {
                console.error('Time series load error:', err);
            }
        },

        /**
         * Check for anomalies
         */
        async checkAnomalies(campaignId, metric) {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/realtime/campaign/${campaignId}/anomalies/${metric}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to check anomalies');
                }

                const data = await response.json();

                if (data.success && data.anomalies_detected) {
                    this.showAnomalyAlert(campaignId, metric, data);
                }

            } catch (err) {
                console.error('Anomaly check error:', err);
            }
        },

        /**
         * Update all charts
         */
        updateCharts() {
            // Update performance overview chart
            this.renderPerformanceChart();

            // Update trend charts for each campaign
            this.campaigns.slice(0, 3).forEach(campaign => {
                this.loadTimeSeries(campaign.campaign_id);
            });
        },

        /**
         * Render performance overview chart
         */
        renderPerformanceChart() {
            const ctx = document.getElementById('performanceChart');
            if (!ctx) return;

            // Destroy existing chart
            if (this.charts.performance) {
                this.charts.performance.destroy();
            }

            const campaignNames = this.campaigns.map(c => this.truncate(c.campaign_name, 20));
            const impressions = this.campaigns.map(c => c.metrics.impressions?.value || 0);
            const clicks = this.campaigns.map(c => c.metrics.clicks?.value || 0);
            const conversions = this.campaigns.map(c => c.metrics.conversions?.value || 0);

            this.charts.performance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: campaignNames,
                    datasets: [
                        {
                            label: 'Impressions',
                            data: impressions,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
                            borderWidth: 1
                        },
                        {
                            label: 'Clicks',
                            data: clicks,
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        },
                        {
                            label: 'Conversions',
                            data: conversions,
                            backgroundColor: 'rgba(168, 85, 247, 0.5)',
                            borderColor: 'rgb(168, 85, 247)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        /**
         * Render time series chart
         */
        renderTimeSeriesChart(campaignId) {
            const ctx = document.getElementById(`timeSeriesChart-${campaignId}`);
            if (!ctx) return;

            const chartKey = `timeSeries-${campaignId}`;

            // Destroy existing chart
            if (this.charts[chartKey]) {
                this.charts[chartKey].destroy();
            }

            const series = this.timeSeriesData[campaignId] || [];
            const labels = series.map(point => new Date(point.timestamp).toLocaleTimeString());
            const values = series.map(point => point.value);

            this.charts[chartKey] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: this.selectedMetric,
                        data: values,
                        fill: true,
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        borderColor: 'rgb(59, 130, 246)',
                        borderWidth: 2,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        },

        /**
         * Start auto-refresh
         */
        startAutoRefresh() {
            if (this.refreshTimer) {
                clearInterval(this.refreshTimer);
            }

            this.refreshTimer = setInterval(() => {
                this.loadDashboard();
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
         * Change time window
         */
        changeTimeWindow(window) {
            this.timeWindow = window;
            this.loadDashboard();
        },

        /**
         * Show anomaly alert
         */
        showAnomalyAlert(campaignId, metric, data) {
            const campaign = this.campaigns.find(c => c.campaign_id === campaignId);
            const campaignName = campaign?.campaign_name || campaignId;

            const message = `Anomaly detected in ${campaignName}: ${metric} ${data.anomaly_type} (${data.statistics.deviation}% deviation from normal)`;

            // Dispatch custom event for notification system
            this.$dispatch('anomaly-detected', {
                campaignId,
                metric,
                message,
                data
            });
        },

        /**
         * Format number with abbreviation
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
         * Format currency
         */
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },

        /**
         * Format percentage
         */
        formatPercentage(value) {
            return value.toFixed(2) + '%';
        },

        /**
         * Truncate text
         */
        truncate(text, length) {
            if (text.length <= length) return text;
            return text.substring(0, length) + '...';
        },

        /**
         * Get auth token from localStorage
         */
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        /**
         * Get status badge color
         */
        getStatusColor(status) {
            const colors = {
                'active': 'bg-green-100 text-green-800',
                'paused': 'bg-yellow-100 text-yellow-800',
                'completed': 'bg-gray-100 text-gray-800',
                'draft': 'bg-blue-100 text-blue-800'
            };
            return colors[status] || 'bg-gray-100 text-gray-800';
        },

        /**
         * Render component HTML
         */
        render() {
            return `
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900">Real-Time Dashboard</h2>
                            <p class="text-sm text-gray-600 mt-1">
                                Auto-refreshing every ${this.refreshInterval / 1000}s
                            </p>
                        </div>

                        <div class="flex items-center space-x-4">
                            <!-- Time Window Selector -->
                            <select x-model="timeWindow"
                                    @change="loadDashboard()"
                                    class="px-3 py-2 border border-gray-300 rounded-md">
                                <option value="1m">Last 1 min</option>
                                <option value="5m">Last 5 min</option>
                                <option value="15m">Last 15 min</option>
                                <option value="1h">Last 1 hour</option>
                            </select>

                            <!-- Auto Refresh Toggle -->
                            <label class="flex items-center space-x-2">
                                <input type="checkbox"
                                       x-model="autoRefresh"
                                       class="rounded border-gray-300">
                                <span class="text-sm text-gray-700">Auto-refresh</span>
                            </label>

                            <!-- Refresh Button -->
                            <button @click="loadDashboard()"
                                    :disabled="loading"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50">
                                <span x-show="!loading">Refresh</span>
                                <span x-show="loading">Loading...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div x-show="error"
                         class="p-4 bg-red-50 border border-red-200 rounded-md">
                        <p class="text-red-800" x-text="error"></p>
                    </div>

                    <!-- Loading State -->
                    <div x-show="loading && !dashboardData"
                         class="flex justify-center items-center py-12">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Dashboard Content -->
                    <div x-show="dashboardData && !loading" class="space-y-6">
                        <!-- Totals Cards -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">Impressions</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2"
                                     x-text="formatNumber(totals.impressions || 0)"></div>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">Clicks</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2"
                                     x-text="formatNumber(totals.clicks || 0)"></div>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">Conversions</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2"
                                     x-text="formatNumber(totals.conversions || 0)"></div>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">Spend</div>
                                <div class="text-3xl font-bold text-gray-900 mt-2"
                                     x-text="formatCurrency(totals.spend || 0)"></div>
                            </div>
                        </div>

                        <!-- Derived Metrics -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">CTR</div>
                                <div class="text-2xl font-semibold text-blue-600 mt-2"
                                     x-text="formatPercentage(derivedMetrics.ctr || 0)"></div>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">CPC</div>
                                <div class="text-2xl font-semibold text-green-600 mt-2"
                                     x-text="formatCurrency(derivedMetrics.cpc || 0)"></div>
                            </div>

                            <div class="bg-white p-6 rounded-lg shadow">
                                <div class="text-sm text-gray-600">Conversion Rate</div>
                                <div class="text-2xl font-semibold text-purple-600 mt-2"
                                     x-text="formatPercentage(derivedMetrics.conversion_rate || 0)"></div>
                            </div>
                        </div>

                        <!-- Performance Chart -->
                        <div class="bg-white p-6 rounded-lg shadow">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Campaign Performance</h3>
                            <div class="h-64">
                                <canvas id="performanceChart"></canvas>
                            </div>
                        </div>

                        <!-- Campaign List -->
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-semibold text-gray-900">Active Campaigns</h3>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Impressions</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Clicks</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">CTR</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Conversions</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Spend</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="campaign in campaigns" :key="campaign.campaign_id">
                                            <tr>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium text-gray-900"
                                                         x-text="campaign.campaign_name"></div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900"
                                                    x-text="formatNumber(campaign.metrics.impressions?.value || 0)"></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900"
                                                    x-text="formatNumber(campaign.metrics.clicks?.value || 0)"></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900"
                                                    x-text="formatPercentage(campaign.metrics.ctr?.value || 0)"></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900"
                                                    x-text="formatNumber(campaign.metrics.conversions?.value || 0)"></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900"
                                                    x-text="formatCurrency(campaign.metrics.spend?.value || 0)"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    };
}
