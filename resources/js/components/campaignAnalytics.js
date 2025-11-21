/**
 * Campaign Analytics & Performance Visualization Component (Phase 8)
 *
 * Comprehensive analytics dashboard for campaign performance analysis
 */

export default function campaignAnalytics() {
    return {
        // State
        orgId: null,
        campaignId: null,
        loading: false,
        error: null,
        activeTab: 'overview',

        // Data
        roiData: null,
        attributionData: null,
        ltvData: null,
        projection: null,

        // Settings
        dateRange: {
            start: null,
            end: null
        },
        attributionModel: 'linear',

        // Charts
        charts: {},

        /**
         * Initialize component
         */
        init() {
            this.orgId = this.$el.dataset.orgId;
            this.campaignId = this.$el.dataset.campaignId;

            if (!this.orgId || !this.campaignId) {
                this.error = 'Organization ID and Campaign ID are required';
                return;
            }

            // Set default date range (last 30 days)
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 30);

            this.dateRange.start = start.toISOString().split('T')[0];
            this.dateRange.end = end.toISOString().split('T')[0];

            // Load all data
            this.loadAllData();
        },

        /**
         * Load all analytics data
         */
        async loadAllData() {
            await Promise.all([
                this.loadROI(),
                this.loadAttribution(),
                this.loadLTV(),
                this.loadProjection()
            ]);
        },

        /**
         * Load ROI data
         */
        async loadROI() {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/roi/campaigns/${this.campaignId}`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        },
                        body: JSON.stringify({
                            date_range: this.dateRange
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to load ROI data');
                }

                const data = await response.json();

                if (data.success) {
                    this.roiData = data;
                    this.renderROIChart();
                }

            } catch (err) {
                console.error('ROI load error:', err);
            }
        },

        /**
         * Load attribution data
         */
        async loadAttribution() {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/attribution/campaigns/${this.campaignId}/insights`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        },
                        body: JSON.stringify({
                            model: this.attributionModel,
                            date_range: this.dateRange
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to load attribution data');
                }

                const data = await response.json();

                if (data.success) {
                    this.attributionData = data;
                    this.renderAttributionChart();
                }

            } catch (err) {
                console.error('Attribution load error:', err);
            }
        },

        /**
         * Load lifetime value data
         */
        async loadLTV() {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/roi/campaigns/${this.campaignId}/ltv`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        }
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to load LTV data');
                }

                const data = await response.json();

                if (data.success) {
                    this.ltvData = data;
                }

            } catch (err) {
                console.error('LTV load error:', err);
            }
        },

        /**
         * Load ROI projection
         */
        async loadProjection() {
            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/roi/campaigns/${this.campaignId}/project`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        },
                        body: JSON.stringify({
                            days_to_project: 30
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error('Failed to load projection');
                }

                const data = await response.json();

                if (data.success) {
                    this.projection = data;
                    this.renderProjectionChart();
                }

            } catch (err) {
                console.error('Projection load error:', err);
            }
        },

        /**
         * Render ROI chart
         */
        renderROIChart() {
            const ctx = document.getElementById('roiChart');
            if (!ctx || !this.roiData) return;

            if (this.charts.roi) {
                this.charts.roi.destroy();
            }

            const metrics = this.roiData.financial_metrics;

            this.charts.roi = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Spend', 'Profit'],
                    datasets: [{
                        data: [metrics.total_spend, Math.max(0, metrics.profit)],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(34, 197, 94, 0.8)'
                        ],
                        borderColor: [
                            'rgb(239, 68, 68)',
                            'rgb(34, 197, 94)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        title: {
                            display: true,
                            text: `ROI: ${metrics.roi_percentage.toFixed(2)}%`
                        }
                    }
                }
            });
        },

        /**
         * Render attribution chart
         */
        renderAttributionChart() {
            const ctx = document.getElementById('attributionChart');
            if (!ctx || !this.attributionData) return;

            if (this.charts.attribution) {
                this.charts.attribution.destroy();
            }

            const insights = this.attributionData.insights || [];
            const labels = insights.map(i => i.channel);
            const contributions = insights.map(i => i.contribution_percentage);
            const colors = this.generateColors(insights.length);

            this.charts.attribution = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: contributions,
                        backgroundColor: colors.backgrounds,
                        borderColor: colors.borders,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right'
                        },
                        title: {
                            display: true,
                            text: `Attribution Model: ${this.attributionModel}`
                        },
                        tooltip: {
                            callbacks: {
                                label: (context) => {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    return `${label}: ${value.toFixed(2)}%`;
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Render projection chart
         */
        renderProjectionChart() {
            const ctx = document.getElementById('projectionChart');
            if (!ctx || !this.projection) return;

            if (this.charts.projection) {
                this.charts.projection.destroy();
            }

            const metrics = this.projection.projected_metrics;

            this.charts.projection = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Current Period', 'Projected (30 days)'],
                    datasets: [
                        {
                            label: 'Spend',
                            data: [
                                this.roiData?.financial_metrics.total_spend || 0,
                                metrics.projected_spend
                            ],
                            backgroundColor: 'rgba(239, 68, 68, 0.5)',
                            borderColor: 'rgb(239, 68, 68)',
                            borderWidth: 1
                        },
                        {
                            label: 'Revenue',
                            data: [
                                this.roiData?.financial_metrics.total_revenue || 0,
                                metrics.projected_revenue
                            ],
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgb(34, 197, 94)',
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
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `Confidence: ${this.projection.confidence_level.level} (${this.projection.confidence_level.percentage}%)`
                        }
                    }
                }
            });
        },

        /**
         * Change attribution model
         */
        async changeAttributionModel(model) {
            this.attributionModel = model;
            await this.loadAttribution();
        },

        /**
         * Update date range
         */
        async updateDateRange() {
            await this.loadAllData();
        },

        /**
         * Generate colors for charts
         */
        generateColors(count) {
            const baseColors = [
                { bg: 'rgba(59, 130, 246, 0.5)', border: 'rgb(59, 130, 246)' },
                { bg: 'rgba(34, 197, 94, 0.5)', border: 'rgb(34, 197, 94)' },
                { bg: 'rgba(168, 85, 247, 0.5)', border: 'rgb(168, 85, 247)' },
                { bg: 'rgba(249, 115, 22, 0.5)', border: 'rgb(249, 115, 22)' },
                { bg: 'rgba(236, 72, 153, 0.5)', border: 'rgb(236, 72, 153)' }
            ];

            const backgrounds = [];
            const borders = [];

            for (let i = 0; i < count; i++) {
                const color = baseColors[i % baseColors.length];
                backgrounds.push(color.bg);
                borders.push(color.border);
            }

            return { backgrounds, borders };
        },

        /**
         * Format currency
         */
        formatCurrency(amount) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD'
            }).format(amount);
        },

        /**
         * Format percentage
         */
        formatPercentage(value) {
            return value.toFixed(2) + '%';
        },

        /**
         * Get profitability status color
         */
        getProfitabilityColor(status) {
            const colors = {
                'highly_profitable': 'text-green-600',
                'profitable': 'text-green-500',
                'break_even': 'text-yellow-500',
                'unprofitable': 'text-orange-500',
                'highly_unprofitable': 'text-red-600'
            };
            return colors[status] || 'text-gray-600';
        },

        /**
         * Get auth token
         */
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        }
    };
}
