/**
 * Publish Modal - Publishing Management Module
 * Handles publish now, schedule, add to queue, save draft, submit for approval
 */

export function getPublishingManagementMethods() {
    return {
        // ============================================
        // DRAFT MANAGEMENT
        // ============================================

        async saveDraft() {
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/save-draft`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.scheduleEnabled ? this.schedule : null,
                        is_draft: true
                    })
                });
                if (response.ok) {
                    window.notify('Draft saved successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to save draft', 'error');
                }
            } catch (e) {
                console.error('Failed to save draft', e);
                window.notify('Failed to save draft', 'error');
            }
        },

        // ============================================
        // PUBLISH NOW
        // ============================================

        async publishNow() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        is_draft: false
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post created successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to create post', 'error');
                }
            } catch (e) {
                console.error('Failed to publish', e);
                window.notify('Failed to publish post', 'error');
            }
        },

        // ============================================
        // SCHEDULE POST
        // ============================================

        async schedulePost() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.schedule,
                        is_draft: false
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post scheduled successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to schedule post', 'error');
                }
            } catch (e) {
                console.error('Failed to schedule', e);
                window.notify('Failed to schedule post', 'error');
            }
        },

        // ============================================
        // ADD TO QUEUE
        // ============================================

        async addToQueue() {
            if (!this.canSubmit) return;
            try {
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/create`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        queue_position: this.queuePosition
                    })
                });
                if (response.ok) {
                    const data = await response.json();
                    window.notify(data.message || 'Post added to queue successfully', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to add post to queue', 'error');
                }
            } catch (e) {
                console.error('Failed to add to queue', e);
                window.notify('Failed to add post to queue', 'error');
            }
        },

        // ============================================
        // SUBMIT FOR APPROVAL
        // ============================================

        async submitForApproval() {
            if (!this.canSubmit) return;
            try {
                // For now, treat as draft with pending approval status
                const response = await fetch(`/orgs/${window.currentOrgId}/social/publish-modal/save-draft`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    body: JSON.stringify({
                        profile_ids: this.selectedProfiles.map(p => p.integration_id),
                        content: this.content,
                        schedule: this.scheduleEnabled ? this.schedule : null,
                        is_draft: true,
                        requires_approval: true
                    })
                });
                if (response.ok) {
                    window.notify('Post submitted for approval', 'success');
                    this.closeModal();
                } else {
                    const data = await response.json();
                    window.notify(data.message || 'Failed to submit for approval', 'error');
                }
            } catch (e) {
                console.error('Failed to submit for approval', e);
                window.notify('Failed to submit for approval', 'error');
            }
        },

        // ============================================
        // CALENDAR DRAG & DROP RESCHEDULING
        // ============================================

        async reschedulePostByDrag(newDate) {
            if (!this.draggedPost) return;

            try {
                // Update post schedule via API
                const response = await fetch(`/orgs/${window.currentOrgId}/social/posts/${this.draggedPost.id}/reschedule`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        scheduled_date: newDate,
                        scheduled_time: this.draggedPost.time
                    })
                });

                if (response.ok) {
                    // Update local state
                    this.draggedPost.scheduled_date = newDate;

                    // Reload calendar to reflect changes
                    await this.loadScheduledPosts();

                    // Show success feedback
                    this.showToast('Post rescheduled successfully', 'success');
                } else {
                    throw new Error('Failed to reschedule post');
                }
            } catch (error) {
                console.error('Failed to reschedule post:', error);
                this.showToast('Failed to reschedule post', 'error');
            } finally {
                this.draggedPost = null;
                this.dragOverDate = null;
            }
        },

        showToast(message, type = 'info') {
            // Simple toast notification (can be enhanced with a toast library)
            const toast = document.createElement('div');
            toast.className = `fixed bottom-4 end-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.remove();
            }, 3000);
        },

        // ============================================
        // PUBLISH STATUS HELPERS
        // ============================================

        getPublishStatusText() {
            if (this.publishMode === 'now') {
                return 'Publishing now';
            } else if (this.publishMode === 'schedule' && this.schedule.date && this.schedule.time) {
                return `Scheduled: ${this.schedule.date} ${this.schedule.time}`;
            }
            return 'Just now';
        }
    };
}

export default getPublishingManagementMethods;
