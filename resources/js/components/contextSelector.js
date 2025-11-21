/**
 * Context Selector Component (Phase 2 - Option 2: Context System UI)
 *
 * Alpine.js component for managing organization context switching
 *
 * Usage:
 * <div x-data="contextSelector()" x-init="init()">
 *     <div x-html="renderSelector()"></div>
 * </div>
 */

export default function contextSelector() {
    return {
        // State
        currentContext: null,
        organizations: [],
        isLoading: false,
        isDropdownOpen: false,
        error: null,

        /**
         * Initialize the component
         */
        async init() {
            await this.loadContext();
            await this.loadOrganizations();

            // Listen for custom context events
            window.addEventListener('context-changed', (e) => {
                this.currentContext = e.detail.context;
            });
        },

        /**
         * Load current context from API
         */
        async loadContext() {
            this.isLoading = true;
            this.error = null;

            try {
                const response = await fetch('/api/context', {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load context');
                }

                const data = await response.json();

                if (data.success) {
                    this.currentContext = data.context;
                } else {
                    throw new Error(data.message || 'Failed to load context');
                }
            } catch (error) {
                console.error('Context load error:', error);
                this.error = error.message;
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Load available organizations from API
         */
        async loadOrganizations() {
            try {
                const response = await fetch('/api/context/organizations', {
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Accept': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load organizations');
                }

                const data = await response.json();

                if (data.success) {
                    this.organizations = data.organizations;
                }
            } catch (error) {
                console.error('Organizations load error:', error);
            }
        },

        /**
         * Switch to a different organization
         */
        async switchOrganization(orgId) {
            if (!orgId || orgId === this.currentContext?.active_org?.org_id) {
                return;
            }

            this.isLoading = true;
            this.error = null;
            this.isDropdownOpen = false;

            try {
                const response = await fetch('/api/context/switch', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${this.getAuthToken()}`,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ org_id: orgId })
                });

                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to switch organization');
                }

                const data = await response.json();

                if (data.success) {
                    // Update context
                    await this.loadContext();

                    // Dispatch event for other components
                    window.dispatchEvent(new CustomEvent('context-switched', {
                        detail: { context: data.context }
                    }));

                    // Show success message
                    this.showNotification('تم تغيير المنظمة بنجاح', 'success');

                    // Reload page to refresh data with new context
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error(data.message || 'Failed to switch organization');
                }
            } catch (error) {
                console.error('Switch organization error:', error);
                this.error = error.message;
                this.showNotification(error.message, 'error');
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * Toggle dropdown visibility
         */
        toggleDropdown() {
            this.isDropdownOpen = !this.isDropdownOpen;
        },

        /**
         * Close dropdown when clicking outside
         */
        closeDropdown() {
            this.isDropdownOpen = false;
        },

        /**
         * Get authentication token from localStorage or cookie
         */
        getAuthToken() {
            // Try localStorage first (SPA)
            let token = localStorage.getItem('auth_token');

            // Fallback to cookie (traditional)
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
         * Show notification message
         */
        showNotification(message, type = 'info') {
            // Dispatch notification event for global notification system
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: { message, type }
            }));
        },

        /**
         * Render the selector component
         */
        renderSelector() {
            if (this.isLoading && !this.currentContext) {
                return `
                    <div class="flex items-center justify-center p-4">
                        <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                `;
            }

            if (this.error) {
                return `
                    <div class="bg-red-50 border border-red-200 rounded-md p-3 text-red-800 text-sm">
                        ${this.error}
                    </div>
                `;
            }

            if (!this.currentContext) {
                return '';
            }

            const activeOrg = this.currentContext.active_org;
            const orgCount = this.organizations.length;

            return `
                <div class="relative" @click.away="closeDropdown()">
                    <button
                        type="button"
                        class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="toggleDropdown()"
                        :disabled="isLoading"
                    >
                        <div class="flex items-center space-x-2 rtl:space-x-reverse">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <span class="truncate">${activeOrg.name}</span>
                        </div>
                        <svg class="w-4 h-4 ml-2 text-gray-500 transition-transform" :class="{ 'transform rotate-180': isDropdownOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        x-show="isDropdownOpen"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute z-50 mt-2 w-full bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 max-h-64 overflow-y-auto"
                    >
                        <div class="py-1">
                            ${this.organizations.map(org => `
                                <button
                                    type="button"
                                    class="flex items-center justify-between w-full px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 ${org.is_active ? 'bg-blue-50 text-blue-700' : ''}"
                                    @click="switchOrganization('${org.org_id}')"
                                    ${org.is_active ? 'disabled' : ''}
                                >
                                    <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                        <span class="truncate">${org.name}</span>
                                        ${org.role_name ? `<span class="text-xs text-gray-500">(${org.role_name})</span>` : ''}
                                    </div>
                                    ${org.is_active ? '<svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>' : ''}
                                </button>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        },

        /**
         * Get current organization name for display
         */
        get currentOrgName() {
            return this.currentContext?.active_org?.name || 'Loading...';
        },

        /**
         * Check if user has multiple organizations
         */
        get hasMultipleOrgs() {
            return this.organizations.length > 1;
        }
    };
}

// Export for use in HTML
window.contextSelector = contextSelector;
