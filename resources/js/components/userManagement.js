/**
 * User Management Component (Phase 2 - Option 4: User Management UI)
 *
 * Alpine.js component for comprehensive user management
 *
 * Features:
 * - User listing with search and filters
 * - Email invitations
 * - Role management
 * - User activation/deactivation
 * - Activity log viewing
 * - Pending invitations management
 *
 * Usage:
 * <div x-data="userManagement('{org_id}')" x-init="init()">
 *     <div x-html="renderUserManagement()"></div>
 * </div>
 */

export default function userManagement(orgId) {
    return {
        // State
        orgId: orgId,
        users: [],
        invitations: [],
        roles: [],
        isLoading: false,
        error: null,

        // Filters
        searchQuery: '',
        roleFilter: '',
        statusFilter: '',

        // Pagination
        currentPage: 1,
        perPage: 20,
        totalPages: 1,
        totalUsers: 0,

        // Modals
        showInviteModal: false,
        showActivityModal: false,
        selectedUser: null,
        userActivity: [],

        // Invite form
        inviteEmail: '',
        inviteRoleId: '',
        inviteMessage: '',

        /**
         * Initialize the component
         */
        async init() {
            await Promise.all([
                this.loadUsers(),
                this.loadRoles(),
                this.loadInvitations()
            ]);
        },

        /**
         * Load users from API
         */
        async loadUsers() {
            this.isLoading = true;
            this.error = null;

            try {
                const params = new URLSearchParams({
                    page: this.currentPage,
                    per_page: this.perPage,
                });

                if (this.searchQuery) params.append('search', this.searchQuery);
                if (this.roleFilter) params.append('role_id', this.roleFilter);
                if (this.statusFilter) params.append('status', this.statusFilter);

                const response = await fetch(`/api/orgs/${this.orgId}/users?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load users');
                }

                const data = await response.json();

                if (data.success) {
                    this.users = data.users;
                    this.totalUsers = data.pagination.total;
                    this.totalPages = data.pagination.last_page;
                } else {
                    throw new Error(data.message || 'Failed to load users');
                }
            } catch (error) {
                console.error('Users load error:', error);
                this.error = error.message;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Load available roles
         */
        async loadRoles() {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/roles`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.roles = data.roles || [];
                    }
                }
            } catch (error) {
                console.error('Roles load error:', error);
            }
        },

        /**
         * Load pending invitations
         */
        async loadInvitations() {
            try {
                const response = await fetch(`/api/orgs/${this.orgId}/users/invitations`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        this.invitations = data.invitations || [];
                    }
                }
            } catch (error) {
                console.error('Invitations load error:', error);
            }
        },

        /**
         * Send user invitation
         */
        async sendInvitation() {
            if (!this.inviteEmail || !this.inviteRoleId) {
                this.showNotification('Please fill in all required fields', 'error');
                return;
            }

            this.isLoading = true;

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/users/invite`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        email: this.inviteEmail,
                        role_id: this.inviteRoleId,
                        message: this.inviteMessage,
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Invitation sent successfully', 'success');
                    this.closeInviteModal();
                    await this.loadInvitations();
                } else {
                    throw new Error(data.message || 'Failed to send invitation');
                }
            } catch (error) {
                console.error('Invitation error:', error);
                this.showNotification(error.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Update user role
         */
        async updateUserRole(userId, newRoleId) {
            if (!confirm('Are you sure you want to change this user\'s role?')) {
                return;
            }

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/users/${userId}/role`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ role_id: newRoleId })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('User role updated successfully', 'success');
                    await this.loadUsers();
                } else {
                    throw new Error(data.message || 'Failed to update role');
                }
            } catch (error) {
                console.error('Role update error:', error);
                this.showNotification(error.message, 'error');
            }
        },

        /**
         * Toggle user status (activate/deactivate)
         */
        async toggleUserStatus(userId, currentStatus) {
            const newStatus = !currentStatus;
            const action = newStatus ? 'activate' : 'deactivate';

            if (!confirm(`Are you sure you want to ${action} this user?`)) {
                return;
            }

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/users/${userId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ is_active: newStatus })
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification(`User ${action}d successfully`, 'success');
                    await this.loadUsers();
                } else {
                    throw new Error(data.message || 'Failed to update status');
                }
            } catch (error) {
                console.error('Status update error:', error);
                this.showNotification(error.message, 'error');
            }
        },

        /**
         * Remove user from organization
         */
        async removeUser(userId) {
            if (!confirm('Are you sure you want to remove this user from the organization? This action cannot be undone.')) {
                return;
            }

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/users/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('User removed successfully', 'success');
                    await this.loadUsers();
                } else {
                    throw new Error(data.message || 'Failed to remove user');
                }
            } catch (error) {
                console.error('User removal error:', error);
                this.showNotification(error.message, 'error');
            }
        },

        /**
         * View user activity log
         */
        async viewActivity(userId) {
            this.selectedUser = this.users.find(u => u.user_id === userId);
            this.showActivityModal = true;
            this.isLoading = true;

            try {
                const params = new URLSearchParams({
                    limit: 100,
                });

                const response = await fetch(`/api/orgs/${this.orgId}/users/${userId}/activity?${params}`, {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.userActivity = data.activities || [];
                } else {
                    throw new Error(data.message || 'Failed to load activity');
                }
            } catch (error) {
                console.error('Activity load error:', error);
                this.userActivity = [];
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Cancel invitation
         */
        async cancelInvitation(invitationId) {
            if (!confirm('Are you sure you want to cancel this invitation?')) {
                return;
            }

            try {
                const response = await fetch(`/api/orgs/${this.orgId}/users/invitations/${invitationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.showNotification('Invitation cancelled', 'success');
                    await this.loadInvitations();
                } else {
                    throw new Error(data.message || 'Failed to cancel invitation');
                }
            } catch (error) {
                console.error('Cancel invitation error:', error);
                this.showNotification(error.message, 'error');
            }
        },

        /**
         * Apply filters and reload users
         */
        async applyFilters() {
            this.currentPage = 1;
            await this.loadUsers();
        },

        /**
         * Clear all filters
         */
        async clearFilters() {
            this.searchQuery = '';
            this.roleFilter = '';
            this.statusFilter = '';
            this.currentPage = 1;
            await this.loadUsers();
        },

        /**
         * Go to page
         */
        async goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
            await this.loadUsers();
        },

        /**
         * Open invite modal
         */
        openInviteModal() {
            this.inviteEmail = '';
            this.inviteRoleId = '';
            this.inviteMessage = '';
            this.showInviteModal = true;
        },

        /**
         * Close invite modal
         */
        closeInviteModal() {
            this.showInviteModal = false;
        },

        /**
         * Close activity modal
         */
        closeActivityModal() {
            this.showActivityModal = false;
            this.selectedUser = null;
            this.userActivity = [];
        },

        /**
         * Format date
         */
        formatDate(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
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
         * Show notification
         */
        showNotification(message, type = 'info') {
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { message, type }
            }));
        },

        /**
         * Render the user management interface
         */
        renderUserManagement() {
            return `
                <div class="space-y-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900">User Management</h2>
                        <button @click="openInviteModal()"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <svg class="inline-block w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Invite User
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <input type="text" x-model="searchQuery" @input="applyFilters()"
                                placeholder="Search by name or email..."
                                class="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">

                            <select x-model="roleFilter" @change="applyFilters()"
                                class="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Roles</option>
                                ${this.roles.map(role => `<option value="${role.role_id}">${role.role_name}</option>`).join('')}
                            </select>

                            <select x-model="statusFilter" @change="applyFilters()"
                                class="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>

                            <button @click="clearFilters()"
                                class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                                Clear Filters
                            </button>
                        </div>
                    </div>

                    <!-- Pending Invitations -->
                    ${this.invitations.length > 0 ? `
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <h3 class="text-lg font-semibold text-yellow-900 mb-3">Pending Invitations (${this.invitations.length})</h3>
                            <div class="space-y-2">
                                ${this.invitations.map(inv => `
                                    <div class="flex items-center justify-between bg-white rounded p-3">
                                        <div>
                                            <p class="font-medium text-gray-900">${inv.email}</p>
                                            <p class="text-sm text-gray-500">${inv.role_name} • Expires ${this.formatDate(inv.expires_at)}</p>
                                        </div>
                                        <button @click="cancelInvitation('${inv.invitation_id}')"
                                            class="text-red-600 hover:text-red-800 text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}

                    <!-- Users Table -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        ${this.isLoading && this.users.length === 0 ? `
                            <div class="flex items-center justify-center p-8">
                                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        ` : `
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    ${this.users.map(user => `
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        ${user.avatar_url ? `
                                                            <img class="h-10 w-10 rounded-full" src="${user.avatar_url}" alt="">
                                                        ` : `
                                                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                                <span class="text-gray-600 font-medium">${(user.display_name || user.name || user.email).charAt(0).toUpperCase()}</span>
                                                            </div>
                                                        `}
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">${user.display_name || user.name}</div>
                                                        <div class="text-sm text-gray-500">${user.email}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <select @change="updateUserRole('${user.user_id}', $event.target.value)"
                                                    class="text-sm border-gray-300 rounded-md">
                                                    ${this.roles.map(role => `
                                                        <option value="${role.role_id}" ${role.role_id === user.role_id ? 'selected' : ''}>
                                                            ${role.role_name}
                                                        </option>
                                                    `).join('')}
                                                </select>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${user.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                                    ${user.is_active ? 'Active' : 'Inactive'}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                ${this.formatDate(user.joined_at)}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <button @click="viewActivity('${user.user_id}')" class="text-blue-600 hover:text-blue-900 mr-3">Activity</button>
                                                <button @click="toggleUserStatus('${user.user_id}', ${user.is_active})" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                    ${user.is_active ? 'Deactivate' : 'Activate'}
                                                </button>
                                                <button @click="removeUser('${user.user_id}')" class="text-red-600 hover:text-red-900">Remove</button>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>

                            <!-- Pagination -->
                            ${this.totalPages > 1 ? `
                                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                                    <div class="flex-1 flex justify-between sm:hidden">
                                        <button @click="goToPage(currentPage - 1)" :disabled="currentPage === 1"
                                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Previous
                                        </button>
                                        <button @click="goToPage(currentPage + 1)" :disabled="currentPage === totalPages"
                                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Next
                                        </button>
                                    </div>
                                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                        <div>
                                            <p class="text-sm text-gray-700">
                                                Showing <span class="font-medium">${(this.currentPage - 1) * this.perPage + 1}</span> to
                                                <span class="font-medium">${Math.min(this.currentPage * this.perPage, this.totalUsers)}</span> of
                                                <span class="font-medium">${this.totalUsers}</span> users
                                            </p>
                                        </div>
                                        <div>
                                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                                ${Array.from({length: this.totalPages}, (_, i) => i + 1).map(page => `
                                                    <button @click="goToPage(${page})"
                                                        class="relative inline-flex items-center px-4 py-2 border text-sm font-medium ${page === this.currentPage ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}">
                                                        ${page}
                                                    </button>
                                                `).join('')}
                                            </nav>
                                        </div>
                                    </div>
                                </div>
                            ` : ''}
                        `}
                    </div>
                </div>

                <!-- Invite Modal -->
                ${this.renderInviteModal()}

                <!-- Activity Modal -->
                ${this.renderActivityModal()}
            `;
        },

        /**
         * Render invite modal
         */
        renderInviteModal() {
            if (!this.showInviteModal) return '';

            return `
                <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeInviteModal()">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Invite User</h3>
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                        <input type="email" x-model="inviteEmail" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="user@example.com">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                                        <select x-model="inviteRoleId" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">Select a role...</option>
                                            ${this.roles.map(role => `<option value="${role.role_id}">${role.role_name}</option>`).join('')}
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Welcome Message (Optional)</label>
                                        <textarea x-model="inviteMessage" rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Add a personal message to the invitation..."></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button @click="sendInvitation()" :disabled="isLoading"
                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                    ${this.isLoading ? 'Sending...' : 'Send Invitation'}
                                </button>
                                <button @click="closeInviteModal()"
                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Render activity modal
         */
        renderActivityModal() {
            if (!this.showActivityModal) return '';

            return `
                <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="closeActivityModal()">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"></div>
                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Activity Log - ${this.selectedUser ? (this.selectedUser.display_name || this.selectedUser.name) : ''}
                                </h3>
                                <div class="max-h-96 overflow-y-auto">
                                    ${this.userActivity.length > 0 ? `
                                        <div class="space-y-3">
                                            ${this.userActivity.map(activity => `
                                                <div class="border-l-4 border-blue-500 pl-4 py-2">
                                                    <p class="text-sm font-medium text-gray-900">${activity.action_type}</p>
                                                    <p class="text-xs text-gray-500">${this.formatDate(activity.created_at)}</p>
                                                    ${activity.metadata ? `<pre class="text-xs text-gray-600 mt-1">${JSON.stringify(JSON.parse(activity.metadata), null, 2)}</pre>` : ''}
                                                </div>
                                            `).join('')}
                                        </div>
                                    ` : `
                                        <p class="text-gray-500 text-center py-8">No activity found</p>
                                    `}
                                </div>
                            </div>
                            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                <button @click="closeActivityModal()"
                                    class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    };
}

// Export for use in HTML
window.userManagement = userManagement;
