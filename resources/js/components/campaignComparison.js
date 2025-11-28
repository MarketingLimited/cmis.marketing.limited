/**
 * Campaign Comparison Component (Phase 11)
 *
 * Allows side-by-side comparison of multiple campaigns
 */

export default function campaignComparison() {
    return {
        // State
        orgId: null,
        selectedCampaigns: [],
        comparisonData: null,
        loading: false,
        error: null,

        // Chart
        comparisonChart: null,

        /**
         * Initialize component
         */
        init() {
            this.orgId = this.$el.dataset.orgId;
        },

        /**
         * Add campaign to comparison
         */
        addCampaign(campaignId) {
            if (!this.selectedCampaigns.includes(campaignId)) {
                this.selectedCampaigns.push(campaignId);

                if (this.selectedCampaigns.length >= 2) {
                    this.loadComparison();
                }
            }
        },

        /**
         * Remove campaign from comparison
         */
        removeCampaign(campaignId) {
            const index = this.selectedCampaigns.indexOf(campaignId);
            if (index > -1) {
                this.selectedCampaigns.splice(index, 1);
            }

            if (this.selectedCampaigns.length >= 2) {
                this.loadComparison();
            } else {
                this.comparisonData = null;
            }
        },

        /**
         * Load comparison data
         */
        async loadComparison() {
            this.loading = true;
            this.error = null;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/compare`,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': `Bearer ${this.getAuthToken()}`
                        },
                        body: JSON.stringify({
                            campaign_ids: this.selectedCampaigns,
                            format: 'json'
                        })
                    }
                );

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.comparisonData = data.data || data;
                    this.renderComparisonChart();
                } else {
                    this.error = data.error || 'Failed to load comparison';
                }
            } catch (err) {
                console.error(__('javascript.comparison_error'), err);
                this.error = err.message;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Render comparison chart
         */
        renderComparisonChart() {
            const ctx = document.getElementById('comparisonChart');
            if (!ctx || !this.comparisonData) return;

            if (this.comparisonChart) {
                this.comparisonChart.destroy();
            }

            const labels = this.comparisonData.comparison.map(c => c.campaign.name);
            const rois = this.comparisonData.comparison.map(c => c.metrics.roi);
            const ctrs = this.comparisonData.comparison.map(c => c.metrics.ctr);

            this.comparisonChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'ROI (%)',
                            data: rois,
                            backgroundColor: 'rgba(34, 197, 94, 0.5)',
                            borderColor: 'rgb(34, 197, 94)',
                            borderWidth: 1
                        },
                        {
                            label: 'CTR (%)',
                            data: ctrs,
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgb(59, 130, 246)',
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
         * Get auth token
         */
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        }
    };
}
