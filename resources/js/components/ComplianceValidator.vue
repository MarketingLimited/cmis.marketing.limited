<template>
  <div class="compliance-validator">
    <div class="validator-header">
      <h3 class="validator-title">Content Compliance Check</h3>
      <div v-if="validationResult" class="compliance-score">
        <span class="score-label">Compliance Score:</span>
        <span
          class="score-value"
          :class="scoreClass"
        >
          {{ validationResult.score }}%
        </span>
      </div>
    </div>

    <div class="content-input-section">
      <label for="content-input" class="input-label">
        Content to Validate
      </label>
      <textarea
        id="content-input"
        v-model="content"
        class="content-textarea"
        rows="6"
        placeholder="Enter your content here to check compliance..."
        @input="debouncedValidate"
      ></textarea>
      <div class="character-count">
        {{ content.length }} characters
      </div>
    </div>

    <div v-if="showContextOptions" class="context-options">
      <div class="option-group">
        <label>Platform:</label>
        <select v-model="context.platform" @change="validateContent">
          <option value="">Any Platform</option>
          <option value="facebook">Facebook</option>
          <option value="instagram">Instagram</option>
          <option value="twitter">Twitter</option>
          <option value="linkedin">LinkedIn</option>
        </select>
      </div>

      <div class="option-group">
        <label>Market:</label>
        <select v-model="context.market" @change="validateContent">
          <option value="">Any Market</option>
          <option value="US">United States</option>
          <option value="EU">European Union</option>
          <option value="CA">California (CCPA)</option>
          <option value="UK">United Kingdom</option>
        </select>
      </div>

      <div class="option-group">
        <label>Content Type:</label>
        <select v-model="context.content_type" @change="validateContent">
          <option value="">Any Type</option>
          <option value="ad">Advertisement</option>
          <option value="organic">Organic Post</option>
          <option value="sponsored">Sponsored Content</option>
        </select>
      </div>
    </div>

    <button
      class="validate-button"
      @click="validateContent"
      :disabled="isValidating || !content.trim()"
    >
      <span v-if="isValidating">Validating...</span>
      <span v-else>Check Compliance</span>
    </button>

    <div v-if="error" class="error-message">
      <strong>Error:</strong> {{ error }}
    </div>

    <div v-if="validationResult" class="validation-results">
      <div
        v-if="validationResult.is_compliant"
        class="result-status success"
      >
        <svg class="status-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span>Content is compliant with all rules</span>
      </div>

      <div
        v-else
        class="result-status error"
      >
        <svg class="status-icon" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <span>Content has {{ validationResult.violations.length }} compliance issue(s)</span>
      </div>

      <div v-if="validationResult.violations && validationResult.violations.length > 0" class="violations-section">
        <h4 class="section-title">Violations</h4>
        <div
          v-for="(violation, index) in validationResult.violations"
          :key="index"
          class="violation-item"
          :class="`severity-${violation.severity}`"
        >
          <div class="violation-header">
            <span class="severity-badge" :class="`badge-${violation.severity}`">
              {{ violation.severity }}
            </span>
            <span class="violation-name">{{ violation.rule_name }}</span>
          </div>
          <p class="violation-message">{{ violation.message }}</p>
        </div>
      </div>

      <div v-if="validationResult.warnings && validationResult.warnings.length > 0" class="warnings-section">
        <h4 class="section-title">Warnings</h4>
        <div
          v-for="(warning, index) in validationResult.warnings"
          :key="index"
          class="warning-item"
        >
          <svg class="warning-icon" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
          </svg>
          <span>{{ warning }}</span>
        </div>
      </div>

      <div v-if="validationResult.suggestions && validationResult.suggestions.length > 0" class="suggestions-section">
        <h4 class="section-title">Suggestions</h4>
        <ul class="suggestions-list">
          <li v-for="(suggestion, index) in validationResult.suggestions" :key="index">
            {{ suggestion }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ComplianceValidator',

  props: {
    initialContent: {
      type: String,
      default: '',
    },
    showContextOptions: {
      type: Boolean,
      default: true,
    },
    autoValidate: {
      type: Boolean,
      default: true,
    },
    debounceDelay: {
      type: Number,
      default: 1000,
    },
  },

  data() {
    return {
      content: this.initialContent,
      context: {
        platform: '',
        market: '',
        content_type: '',
      },
      validationResult: null,
      isValidating: false,
      error: null,
      debounceTimer: null,
    };
  },

  computed: {
    scoreClass() {
      if (!this.validationResult) return '';
      const score = this.validationResult.score;
      if (score >= 90) return 'score-excellent';
      if (score >= 70) return 'score-good';
      if (score >= 50) return 'score-fair';
      return 'score-poor';
    },
  },

  watch: {
    initialContent(newValue) {
      this.content = newValue;
      if (this.autoValidate) {
        this.debouncedValidate();
      }
    },
  },

  methods: {
    async validateContent() {
      if (!this.content.trim()) {
        this.validationResult = null;
        return;
      }

      this.isValidating = true;
      this.error = null;

      try {
        // Use the API client from parent or make direct request
        const response = await fetch('/api/compliance/validate', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${this.getAuthToken()}`,
          },
          body: JSON.stringify({
            content: this.content,
            context: this.context,
          }),
        });

        if (!response.ok) {
          throw new Error('Validation request failed');
        }

        const data = await response.json();
        this.validationResult = data.data || data;

        this.$emit('validation-complete', this.validationResult);
      } catch (err) {
        this.error = err.message || 'Failed to validate content';
        this.$emit('validation-error', err);
      } finally {
        this.isValidating = false;
      }
    },

    debouncedValidate() {
      if (!this.autoValidate) return;

      clearTimeout(this.debounceTimer);
      this.debounceTimer = setTimeout(() => {
        this.validateContent();
      }, this.debounceDelay);
    },

    getAuthToken() {
      // Get token from localStorage or Vuex store
      return localStorage.getItem('auth_token') || '';
    },

    clearValidation() {
      this.validationResult = null;
      this.error = null;
    },

    reset() {
      this.content = '';
      this.context = {
        platform: '',
        market: '',
        content_type: '',
      };
      this.clearValidation();
    },
  },

  mounted() {
    if (this.initialContent && this.autoValidate) {
      this.validateContent();
    }
  },

  beforeUnmount() {
    clearTimeout(this.debounceTimer);
  },
};
</script>

<style scoped>
.compliance-validator {
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 24px;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.validator-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.validator-title {
  font-size: 20px;
  font-weight: 600;
  color: #111827;
  margin: 0;
}

.compliance-score {
  display: flex;
  align-items: center;
  gap: 8px;
}

.score-label {
  font-size: 14px;
  color: #6b7280;
}

.score-value {
  font-size: 24px;
  font-weight: 700;
}

.score-excellent { color: #10b981; }
.score-good { color: #3b82f6; }
.score-fair { color: #f59e0b; }
.score-poor { color: #ef4444; }

.content-input-section {
  margin-bottom: 20px;
}

.input-label {
  display: block;
  font-size: 14px;
  font-weight: 500;
  color: #374151;
  margin-bottom: 8px;
}

.content-textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  line-height: 1.5;
  resize: vertical;
  transition: border-color 0.2s;
}

.content-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.character-count {
  text-align: right;
  font-size: 12px;
  color: #6b7280;
  margin-top: 4px;
}

.context-options {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
  margin-bottom: 20px;
  padding: 16px;
  background: #f9fafb;
  border-radius: 6px;
}

.option-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.option-group label {
  font-size: 13px;
  font-weight: 500;
  color: #374151;
}

.option-group select {
  padding: 8px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  background: white;
}

.validate-button {
  width: 100%;
  padding: 12px;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-size: 15px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.validate-button:hover:not(:disabled) {
  background: #2563eb;
}

.validate-button:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}

.error-message {
  margin-top: 16px;
  padding: 12px;
  background: #fee2e2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  color: #991b1b;
  font-size: 14px;
}

.validation-results {
  margin-top: 24px;
}

.result-status {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  border-radius: 6px;
  font-weight: 500;
  margin-bottom: 20px;
}

.result-status.success {
  background: #d1fae5;
  color: #065f46;
}

.result-status.error {
  background: #fee2e2;
  color: #991b1b;
}

.status-icon {
  width: 24px;
  height: 24px;
  flex-shrink: 0;
}

.section-title {
  font-size: 16px;
  font-weight: 600;
  color: #111827;
  margin: 20px 0 12px 0;
}

.violations-section,
.warnings-section,
.suggestions-section {
  margin-bottom: 20px;
}

.violation-item {
  padding: 12px;
  border-left: 4px solid;
  border-radius: 4px;
  margin-bottom: 8px;
}

.severity-critical {
  background: #fef2f2;
  border-color: #dc2626;
}

.severity-high {
  background: #fef3f2;
  border-color: #f97316;
}

.severity-medium {
  background: #fefce8;
  border-color: #eab308;
}

.severity-low {
  background: #f0f9ff;
  border-color: #3b82f6;
}

.violation-header {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.severity-badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.badge-critical {
  background: #dc2626;
  color: white;
}

.badge-high {
  background: #f97316;
  color: white;
}

.badge-medium {
  background: #eab308;
  color: white;
}

.badge-low {
  background: #3b82f6;
  color: white;
}

.violation-name {
  font-weight: 600;
  color: #111827;
}

.violation-message {
  margin: 4px 0 0 0;
  font-size: 14px;
  color: #4b5563;
}

.warning-item {
  display: flex;
  align-items: start;
  gap: 8px;
  padding: 10px;
  background: #fffbeb;
  border: 1px solid #fef3c7;
  border-radius: 4px;
  margin-bottom: 8px;
  font-size: 14px;
  color: #92400e;
}

.warning-icon {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
  color: #f59e0b;
}

.suggestions-list {
  margin: 0;
  padding-left: 24px;
}

.suggestions-list li {
  margin-bottom: 8px;
  font-size: 14px;
  color: #4b5563;
  line-height: 1.6;
}
</style>
