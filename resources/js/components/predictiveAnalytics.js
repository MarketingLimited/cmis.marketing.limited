/**
 * CMIS Predictive Analytics Component (Phase 16)
 *
 * Alpine.js component for forecasting, anomaly detection,
 * trend analysis, and AI recommendations
 */

export default function predictiveAnalytics() {
    return {
        // State
        orgId: null,
        activeTab: 'dashboard',
        loading: false,
        error: null,

        // Data
        stats: {
            forecasts: { total: 0, with_actuals: 0, accuracy_rate: 0 },
            anomalies: { total: 0, new: 0, acknowledged: 0, resolved: 0 },
            recommendations: { total: 0, pending: 0, accepted: 0, implemented: 0 },
            trends: { total: 0, upward: 0, downward: 0, stable: 0 },
            recent: { anomalies: [], recommendations: [], trends: [] }
        },

        forecasts: [],
        forecastsPagination: { current_page: 1, last_page: 1 },
        selectedForecast: null,

        anomalies: [],
        anomaliesPagination: { current_page: 1, last_page: 1 },
        selectedAnomaly: null,

        recommendations: [],
        recommendationsPagination: { current_page: 1, last_page: 1 },
        selectedRecommendation: null,

        trends: [],
        trendsPagination: { current_page: 1, last_page: 1 },
        selectedTrend: null,

        // Forms
        forecastForm: {
            entity_type: 'campaign',
            entity_id: '',
            metric: 'revenue',
            days: 30,
            forecast_type: 'moving_average'
        },

        anomalyForm: {
            entity_type: 'campaign',
            entity_id: '',
            metric: 'revenue',
            days: 30
        },

        trendForm: {
            entity_type: 'campaign',
            entity_id: '',
            metric: 'revenue',
            days: 30
        },

        recommendationForm: {
            entity_type: 'campaign',
            entity_id: ''
        },

        // Filters
        forecastFilters: {
            entity_type: '',
            metric: '',
            forecast_type: ''
        },

        anomalyFilters: {
            status: '',
            severity: '',
            anomaly_type: ''
        },

        recommendationFilters: {
            status: '',
            priority: '',
            category: ''
        },

        trendFilters: {
            trend_type: ''
        },

        // Charts
        forecastChart: null,
        trendChart: null,

        // Initialize
        init() {
            this.orgId = this.$el.dataset.orgId || window.currentOrgId;

            if (this.orgId) {
                this.loadStats();
            }
        },

        // Get auth token
        getAuthToken() {
            return localStorage.getItem('auth_token') || document.querySelector('meta[name="api-token"]')?.content;
        },

        // API call helper
        async apiCall(url, options = {}) {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        ...options.headers
                    }
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || `HTTP ${response.status}`);
                }

                return data;
            } catch (error) {
                this.error = error.message;
                throw error;
            } finally {
                this.loading = false;
            }
        },

        // Load statistics
        async loadStats() {
            try {
                const data = await this.apiCall(`/api/orgs/${this.orgId}/analytics/stats`);
                this.stats = data.stats;
            } catch (error) {
                console.error('Failed to load stats:', error);
            }
        },

        // ===== FORECASTS =====

        async generateForecast() {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/forecasts`,
                    {
                        method: 'POST',
                        body: JSON.stringify(this.forecastForm)
                    }
                );

                alert(`${data.count} forecasts generated successfully`);
                this.loadForecasts();
                this.loadStats();
                this.resetForecastForm();
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async loadForecasts(page = 1) {
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: 30,
                    ...this.forecastFilters
                });

                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/forecasts?${params}`
                );

                this.forecasts = data.forecasts.data;
                this.forecastsPagination = {
                    current_page: data.forecasts.current_page,
                    last_page: data.forecasts.last_page
                };
            } catch (error) {
                console.error('Failed to load forecasts:', error);
            }
        },

        async viewForecast(forecastId) {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/forecasts/${forecastId}`
                );

                this.selectedForecast = data.forecast;
                this.activeTab = 'forecast-details';

                this.$nextTick(() => {
                    this.renderForecastChart();
                });
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async updateForecastActual(forecastId, actualValue) {
            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/forecasts/${forecastId}`,
                    {
                        method: 'PUT',
                        body: JSON.stringify({ actual_value: actualValue })
                    }
                );

                this.loadForecasts();
                this.loadStats();
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        renderForecastChart() {
            const canvas = document.getElementById('forecastChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');

            if (this.forecastChart) {
                this.forecastChart.destroy();
            }

            this.forecastChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [this.selectedForecast.forecast_date],
                    datasets: [
                        {
                            label: 'Predicted Value',
                            data: [this.selectedForecast.predicted_value],
                            borderColor: 'rgb(59, 130, 246)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: false
                        },
                        {
                            label: 'Confidence Range',
                            data: [this.selectedForecast.confidence_upper],
                            borderColor: 'rgba(59, 130, 246, 0.3)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            fill: '+1'
                        },
                        {
                            label: 'Lower Bound',
                            data: [this.selectedForecast.confidence_lower],
                            borderColor: 'rgba(59, 130, 246, 0.3)',
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        resetForecastForm() {
            this.forecastForm = {
                entity_type: 'campaign',
                entity_id: '',
                metric: 'revenue',
                days: 30,
                forecast_type: 'moving_average'
            };
        },

        // ===== ANOMALIES =====

        async detectAnomalies() {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/anomalies/detect`,
                    {
                        method: 'POST',
                        body: JSON.stringify(this.anomalyForm)
                    }
                );

                alert(data.message);
                this.loadAnomalies();
                this.loadStats();
                this.resetAnomalyForm();
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async loadAnomalies(page = 1) {
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: 15,
                    ...this.anomalyFilters
                });

                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/anomalies?${params}`
                );

                this.anomalies = data.anomalies.data;
                this.anomaliesPagination = {
                    current_page: data.anomalies.current_page,
                    last_page: data.anomalies.last_page
                };
            } catch (error) {
                console.error('Failed to load anomalies:', error);
            }
        },

        async viewAnomaly(anomalyId) {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/anomalies/${anomalyId}`
                );

                this.selectedAnomaly = data.anomaly;
                this.activeTab = 'anomaly-details';
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async acknowledgeAnomaly(anomalyId) {
            const notes = prompt('Enter acknowledgement notes (optional):');

            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/anomalies/${anomalyId}/acknowledge`,
                    {
                        method: 'POST',
                        body: JSON.stringify({ notes })
                    }
                );

                this.loadAnomalies();
                this.loadStats();

                if (this.selectedAnomaly?.anomaly_id === anomalyId) {
                    this.viewAnomaly(anomalyId);
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async resolveAnomaly(anomalyId) {
            const resolutionNotes = prompt('Enter resolution notes:');
            if (!resolutionNotes) return;

            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/anomalies/${anomalyId}/resolve`,
                    {
                        method: 'POST',
                        body: JSON.stringify({ resolution_notes: resolutionNotes })
                    }
                );

                this.loadAnomalies();
                this.loadStats();

                if (this.selectedAnomaly?.anomaly_id === anomalyId) {
                    this.viewAnomaly(anomalyId);
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async markFalsePositive(anomalyId) {
            if (!confirm('Mark this anomaly as a false positive?')) return;

            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/anomalies/${anomalyId}/false-positive`,
                    { method: 'POST' }
                );

                this.loadAnomalies();
                this.loadStats();

                if (this.selectedAnomaly?.anomaly_id === anomalyId) {
                    this.viewAnomaly(anomalyId);
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        getSeverityClass(severity) {
            const classes = {
                low: 'text-yellow-600 bg-yellow-50',
                medium: 'text-orange-600 bg-orange-50',
                high: 'text-red-600 bg-red-50',
                critical: 'text-red-700 bg-red-100'
            };
            return classes[severity] || 'text-gray-600 bg-gray-50';
        },

        resetAnomalyForm() {
            this.anomalyForm = {
                entity_type: 'campaign',
                entity_id: '',
                metric: 'revenue',
                days: 30
            };
        },

        // ===== TRENDS =====

        async analyzeTrend() {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/trends`,
                    {
                        method: 'POST',
                        body: JSON.stringify(this.trendForm)
                    }
                );

                alert('Trend analysis completed');
                this.loadTrends();
                this.loadStats();
                this.viewTrendDetails(data.trend);
                this.resetTrendForm();
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async loadTrends(page = 1) {
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: 15,
                    ...this.trendFilters
                });

                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/trends?${params}`
                );

                this.trends = data.trends.data;
                this.trendsPagination = {
                    current_page: data.trends.current_page,
                    last_page: data.trends.last_page
                };
            } catch (error) {
                console.error('Failed to load trends:', error);
            }
        },

        viewTrendDetails(trend) {
            this.selectedTrend = trend;
            this.activeTab = 'trend-details';

            this.$nextTick(() => {
                this.renderTrendChart();
            });
        },

        renderTrendChart() {
            const canvas = document.getElementById('trendChart');
            if (!canvas || !this.selectedTrend) return;

            const ctx = canvas.getContext('2d');

            if (this.trendChart) {
                this.trendChart.destroy();
            }

            // Generate sample data points based on trend
            const days = this.selectedTrend.data_points || 30;
            const labels = Array.from({ length: days }, (_, i) => `Day ${i + 1}`);
            const slope = this.selectedTrend.slope || 0;
            const baseValue = 100;
            const data = labels.map((_, i) => baseValue + (slope * i));

            this.trendChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: this.selectedTrend.metric,
                        data,
                        borderColor: this.getTrendColor(this.selectedTrend.trend_type),
                        backgroundColor: this.getTrendColor(this.selectedTrend.trend_type, 0.1),
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true },
                        tooltip: { mode: 'index', intersect: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        },

        getTrendColor(trendType, alpha = 1) {
            const colors = {
                upward: `rgba(34, 197, 94, ${alpha})`,
                downward: `rgba(239, 68, 68, ${alpha})`,
                stable: `rgba(59, 130, 246, ${alpha})`,
                seasonal: `rgba(168, 85, 247, ${alpha})`,
                volatile: `rgba(234, 179, 8, ${alpha})`
            };
            return colors[trendType] || `rgba(107, 114, 128, ${alpha})`;
        },

        getTrendTypeClass(trendType) {
            const classes = {
                upward: 'text-green-600 bg-green-50',
                downward: 'text-red-600 bg-red-50',
                stable: 'text-blue-600 bg-blue-50',
                seasonal: 'text-purple-600 bg-purple-50',
                volatile: 'text-yellow-600 bg-yellow-50'
            };
            return classes[trendType] || 'text-gray-600 bg-gray-50';
        },

        resetTrendForm() {
            this.trendForm = {
                entity_type: 'campaign',
                entity_id: '',
                metric: 'revenue',
                days: 30
            };
        },

        // ===== RECOMMENDATIONS =====

        async generateRecommendations() {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/recommendations/generate`,
                    {
                        method: 'POST',
                        body: JSON.stringify(this.recommendationForm)
                    }
                );

                alert(data.message);
                this.loadRecommendations();
                this.loadStats();
                this.resetRecommendationForm();
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async loadRecommendations(page = 1) {
            try {
                const params = new URLSearchParams({
                    page,
                    per_page: 15,
                    ...this.recommendationFilters
                });

                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/recommendations?${params}`
                );

                this.recommendations = data.recommendations.data;
                this.recommendationsPagination = {
                    current_page: data.recommendations.current_page,
                    last_page: data.recommendations.last_page
                };
            } catch (error) {
                console.error('Failed to load recommendations:', error);
            }
        },

        async viewRecommendation(recommendationId) {
            try {
                const data = await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/recommendations/${recommendationId}`
                );

                this.selectedRecommendation = data.recommendation;
                this.activeTab = 'recommendation-details';
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async acceptRecommendation(recommendationId) {
            if (!confirm('Accept this recommendation?')) return;

            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/recommendations/${recommendationId}/accept`,
                    { method: 'POST' }
                );

                this.loadRecommendations();
                this.loadStats();

                if (this.selectedRecommendation?.recommendation_id === recommendationId) {
                    this.viewRecommendation(recommendationId);
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async rejectRecommendation(recommendationId) {
            const rejectionReason = prompt('Enter rejection reason (optional):');

            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/recommendations/${recommendationId}/reject`,
                    {
                        method: 'POST',
                        body: JSON.stringify({ rejection_reason: rejectionReason })
                    }
                );

                this.loadRecommendations();
                this.loadStats();

                if (this.selectedRecommendation?.recommendation_id === recommendationId) {
                    this.viewRecommendation(recommendationId);
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        async implementRecommendation(recommendationId) {
            const implementationNotes = prompt('Enter implementation notes (optional):');

            try {
                await this.apiCall(
                    `/api/orgs/${this.orgId}/analytics/recommendations/${recommendationId}/implement`,
                    {
                        method: 'POST',
                        body: JSON.stringify({ implementation_notes: implementationNotes })
                    }
                );

                this.loadRecommendations();
                this.loadStats();

                if (this.selectedRecommendation?.recommendation_id === recommendationId) {
                    this.viewRecommendation(recommendationId);
                }
            } catch (error) {
                alert(`Error: ${error.message}`);
            }
        },

        getPriorityClass(priority) {
            const classes = {
                low: 'text-gray-600 bg-gray-50',
                medium: 'text-blue-600 bg-blue-50',
                high: 'text-orange-600 bg-orange-50',
                critical: 'text-red-600 bg-red-50'
            };
            return classes[priority] || 'text-gray-600 bg-gray-50';
        },

        getStatusClass(status) {
            const classes = {
                pending: 'text-yellow-600 bg-yellow-50',
                accepted: 'text-blue-600 bg-blue-50',
                rejected: 'text-red-600 bg-red-50',
                implemented: 'text-green-600 bg-green-50',
                expired: 'text-gray-600 bg-gray-50'
            };
            return classes[status] || 'text-gray-600 bg-gray-50';
        },

        resetRecommendationForm() {
            this.recommendationForm = {
                entity_type: 'campaign',
                entity_id: ''
            };
        },

        // ===== TAB NAVIGATION =====

        switchTab(tab) {
            this.activeTab = tab;

            // Load data when switching tabs
            switch (tab) {
                case 'forecasts':
                    if (this.forecasts.length === 0) this.loadForecasts();
                    break;
                case 'anomalies':
                    if (this.anomalies.length === 0) this.loadAnomalies();
                    break;
                case 'trends':
                    if (this.trends.length === 0) this.loadTrends();
                    break;
                case 'recommendations':
                    if (this.recommendations.length === 0) this.loadRecommendations();
                    break;
            }
        },

        // ===== UTILITY METHODS =====

        formatDate(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleDateString();
        },

        formatDateTime(dateString) {
            if (!dateString) return 'N/A';
            return new Date(dateString).toLocaleString();
        },

        formatNumber(value, decimals = 2) {
            if (value === null || value === undefined) return 'N/A';
            return Number(value).toLocaleString(undefined, {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            });
        },

        formatPercentage(value, decimals = 1) {
            if (value === null || value === undefined) return 'N/A';
            return `${Number(value).toFixed(decimals)}%`;
        }
    };
}
