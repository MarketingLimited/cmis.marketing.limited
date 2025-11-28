/**
 * Campaign Performance Dashboard Component (Phase 2 - Option 3)
 *
 * Alpine.js + Chart.js component for campaign performance analytics
 *
 * Usage:
 * <div x-data="campaignDashboard()" x-init="init()">
 *     <div x-html="renderDashboard()"></div>
 * </div>
 */

export default function campaignDashboard(campaignId = null) {
    return {
        // State
        campaignId: campaignId,
        metrics: null,
        trends: null,
        topPerforming: [],
        isLoading: false,
        error: null,

        // Date range
        startDate: null,
        endDate: null,
        interval: 'day',

        // Charts
        charts: {},

        /**
         * Initialize the component
         */
        async init() {
            // Set default date range (last 30 days)
            this.endDate = new Date().toISOString().split('T')[0];
            this.startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

            // Load initial data
            if (this.campaignId) {
                await this.loadCampaignMetrics();
                await this.loadPerformanceTrends();
            } else {
                await this.loadTopPerforming();
            }
        },

        /**
         * Load campaign performance metrics
         */
        async loadCampaignMetrics() {
            this.isLoading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    start_date: this.startDate,
                    end_date: this.endDate
                });

                const response = await fetch(`/api/campaigns/${this.campaignId}/performance-metrics?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load campaign metrics');
                }

                const data = await response.json();

                if (data.success) {
                    this.metrics = data.data;
                } else {
                    throw new Error(data.message || 'Failed to load metrics');
                }
            } catch (error) {
                console.error(__('javascript.metrics_load_error'), error);
                this.error = error.message;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Load performance trends over time
         */
        async loadPerformanceTrends() {
            try {
                const params = new URLSearchParams({
                    interval: this.interval,
                    periods: 30
                });

                const response = await fetch(`/api/campaigns/${this.campaignId}/performance-trends?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load trends');
                }

                const data = await response.json();

                if (data.success) {
                    this.trends = data.data;
                    this.$nextTick(() => this.renderTrendCharts());
                }
            } catch (error) {
                console.error(__('javascript.trends_load_error'), error);
            }
        },

        /**
         * Load top performing campaigns
         */
        async loadTopPerforming(metric = 'impressions', limit = 10) {
            this.isLoading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    metric,
                    limit,
                    start_date: this.startDate,
                    end_date: this.endDate
                });

                const response = await fetch(`/api/campaigns/top-performing?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load top performing campaigns');
                }

                const data = await response.json();

                if (data.success) {
                    this.topPerforming = data.data.campaigns || [];
                } else {
                    throw new Error(data.message || 'Failed to load campaigns');
                }
            } catch (error) {
                console.error(__('javascript.top_performing_load_error'), error);
                this.error = error.message;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Render trend charts using Chart.js
         */
        renderTrendCharts() {
            if (!this.trends || !this.trends.trends) return;

            // Destroy existing charts
            Object.values(this.charts).forEach(chart => chart.destroy());
            this.charts = {};

            // Render impressions trend
            this.renderTrendChart('impressions', 'Impressions', 'rgba(59, 130, 246, 0.5)');

            // Render clicks trend
            this.renderTrendChart('clicks', 'Clicks', 'rgba(16, 185, 129, 0.5)');

            // Render conversions trend
            this.renderTrendChart('conversions', 'Conversions', 'rgba(245, 158, 11, 0.5)');
        },

        /**
         * Render a specific trend chart
         */
        renderTrendChart(metric, label, color) {
            const canvas = document.getElementById(`chart-${metric}`);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const trendData = this.trends.trends[metric] || [];

            this.charts[metric] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: trendData.map(d => d.date || d.period),
                    datasets: [{
                        label: label,
                        data: trendData.map(d => d.value),
                        borderColor: color.replace('0.5', '1'),
                        backgroundColor: color,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Update date range and reload data
         */
        async updateDateRange() {
            if (this.campaignId) {
                await this.loadCampaignMetrics();
                await this.loadPerformanceTrends();
            } else {
                await this.loadTopPerforming();
            }
        },

        /**
         * Format number with commas
         */
        formatNumber(num) {
            if (!num) return '0';
            return num.toLocaleString();
        },

        /**
         * Format currency
         */
        formatCurrency(amount, currency = 'BHD') {
            if (!amount) return `${currency} 0.00`;
            return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        },

        /**
         * Format percentage
         */
        formatPercentage(value) {
            if (!value) return '0.0%';
            return `${value.toFixed(1)}%`;
        },

        /**
         * Get authentication token
         */
        getAuthToken() {
            let token = localStorage.getItem('auth_token');
            if (!token) {
                const cookies = document.cookie.split(';');
                const authCookie = cookies.find(c => c.trim().startsWith('auth_token='));
                if (authCookie) {
                    token = authCookie.split('=')[1];
                }
            }
            return token;
        },

        /**
         * Render the dashboard component
         */
        renderDashboard() {
            if (this.isLoading && !this.metrics && this.topPerforming.length === 0) {
                return `
                    <div class="flex items-center justify-center p-8">
                        <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                `;
            }

            if (this.error) {
                return `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
                        <p class="font-medium">Error loading dashboard</p>
                        <p class="text-sm mt-1">${this.error}</p>
                    </div>
                `;
            }

            // Render campaign-specific dashboard
            if (this.campaignId && this.metrics) {
                return this.renderCampaignDashboard();
            }

            // Render overview dashboard
            return this.renderOverviewDashboard();
        },

        /**
         * Render campaign-specific dashboard
         */
        renderCampaignDashboard() {
            const summary = this.metrics.summary || {};

            return `
                <div class="space-y-6">
                    <!-- Date Range Selector -->
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" x-model="startDate" @change="updateDateRange()"
                                class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" x-model="endDate" @change="updateDateRange()"
                                class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Metrics Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Impressions</h3>
                            <p class="text-3xl font-bold text-gray-900">${this.formatNumber(summary.impressions)}</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Clicks</h3>
                            <p class="text-3xl font-bold text-blue-600">${this.formatNumber(summary.clicks)}</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Conversions</h3>
                            <p class="text-3xl font-bold text-green-600">${this.formatNumber(summary.conversions)}</p>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Spend</h3>
                            <p class="text-3xl font-bold text-orange-600">${this.formatCurrency(summary.spend)}</p>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Impressions Trend</h3>
                            <canvas id="chart-impressions" height="200"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Clicks Trend</h3>
                            <canvas id="chart-clicks" height="200"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Conversions Trend</h3>
                            <canvas id="chart-conversions" height="200"></canvas>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Render overview dashboard with top performers
         */
        renderOverviewDashboard() {
            if (this.topPerforming.length === 0) {
                return `
                    <div class="bg-gray-50 rounded-lg p-8 text-center">
                        <p class="text-gray-600">No campaigns found</p>
                    </div>
                `;
            }

            return `
                <div class="space-y-6">
                    <!-- Date Range Selector -->
                    <div class="flex items-center space-x-4 rtl:space-x-reverse">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" x-model="startDate" @change="updateDateRange()"
                                class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" x-model="endDate" @change="updateDateRange()"
                                class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>

                    <!-- Top Performing Campaigns -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Top Performing Campaigns</h2>
                        </div>
                        <div class="divide-y divide-gray-200">
                            ${this.topPerforming.map(campaign => `
                                <div class="px-6 py-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <h3 class="text-sm font-medium text-gray-900">${campaign.name || 'Unnamed Campaign'}</h3>
                                            <p class="text-xs text-gray-500 mt-1">${campaign.status || 'Unknown'}</p>
                                        </div>
                                        <div class="flex items-center space-x-4 rtl:space-x-reverse">
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-900">${this.formatNumber(campaign.impressions || 0)}</p>
                                                <p class="text-xs text-gray-500">Impressions</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-blue-600">${this.formatNumber(campaign.clicks || 0)}</p>
                                                <p class="text-xs text-gray-500">Clicks</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-green-600">${this.formatPercentage(campaign.conversion_rate || 0)}</p>
                                                <p class="text-xs text-gray-500">CVR</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }
    };
}

// Export for use in HTML
window.campaignDashboard = campaignDashboard;
