<template>
  <div class="org-market-manager">
    <div class="manager-header">
      <h2 class="manager-title">Organization Markets</h2>
      <button @click="showAddMarket = true" class="btn-primary">
        Add Market
      </button>
    </div>

    <!-- Statistics -->
    <div v-if="stats" class="stats-grid">
      <div class="stat-card">
        <div class="stat-label">Active Markets</div>
        <div class="stat-value">{{ stats.active_count || 0 }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Investment</div>
        <div class="stat-value">{{ formatCurrency(stats.total_investment || 0) }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Total Campaigns</div>
        <div class="stat-value">{{ stats.total_campaigns || 0 }}</div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Avg Priority</div>
        <div class="stat-value">{{ stats.average_priority || 'N/A' }}</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filter-group">
        <label>Status:</label>
        <select v-model="filters.status" @change="loadMarkets">
          <option value="">All Status</option>
          <option value="active">Active</option>
          <option value="testing">Testing</option>
          <option value="paused">Paused</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Sort By:</label>
        <select v-model="filters.sort_by" @change="loadMarkets">
          <option value="priority">Priority</option>
          <option value="investment">Investment</option>
          <option value="name">Name</option>
          <option value="created_at">Date Added</option>
        </select>
      </div>

      <div class="filter-group">
        <button @click="resetFilters" class="btn-secondary">Reset</button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading markets...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-state">
      <svg class="error-icon" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <p>{{ error }}</p>
      <button @click="loadMarkets" class="btn-secondary">Retry</button>
    </div>

    <!-- Markets Grid -->
    <div v-if="!isLoading && !error" class="markets-grid">
      <div v-if="markets.length === 0" class="empty-state">
        <p>No markets added yet</p>
        <button @click="showAddMarket = true" class="btn-primary">Add Your First Market</button>
      </div>

      <div v-for="market in markets" :key="market.market_id" class="market-card">
        <div class="market-header">
          <div class="market-info">
            <h3 class="market-name">{{ market.name }}</h3>
            <span class="market-code">{{ market.code || market.market_id }}</span>
          </div>
          <div class="market-badges">
            <span :class="['status-badge', `status-${market.status}`]">
              {{ formatStatus(market.status) }}
            </span>
            <span class="priority-badge" :class="`priority-${getPriorityLevel(market.priority_level)}`">
              Priority {{ market.priority_level }}
            </span>
          </div>
        </div>

        <div class="market-details">
          <div class="detail-row">
            <span class="detail-label">Investment Budget:</span>
            <span class="detail-value">{{ formatCurrency(market.investment_budget) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Campaigns:</span>
            <span class="detail-value">{{ market.campaigns_count || 0 }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Total Spend:</span>
            <span class="detail-value">{{ formatCurrency(market.total_spend || 0) }}</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Date Added:</span>
            <span class="detail-value">{{ formatDate(market.created_at) }}</span>
          </div>
        </div>

        <div v-if="market.notes" class="market-notes">
          <strong>Notes:</strong> {{ market.notes }}
        </div>

        <div class="market-actions">
          <button @click="editMarket(market)" class="btn-action btn-edit">Edit</button>
          <button @click="openRoiCalculator(market)" class="btn-action btn-roi">Calculate ROI</button>
          <button @click="viewCampaigns(market)" class="btn-action btn-view">View Campaigns</button>
          <button @click="removeMarket(market)" class="btn-action btn-delete">Remove</button>
        </div>
      </div>
    </div>

    <!-- Add Market Modal -->
    <div v-if="showAddMarket" class="modal-overlay" @click.self="closeAddMarket">
      <div class="modal-content">
        <div class="modal-header">
          <h3>Add Market</h3>
          <button @click="closeAddMarket" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
          <!-- Step 1: Select Market -->
          <div v-if="!selectedMarket" class="market-selection">
            <h4>Select a Market</h4>
            <div v-if="loadingAvailable" class="loading-inline">
              <div class="spinner-small"></div>
              Loading available markets...
            </div>
            <div v-else class="available-markets">
              <div
                v-for="market in availableMarkets"
                :key="market.id"
                @click="selectMarket(market)"
                class="market-option"
              >
                <div class="market-option-name">{{ market.name }}</div>
                <div class="market-option-code">{{ market.code }}</div>
              </div>
            </div>
          </div>

          <!-- Step 2: Configure Market -->
          <div v-else class="market-configuration">
            <div class="selected-market-info">
              <strong>Selected Market:</strong> {{ selectedMarket.name }}
              <button @click="selectedMarket = null" class="btn-change">Change</button>
            </div>

            <form @submit.prevent="addMarket" class="config-form">
              <div class="form-group">
                <label>Priority Level * (1-10)</label>
                <input
                  v-model.number="marketConfig.priority_level"
                  type="number"
                  min="1"
                  max="10"
                  required
                  placeholder="1 = Highest, 10 = Lowest"
                />
                <small>1 = Highest priority, 10 = Lowest priority</small>
              </div>

              <div class="form-group">
                <label>Investment Budget *</label>
                <input
                  v-model.number="marketConfig.investment_budget"
                  type="number"
                  min="0"
                  step="0.01"
                  required
                  placeholder="0.00"
                />
              </div>

              <div class="form-group">
                <label>Status *</label>
                <select v-model="marketConfig.status" required>
                  <option value="active">Active</option>
                  <option value="testing">Testing</option>
                  <option value="paused">Paused</option>
                </select>
              </div>

              <div class="form-group">
                <label>Notes</label>
                <textarea
                  v-model="marketConfig.notes"
                  rows="3"
                  placeholder="Add any notes about this market"
                ></textarea>
              </div>

              <div class="form-actions">
                <button type="button" @click="closeAddMarket" class="btn-secondary">Cancel</button>
                <button type="submit" class="btn-primary" :disabled="isSaving">
                  {{ isSaving ? 'Adding...' : 'Add Market' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Market Modal -->
    <div v-if="editingMarket" class="modal-overlay" @click.self="closeEditMarket">
      <div class="modal-content modal-small">
        <div class="modal-header">
          <h3>Edit Market: {{ editingMarket.name }}</h3>
          <button @click="closeEditMarket" class="modal-close">&times;</button>
        </div>

        <form @submit.prevent="updateMarket" class="modal-body">
          <div class="form-group">
            <label>Priority Level * (1-10)</label>
            <input
              v-model.number="editForm.priority_level"
              type="number"
              min="1"
              max="10"
              required
            />
          </div>

          <div class="form-group">
            <label>Investment Budget *</label>
            <input
              v-model.number="editForm.investment_budget"
              type="number"
              min="0"
              step="0.01"
              required
            />
          </div>

          <div class="form-group">
            <label>Status *</label>
            <select v-model="editForm.status" required>
              <option value="active">Active</option>
              <option value="testing">Testing</option>
              <option value="paused">Paused</option>
            </select>
          </div>

          <div class="form-group">
            <label>Notes</label>
            <textarea v-model="editForm.notes" rows="3"></textarea>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeEditMarket" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- ROI Calculator Modal -->
    <div v-if="roiMarket" class="modal-overlay" @click.self="closeRoiCalculator">
      <div class="modal-content modal-small">
        <div class="modal-header">
          <h3>ROI Calculator: {{ roiMarket.name }}</h3>
          <button @click="closeRoiCalculator" class="modal-close">&times;</button>
        </div>

        <div class="modal-body">
          <div class="roi-info">
            <div class="roi-detail">
              <span>Investment Budget:</span>
              <strong>{{ formatCurrency(roiMarket.investment_budget) }}</strong>
            </div>
            <div class="roi-detail">
              <span>Current Spend:</span>
              <strong>{{ formatCurrency(roiMarket.total_spend || 0) }}</strong>
            </div>
          </div>

          <form @submit.prevent="calculateRoi" class="roi-form">
            <div class="form-group">
              <label>Revenue Generated *</label>
              <input
                v-model.number="roiRevenue"
                type="number"
                min="0"
                step="0.01"
                required
                placeholder="0.00"
              />
            </div>

            <button type="submit" class="btn-primary" :disabled="isCalculating">
              {{ isCalculating ? 'Calculating...' : 'Calculate ROI' }}
            </button>
          </form>

          <div v-if="roiResult" class="roi-result">
            <h4>Results</h4>
            <div class="result-grid">
              <div class="result-item">
                <span class="result-label">ROI:</span>
                <span :class="['result-value', roiResult.roi_percentage >= 0 ? 'positive' : 'negative']">
                  {{ roiResult.roi_percentage.toFixed(2) }}%
                </span>
              </div>
              <div class="result-item">
                <span class="result-label">Profit/Loss:</span>
                <span :class="['result-value', roiResult.profit >= 0 ? 'positive' : 'negative']">
                  {{ formatCurrency(roiResult.profit) }}
                </span>
              </div>
              <div class="result-item">
                <span class="result-label">Revenue:</span>
                <span class="result-value">{{ formatCurrency(roiResult.revenue) }}</span>
              </div>
              <div class="result-item">
                <span class="result-label">Investment:</span>
                <span class="result-value">{{ formatCurrency(roiResult.investment) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'OrgMarketManager',

  data() {
    return {
      markets: [],
      availableMarkets: [],
      stats: null,
      filters: {
        status: '',
        sort_by: 'priority',
      },
      isLoading: false,
      loadingAvailable: false,
      isSaving: false,
      isCalculating: false,
      error: null,
      showAddMarket: false,
      selectedMarket: null,
      editingMarket: null,
      roiMarket: null,
      marketConfig: {
        priority_level: 5,
        investment_budget: 0,
        status: 'active',
        notes: '',
      },
      editForm: {},
      roiRevenue: 0,
      roiResult: null,
    };
  },

  mounted() {
    this.loadMarkets();
    this.loadStats();
  },

  methods: {
    async loadMarkets() {
      this.isLoading = true;
      this.error = null;

      try {
        const orgId = this.getCurrentOrgId();
        const params = new URLSearchParams(this.filters);
        const response = await this.apiCall('GET', `/orgs/${orgId}/markets?${params}`);

        this.markets = response.data || [];
      } catch (error) {
        this.error = error.message || 'Failed to load markets';
      } finally {
        this.isLoading = false;
      }
    },

    async loadStats() {
      try {
        const orgId = this.getCurrentOrgId();
        const response = await this.apiCall('GET', `/orgs/${orgId}/markets/stats`);
        this.stats = response.data || {};
      } catch (error) {
        console.error('Failed to load stats:', error);
      }
    },

    async loadAvailableMarkets() {
      this.loadingAvailable = true;

      try {
        const orgId = this.getCurrentOrgId();
        const response = await this.apiCall('GET', `/orgs/${orgId}/markets/available`);
        this.availableMarkets = response.data || [];
      } catch (error) {
        this.error = error.message || 'Failed to load available markets';
      } finally {
        this.loadingAvailable = false;
      }
    },

    selectMarket(market) {
      this.selectedMarket = market;
    },

    async addMarket() {
      this.isSaving = true;
      this.error = null;

      try {
        const orgId = this.getCurrentOrgId();
        await this.apiCall('POST', `/orgs/${orgId}/markets`, {
          market_id: this.selectedMarket.id,
          ...this.marketConfig,
        });

        this.closeAddMarket();
        this.loadMarkets();
        this.loadStats();
      } catch (error) {
        this.error = error.message || 'Failed to add market';
      } finally {
        this.isSaving = false;
      }
    },

    editMarket(market) {
      this.editingMarket = market;
      this.editForm = {
        priority_level: market.priority_level,
        investment_budget: market.investment_budget,
        status: market.status,
        notes: market.notes || '',
      };
    },

    async updateMarket() {
      this.isSaving = true;
      this.error = null;

      try {
        const orgId = this.getCurrentOrgId();
        await this.apiCall('PUT', `/orgs/${orgId}/markets/${this.editingMarket.market_id}`, this.editForm);

        this.closeEditMarket();
        this.loadMarkets();
        this.loadStats();
      } catch (error) {
        this.error = error.message || 'Failed to update market';
      } finally {
        this.isSaving = false;
      }
    },

    async removeMarket(market) {
      if (!confirm(`Remove "${market.name}" from your organization? This will not delete campaigns.`)) {
        return;
      }

      try {
        const orgId = this.getCurrentOrgId();
        await this.apiCall('DELETE', `/orgs/${orgId}/markets/${market.market_id}`);

        this.loadMarkets();
        this.loadStats();
      } catch (error) {
        this.error = error.message || 'Failed to remove market';
      }
    },

    openRoiCalculator(market) {
      this.roiMarket = market;
      this.roiRevenue = 0;
      this.roiResult = null;
    },

    async calculateRoi() {
      this.isCalculating = true;

      try {
        const orgId = this.getCurrentOrgId();
        const response = await this.apiCall('POST', `/orgs/${orgId}/markets/${this.roiMarket.market_id}/roi`, {
          revenue: this.roiRevenue,
        });

        this.roiResult = response.data;
      } catch (error) {
        this.error = error.message || 'Failed to calculate ROI';
      } finally {
        this.isCalculating = false;
      }
    },

    viewCampaigns(market) {
      window.location.href = `/campaigns?market_id=${market.market_id}`;
    },

    closeAddMarket() {
      this.showAddMarket = false;
      this.selectedMarket = null;
      this.marketConfig = {
        priority_level: 5,
        investment_budget: 0,
        status: 'active',
        notes: '',
      };
    },

    closeEditMarket() {
      this.editingMarket = null;
      this.editForm = {};
    },

    closeRoiCalculator() {
      this.roiMarket = null;
      this.roiRevenue = 0;
      this.roiResult = null;
    },

    resetFilters() {
      this.filters = {
        status: '',
        sort_by: 'priority',
      };
      this.loadMarkets();
    },

    async apiCall(method, endpoint, data = null) {
      const token = localStorage.getItem('auth_token');
      const config = {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      };

      if (data && (method === 'POST' || method === 'PUT')) {
        config.body = JSON.stringify(data);
      }

      const response = await fetch(`/api${endpoint}`, config);
      const responseData = await response.json();

      if (!response.ok) {
        throw new Error(responseData.message || 'Request failed');
      }

      return responseData;
    },

    getCurrentOrgId() {
      return localStorage.getItem('current_org_id') || 'default';
    },

    formatCurrency(amount) {
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
      }).format(amount || 0);
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString();
    },

    formatStatus(status) {
      const statuses = {
        active: 'Active',
        testing: 'Testing',
        paused: 'Paused',
      };
      return statuses[status] || status;
    },

    getPriorityLevel(priority) {
      if (priority <= 3) return 'high';
      if (priority <= 7) return 'medium';
      return 'low';
    },
  },

  watch: {
    showAddMarket(value) {
      if (value) {
        this.loadAvailableMarkets();
      }
    },
  },
};
</script>

<style scoped>
.org-market-manager {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
}

.manager-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.manager-title {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
  margin: 0;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
}

.stat-label {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 8px;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #111827;
}

.filters-section {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 180px;
}

.filter-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.filter-group select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.markets-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
  gap: 20px;
}

.market-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.market-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.market-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 16px;
}

.market-info {
  flex: 1;
}

.market-name {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0 0 4px 0;
}

.market-code {
  font-size: 12px;
  color: #6b7280;
}

.market-badges {
  display: flex;
  flex-direction: column;
  gap: 4px;
  align-items: flex-end;
}

.status-badge,
.priority-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.status-active { background: #d1fae5; color: #065f46; }
.status-testing { background: #fef3c7; color: #92400e; }
.status-paused { background: #fee2e2; color: #991b1b; }

.priority-high { background: #fee2e2; color: #991b1b; }
.priority-medium { background: #fef3c7; color: #92400e; }
.priority-low { background: #dbeafe; color: #1e40af; }

.market-details {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.detail-label {
  color: #6b7280;
}

.detail-value {
  font-weight: 500;
  color: #111827;
}

.market-notes {
  font-size: 13px;
  color: #6b7280;
  margin-bottom: 16px;
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
}

.market-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.btn-action {
  padding: 6px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-view { background: #f3f4f6; color: #374151; }
.btn-edit { background: #dbeafe; color: #1e40af; }
.btn-roi { background: #d1fae5; color: #065f46; }
.btn-delete { background: #fee2e2; color: #991b1b; }

.btn-primary {
  padding: 10px 20px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

.btn-primary:hover {
  background: #2563eb;
}

.btn-primary:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.btn-secondary {
  padding: 10px 20px;
  background: #f3f4f6;
  color: #374151;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
}

.btn-change {
  padding: 4px 8px;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  margin-left: 8px;
}

.loading-state,
.error-state,
.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

.spinner-small {
  width: 20px;
  height: 20px;
  border: 2px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  display: inline-block;
  margin-right: 8px;
  vertical-align: middle;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-icon {
  width: 48px;
  height: 48px;
  color: #dc2626;
  margin: 0 auto 16px;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-small {
  max-width: 500px;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  font-size: 20px;
  font-weight: 600;
}

.modal-close {
  background: none;
  border: none;
  font-size: 28px;
  color: #6b7280;
  cursor: pointer;
  line-height: 1;
}

.modal-body {
  padding: 20px;
}

.market-selection h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.available-markets {
  display: flex;
  flex-direction: column;
  gap: 8px;
  max-height: 400px;
  overflow-y: auto;
}

.market-option {
  padding: 12px;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s;
}

.market-option:hover {
  border-color: #3b82f6;
  background: #eff6ff;
}

.market-option-name {
  font-weight: 500;
  color: #111827;
  margin-bottom: 4px;
}

.market-option-code {
  font-size: 12px;
  color: #6b7280;
}

.selected-market-info {
  padding: 12px;
  background: #eff6ff;
  border: 1px solid #3b82f6;
  border-radius: 6px;
  margin-bottom: 16px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 6px;
}

.form-group input,
.form-group select,
.form-group textarea {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.form-group small {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.roi-info {
  background: #f9fafb;
  padding: 16px;
  border-radius: 6px;
  margin-bottom: 16px;
}

.roi-detail {
  display: flex;
  justify-content: space-between;
  margin-bottom: 8px;
}

.roi-detail:last-child {
  margin-bottom: 0;
}

.roi-result {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px solid #e5e7eb;
}

.roi-result h4 {
  margin: 0 0 16px 0;
  font-size: 16px;
  font-weight: 600;
}

.result-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
}

.result-item {
  padding: 12px;
  background: #f9fafb;
  border-radius: 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.result-label {
  font-size: 12px;
  color: #6b7280;
}

.result-value {
  font-size: 20px;
  font-weight: 700;
}

.result-value.positive {
  color: #065f46;
}

.result-value.negative {
  color: #991b1b;
}

.loading-inline {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px;
  color: #6b7280;
}
</style>
