/**
 * Scheduled Reports Component (Phase 12)
 *
 * Alpine.js component for managing automated report delivery schedules
 */

export default function scheduledReports() {
    return {
        // State
        schedules: [],
        templates: [],
        selectedSchedule: null,
        executionHistory: [],
        loading: false,
        showCreateModal: false,
        showTemplateModal: false,

        // Form data
        form: {
            name: '',
            report_type: 'organization',
            frequency: 'weekly',
            format: 'pdf',
            recipients: [],
            recipientInput: '',
            config: {},
            timezone: 'UTC',
            delivery_time: '09:00',
            day_of_week: 1,
            day_of_month: 1,
            is_active: true
        },

        // Pagination
        currentPage: 1,
        totalPages: 1,

        // Filters
        filters: {
            active: null,
            frequency: null,
            report_type: null
        },

        // Initialize
        async init() {
            this.orgId = this.$el.dataset.orgId;
            await this.loadSchedules();
            await this.loadTemplates();
        },

        // Load schedules
        async loadSchedules() {
            this.loading = true;

            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: 15
                });

                // Apply filters
                if (this.filters.active !== null) {
                    params.append('active', this.filters.active);
                }
                if (this.filters.frequency) {
                    params.append('frequency', this.filters.frequency);
                }
                if (this.filters.report_type) {
                    params.append('report_type', this.filters.report_type);
                }

                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/scheduled-reports?${params}`,
                    {
                        headers: {
                            'Authorization': `Bearer ${this.getAuthToken()}`,
                            'Accept': 'application/json'
                        }
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.schedules = data.schedules.data;
                    this.currentPage = data.schedules.current_page;
                    this.totalPages = data.schedules.last_page;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_schedules'), error);
                this.showError(__('javascript.failed_to_load_schedules'));
            } finally {
                this.loading = false;
            }
        },

        // Load templates
        async loadTemplates() {
            try {
                const response = await fetch('/api/analytics/report-templates', {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.templates = data.templates;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_templates'), error);
            }
        },

        // Create schedule
        async createSchedule() {
            if (!this.validateForm()) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/scheduled-reports`,
                    {
                        method: 'POST',
                        headers: {
                            'Authorization': `Bearer ${this.getAuthToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.form)
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(__('javascript.scheduled_report_created'));
                    this.showCreateModal = false;
                    this.resetForm();
                    await this.loadSchedules();
                } else {
                    this.showError(data.message || 'Failed to create schedule');
                }
            } catch (error) {
                console.error(__('javascript.failed_to_create_schedule'), error);
                this.showError(__('javascript.failed_to_create_schedule'));
            } finally {
                this.loading = false;
            }
        },

        // Update schedule
        async updateSchedule(scheduleId) {
            this.loading = true;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/scheduled-reports/${scheduleId}`,
                    {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${this.getAuthToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(this.form)
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(__('javascript.schedule_updated'));
                    await this.loadSchedules();
                }
            } catch (error) {
                console.error(__('javascript.failed_to_update_schedule'), error);
                this.showError(__('javascript.failed_to_update_schedule'));
            } finally {
                this.loading = false;
            }
        },

        // Delete schedule
        async deleteSchedule(scheduleId) {
            if (!confirm(__('javascript.confirm_delete_scheduled_report'))) {
                return;
            }

            this.loading = true;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/scheduled-reports/${scheduleId}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'Authorization': `Bearer ${this.getAuthToken()}`,
                            'Accept': 'application/json'
                        }
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.showSuccess(__('javascript.scheduled_report_deleted'));
                    await this.loadSchedules();
                }
            } catch (error) {
                console.error(__('javascript.failed_to_delete_schedule'), error);
                this.showError(__('javascript.failed_to_delete_schedule'));
            } finally {
                this.loading = false;
            }
        },

        // Toggle schedule active status
        async toggleActive(schedule) {
            const updatedStatus = !schedule.is_active;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/scheduled-reports/${schedule.schedule_id}`,
                    {
                        method: 'PUT',
                        headers: {
                            'Authorization': `Bearer ${this.getAuthToken()}`,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ is_active: updatedStatus })
                    }
                );

                const data = await response.json();

                if (data.success) {
                    schedule.is_active = updatedStatus;
                    this.showSuccess(updatedStatus ? __('javascript.schedule_activated') : __('javascript.schedule_deactivated'));
                }
            } catch (error) {
                console.error(__('javascript.failed_to_update_schedule'), error);
                this.showError(__('javascript.failed_to_update_schedule'));
            }
        },

        // Load execution history
        async loadHistory(scheduleId) {
            this.loading = true;

            try {
                const response = await fetch(
                    `/api/orgs/${this.orgId}/analytics/scheduled-reports/${scheduleId}/history`,
                    {
                        headers: {
                            'Authorization': `Bearer ${this.getAuthToken()}`,
                            'Accept': 'application/json'
                        }
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.executionHistory = data.history.data;
                }
            } catch (error) {
                console.error(__('javascript.failed_to_load_history'), error);
                this.showError(__('javascript.failed_to_load_history'));
            } finally {
                this.loading = false;
            }
        },

        // Create from template
        async createFromTemplate(templateId) {
            const template = this.templates.find(t => t.template_id === templateId);

            if (!template) {
                return;
            }

            // Pre-fill form with template defaults
            this.form.name = `${template.name} - ${new Date().toISOString().split('T')[0]}`;
            this.form.report_type = template.report_type;
            this.form.config = { ...template.default_config };

            this.showTemplateModal = false;
            this.showCreateModal = true;
        },

        // Form management
        addRecipient() {
            const email = this.form.recipientInput.trim();

            if (email && this.isValidEmail(email)) {
                if (!this.form.recipients.includes(email)) {
                    this.form.recipients.push(email);
                    this.form.recipientInput = '';
                }
            } else {
                this.showError(__('javascript.please_enter_valid_email'));
            }
        },

        removeRecipient(email) {
            this.form.recipients = this.form.recipients.filter(r => r !== email);
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        validateForm() {
            if (!this.form.name) {
                this.showError(__('javascript.please_enter_report_name'));
                return false;
            }

            if (this.form.recipients.length === 0) {
                this.showError(__('javascript.please_add_recipient'));
                return false;
            }

            return true;
        },

        resetForm() {
            this.form = {
                name: '',
                report_type: 'organization',
                frequency: 'weekly',
                format: 'pdf',
                recipients: [],
                recipientInput: '',
                config: {},
                timezone: 'UTC',
                delivery_time: '09:00',
                day_of_week: 1,
                day_of_month: 1,
                is_active: true
            };
        },

        // Utilities
        getAuthToken() {
            return localStorage.getItem('auth_token') || '';
        },

        formatDate(dateString) {
            return new Date(dateString).toLocaleString();
        },

        getFrequencyLabel(frequency) {
            return frequency.charAt(0).toUpperCase() + frequency.slice(1);
        },

        getReportTypeLabel(type) {
            return type.split('_').map(word =>
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
        },

        getStatusBadgeColor(status) {
            const colors = {
                'success': 'green',
                'failed': 'red',
                'partial': 'yellow'
            };
            return colors[status] || 'gray';
        },

        showSuccess(message) {
            // Dispatch custom event for notification center
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
