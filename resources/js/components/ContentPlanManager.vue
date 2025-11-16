<template>
  <div class="content-plan-manager">
    <div class="manager-header">
      <h2 class="manager-title">Content Plans</h2>
      <button @click="openCreateModal" class="btn-primary">
        Create New Plan
      </button>
    </div>

    <!-- Filters -->
    <div class="filters-section">
      <div class="filter-group">
        <label>Status:</label>
        <select v-model="filters.status" @change="loadPlans">
          <option value="">All Status</option>
          <option value="draft">Draft</option>
          <option value="pending">Pending Review</option>
          <option value="approved">Approved</option>
          <option value="rejected">Rejected</option>
          <option value="published">Published</option>
        </select>
      </div>

      <div class="filter-group">
        <label>Campaign:</label>
        <select v-model="filters.campaign_id" @change="loadPlans">
          <option value="">All Campaigns</option>
          <option v-for="campaign in campaigns" :key="campaign.id" :value="campaign.id">
            {{ campaign.name }}
          </option>
        </select>
      </div>

      <div class="filter-group">
        <label>Content Type:</label>
        <select v-model="filters.content_type" @change="loadPlans">
          <option value="">All Types</option>
          <option value="social_post">Social Post</option>
          <option value="blog_article">Blog Article</option>
          <option value="ad_copy">Ad Copy</option>
          <option value="email">Email</option>
          <option value="video_script">Video Script</option>
        </select>
      </div>

      <div class="filter-group">
        <button @click="resetFilters" class="btn-secondary">Reset Filters</button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="isLoading" class="loading-state">
      <div class="spinner"></div>
      <p>Loading content plans...</p>
    </div>

    <!-- Error State -->
    <div v-if="error" class="error-state">
      <svg class="error-icon" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <p>{{ error }}</p>
      <button @click="loadPlans" class="btn-secondary">Retry</button>
    </div>

    <!-- Plans Grid -->
    <div v-if="!isLoading && !error" class="plans-grid">
      <div v-if="plans.length === 0" class="empty-state">
        <p>No content plans found</p>
        <button @click="openCreateModal" class="btn-primary">Create Your First Plan</button>
      </div>

      <div v-for="plan in plans" :key="plan.id" class="plan-card">
        <div class="plan-header">
          <h3 class="plan-name">{{ plan.name }}</h3>
          <span :class="['status-badge', `status-${plan.status}`]">
            {{ formatStatus(plan.status) }}
          </span>
        </div>

        <div class="plan-details">
          <div class="detail-item">
            <span class="detail-label">Campaign:</span>
            <span class="detail-value">{{ plan.campaign?.name || 'N/A' }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Type:</span>
            <span class="detail-value">{{ formatContentType(plan.content_type) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Platforms:</span>
            <span class="detail-value">{{ formatPlatforms(plan.target_platforms) }}</span>
          </div>
          <div class="detail-item">
            <span class="detail-label">Created:</span>
            <span class="detail-value">{{ formatDate(plan.created_at) }}</span>
          </div>
        </div>

        <div v-if="plan.description" class="plan-description">
          {{ truncate(plan.description, 100) }}
        </div>

        <div class="plan-actions">
          <button @click="viewPlan(plan)" class="btn-action btn-view">View</button>
          <button @click="editPlan(plan)" class="btn-action btn-edit">Edit</button>

          <template v-if="plan.status === 'draft'">
            <button @click="submitForReview(plan)" class="btn-action btn-submit">
              Submit for Review
            </button>
          </template>

          <template v-if="plan.status === 'pending'">
            <button @click="approvePlan(plan)" class="btn-action btn-approve">Approve</button>
            <button @click="openRejectModal(plan)" class="btn-action btn-reject">Reject</button>
          </template>

          <template v-if="plan.status === 'approved'">
            <button @click="publishPlan(plan)" class="btn-action btn-publish">Publish</button>
          </template>

          <button @click="deletePlan(plan)" class="btn-action btn-delete">Delete</button>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.total > pagination.per_page" class="pagination">
      <button
        @click="changePage(pagination.current_page - 1)"
        :disabled="pagination.current_page === 1"
        class="pagination-btn"
      >
        Previous
      </button>
      <span class="pagination-info">
        Page {{ pagination.current_page }} of {{ pagination.last_page }}
      </span>
      <button
        @click="changePage(pagination.current_page + 1)"
        :disabled="pagination.current_page === pagination.last_page"
        class="pagination-btn"
      >
        Next
      </button>
    </div>

    <!-- Create/Edit Modal -->
    <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
      <div class="modal-content">
        <div class="modal-header">
          <h3>{{ editingPlan ? 'Edit Content Plan' : 'Create Content Plan' }}</h3>
          <button @click="closeModal" class="modal-close">&times;</button>
        </div>

        <form @submit.prevent="savePlan" class="plan-form">
          <div class="form-group">
            <label>Name *</label>
            <input
              v-model="formData.name"
              type="text"
              required
              placeholder="Enter plan name"
            />
          </div>

          <div class="form-group">
            <label>Campaign *</label>
            <select v-model="formData.campaign_id" required>
              <option value="">Select a campaign</option>
              <option v-for="campaign in campaigns" :key="campaign.id" :value="campaign.id">
                {{ campaign.name }}
              </option>
            </select>
          </div>

          <div class="form-group">
            <label>Content Type *</label>
            <select v-model="formData.content_type" required>
              <option value="social_post">Social Post</option>
              <option value="blog_article">Blog Article</option>
              <option value="ad_copy">Ad Copy</option>
              <option value="email">Email</option>
              <option value="video_script">Video Script</option>
            </select>
          </div>

          <div class="form-group">
            <label>Description</label>
            <textarea
              v-model="formData.description"
              rows="3"
              placeholder="Describe the content plan"
            ></textarea>
          </div>

          <div class="form-group">
            <label>Target Platforms *</label>
            <div class="checkbox-group">
              <label v-for="platform in availablePlatforms" :key="platform.value">
                <input
                  type="checkbox"
                  :value="platform.value"
                  v-model="formData.target_platforms"
                />
                {{ platform.label }}
              </label>
            </div>
          </div>

          <div class="form-group">
            <label>Tone</label>
            <select v-model="formData.tone">
              <option value="">Select tone</option>
              <option value="professional">Professional</option>
              <option value="casual">Casual</option>
              <option value="friendly">Friendly</option>
              <option value="formal">Formal</option>
              <option value="humorous">Humorous</option>
            </select>
          </div>

          <div class="form-group">
            <label>Key Messages</label>
            <textarea
              v-model="keyMessagesText"
              rows="3"
              placeholder="Enter key messages (one per line)"
            ></textarea>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Saving...' : 'Save' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Reject Modal -->
    <div v-if="showRejectModal" class="modal-overlay" @click.self="closeRejectModal">
      <div class="modal-content modal-small">
        <div class="modal-header">
          <h3>Reject Content Plan</h3>
          <button @click="closeRejectModal" class="modal-close">&times;</button>
        </div>

        <form @submit.prevent="confirmReject" class="reject-form">
          <div class="form-group">
            <label>Reason for Rejection *</label>
            <textarea
              v-model="rejectReason"
              rows="4"
              required
              placeholder="Please provide a reason for rejecting this content plan"
            ></textarea>
          </div>

          <div class="form-actions">
            <button type="button" @click="closeRejectModal" class="btn-secondary">Cancel</button>
            <button type="submit" class="btn-danger" :disabled="isSaving">
              {{ isSaving ? 'Rejecting...' : 'Reject Plan' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ContentPlanManager',

  data() {
    return {
      plans: [],
      campaigns: [],
      filters: {
        status: '',
        campaign_id: '',
        content_type: '',
      },
      pagination: {
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
      },
      isLoading: false,
      isSaving: false,
      error: null,
      showModal: false,
      showRejectModal: false,
      editingPlan: null,
      rejectingPlan: null,
      rejectReason: '',
      formData: {
        name: '',
        campaign_id: '',
        content_type: 'social_post',
        description: '',
        target_platforms: [],
        tone: '',
        key_messages: [],
      },
      keyMessagesText: '',
      availablePlatforms: [
        { value: 'facebook', label: 'Facebook' },
        { value: 'instagram', label: 'Instagram' },
        { value: 'twitter', label: 'Twitter' },
        { value: 'linkedin', label: 'LinkedIn' },
        { value: 'tiktok', label: 'TikTok' },
        { value: 'youtube', label: 'YouTube' },
      ],
    };
  },

  mounted() {
    this.loadCampaigns();
    this.loadPlans();
  },

  methods: {
    async loadCampaigns() {
      try {
        const response = await this.apiCall('GET', '/campaigns');
        this.campaigns = response.data || [];
      } catch (error) {
        console.error('Failed to load campaigns:', error);
      }
    },

    async loadPlans() {
      this.isLoading = true;
      this.error = null;

      try {
        const params = new URLSearchParams({
          page: this.pagination.current_page,
          per_page: this.pagination.per_page,
          ...this.filters,
        });

        const response = await this.apiCall('GET', `/creative/content-plans?${params}`);

        this.plans = response.data || [];
        if (response.meta) {
          this.pagination = {
            current_page: response.meta.current_page || 1,
            last_page: response.meta.last_page || 1,
            per_page: response.meta.per_page || 15,
            total: response.meta.total || 0,
          };
        }
      } catch (error) {
        this.error = error.message || 'Failed to load content plans';
      } finally {
        this.isLoading = false;
      }
    },

    async savePlan() {
      this.isSaving = true;
      this.error = null;

      try {
        // Parse key messages from text
        this.formData.key_messages = this.keyMessagesText
          .split('\n')
          .filter(msg => msg.trim())
          .map(msg => msg.trim());

        if (this.editingPlan) {
          await this.apiCall('PUT', `/creative/content-plans/${this.editingPlan.plan_id}`, this.formData);
        } else {
          await this.apiCall('POST', '/creative/content-plans', this.formData);
        }

        this.closeModal();
        this.loadPlans();
      } catch (error) {
        this.error = error.message || 'Failed to save content plan';
      } finally {
        this.isSaving = false;
      }
    },

    async submitForReview(plan) {
      if (!confirm('Submit this plan for review?')) return;

      try {
        await this.apiCall('PUT', `/creative/content-plans/${plan.plan_id}`, {
          status: 'pending',
        });
        this.loadPlans();
      } catch (error) {
        this.error = error.message || 'Failed to submit plan';
      }
    },

    async approvePlan(plan) {
      if (!confirm('Approve this content plan?')) return;

      try {
        await this.apiCall('POST', `/creative/content-plans/${plan.plan_id}/approve`);
        this.loadPlans();
      } catch (error) {
        this.error = error.message || 'Failed to approve plan';
      }
    },

    openRejectModal(plan) {
      this.rejectingPlan = plan;
      this.rejectReason = '';
      this.showRejectModal = true;
    },

    closeRejectModal() {
      this.showRejectModal = false;
      this.rejectingPlan = null;
      this.rejectReason = '';
    },

    async confirmReject() {
      if (!this.rejectReason.trim()) return;

      this.isSaving = true;
      try {
        await this.apiCall('POST', `/creative/content-plans/${this.rejectingPlan.plan_id}/reject`, {
          reason: this.rejectReason,
        });
        this.closeRejectModal();
        this.loadPlans();
      } catch (error) {
        this.error = error.message || 'Failed to reject plan';
      } finally {
        this.isSaving = false;
      }
    },

    async publishPlan(plan) {
      if (!confirm('Publish this content plan?')) return;

      try {
        await this.apiCall('POST', `/creative/content-plans/${plan.plan_id}/publish`);
        this.loadPlans();
      } catch (error) {
        this.error = error.message || 'Failed to publish plan';
      }
    },

    async deletePlan(plan) {
      if (!confirm(`Delete "${plan.name}"? This action cannot be undone.`)) return;

      try {
        await this.apiCall('DELETE', `/creative/content-plans/${plan.plan_id}`);
        this.loadPlans();
      } catch (error) {
        this.error = error.message || 'Failed to delete plan';
      }
    },

    openCreateModal() {
      this.editingPlan = null;
      this.resetForm();
      this.showModal = true;
    },

    editPlan(plan) {
      this.editingPlan = plan;
      this.formData = {
        name: plan.name,
        campaign_id: plan.campaign_id,
        content_type: plan.content_type,
        description: plan.description || '',
        target_platforms: plan.target_platforms || [],
        tone: plan.tone || '',
        key_messages: plan.key_messages || [],
      };
      this.keyMessagesText = (plan.key_messages || []).join('\n');
      this.showModal = true;
    },

    viewPlan(plan) {
      // Navigate to plan detail page or open detail modal
      window.location.href = `/content-plans/${plan.plan_id}`;
    },

    closeModal() {
      this.showModal = false;
      this.editingPlan = null;
      this.resetForm();
    },

    resetForm() {
      this.formData = {
        name: '',
        campaign_id: '',
        content_type: 'social_post',
        description: '',
        target_platforms: [],
        tone: '',
        key_messages: [],
      };
      this.keyMessagesText = '';
    },

    resetFilters() {
      this.filters = {
        status: '',
        campaign_id: '',
        content_type: '',
      };
      this.pagination.current_page = 1;
      this.loadPlans();
    },

    changePage(page) {
      if (page < 1 || page > this.pagination.last_page) return;
      this.pagination.current_page = page;
      this.loadPlans();
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

    formatStatus(status) {
      const statuses = {
        draft: 'Draft',
        pending: 'Pending Review',
        approved: 'Approved',
        rejected: 'Rejected',
        published: 'Published',
      };
      return statuses[status] || status;
    },

    formatContentType(type) {
      const types = {
        social_post: 'Social Post',
        blog_article: 'Blog Article',
        ad_copy: 'Ad Copy',
        email: 'Email',
        video_script: 'Video Script',
      };
      return types[type] || type;
    },

    formatPlatforms(platforms) {
      if (!platforms || platforms.length === 0) return 'N/A';
      return platforms.map(p => p.charAt(0).toUpperCase() + p.slice(1)).join(', ');
    },

    formatDate(dateString) {
      if (!dateString) return 'N/A';
      return new Date(dateString).toLocaleDateString();
    },

    truncate(text, length) {
      if (!text) return '';
      return text.length > length ? text.substring(0, length) + '...' : text;
    },
  },
};
</script>

<style scoped>
.content-plan-manager {
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

.filters-section {
  display: flex;
  gap: 16px;
  margin-bottom: 24px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 8px;
  flex-wrap: wrap;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
  min-width: 200px;
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

.plans-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 20px;
}

.plan-card {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  transition: box-shadow 0.2s;
}

.plan-card:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.plan-header {
  display: flex;
  justify-content: space-between;
  align-items: start;
  margin-bottom: 16px;
}

.plan-name {
  font-size: 18px;
  font-weight: 600;
  color: #111827;
  margin: 0;
  flex: 1;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
}

.status-draft { background: #e5e7eb; color: #374151; }
.status-pending { background: #fef3c7; color: #92400e; }
.status-approved { background: #d1fae5; color: #065f46; }
.status-rejected { background: #fee2e2; color: #991b1b; }
.status-published { background: #dbeafe; color: #1e40af; }

.plan-details {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
  margin-bottom: 12px;
}

.detail-item {
  font-size: 13px;
}

.detail-label {
  font-weight: 500;
  color: #6b7280;
  margin-right: 4px;
}

.detail-value {
  color: #111827;
}

.plan-description {
  font-size: 14px;
  color: #6b7280;
  margin-bottom: 16px;
  line-height: 1.5;
}

.plan-actions {
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
.btn-submit { background: #fef3c7; color: #92400e; }
.btn-approve { background: #d1fae5; color: #065f46; }
.btn-reject { background: #fee2e2; color: #991b1b; }
.btn-publish { background: #dbeafe; color: #1e40af; }
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

.btn-danger {
  padding: 10px 20px;
  background: #dc2626;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
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

.plan-form,
.reject-form {
  padding: 20px;
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

.checkbox-group {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 8px;
}

.checkbox-group label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: normal;
}

.form-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 24px;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
}

.pagination-btn {
  padding: 8px 16px;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  cursor: pointer;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  font-size: 14px;
  color: #6b7280;
}
</style>
