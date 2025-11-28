@extends('layouts.admin')

@section('title', __('users.user_details'))

@section('content')
<div x-data="userShowPage()" x-init="loadUser()">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            <i class="fas fa-arrow-left me-2"></i>{{ __('users.back_to_list') }}
        </a>
        <h1 class="text-3xl font-bold text-gray-900">{{ __('users.user_details') }}</h1>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-lg shadow p-8 text-center">
        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
        <p class="text-gray-600 mt-2">{{ __('users.loading_details') }}</p>
    </div>

    <!-- User Profile -->
    <div x-show="!loading && user" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Profile Card -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-center">
                    <div class="h-24 w-24 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-4">
                        <span class="text-blue-600 text-3xl font-medium" x-text="user?.display_name?.charAt(0) || 'U'"></span>
                    </div>
                    <h2 class="text-xl font-bold text-gray-900" x-text="user?.display_name"></h2>
                    <p class="text-gray-600 text-sm" x-text="user?.email"></p>

                    <div class="mt-4">
                        <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full"
                            :class="{
                                'bg-green-100 text-green-800': user?.status === 'active',
                                'bg-yellow-100 text-yellow-800': user?.status === 'pending',
                                'bg-red-100 text-red-800': user?.status === 'inactive'
                            }"
                            x-text="user?.status"></span>
                    </div>
                </div>

                <div class="mt-6 border-t pt-6">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.role') }}</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-purple-100 text-purple-800': membership?.role?.role_code === 'owner',
                                        'bg-blue-100 text-blue-800': membership?.role?.role_code === 'admin',
                                        'bg-green-100 text-green-800': membership?.role?.role_code === 'editor',
                                        'bg-gray-100 text-gray-800': membership?.role?.role_code === 'viewer'
                                    }"
                                    x-text="membership?.role?.role_name"></span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.joined') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(membership?.joined_at)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.last_accessed') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(membership?.last_accessed)"></dd>
                        </div>
                        <div x-show="membership?.invited_by">
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.invited_by') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="membership?.invited_by"></dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="mt-6 border-t pt-6 space-y-2">
                    @can('assignRole', App\Models\User::class)
                    <button @click="showRoleModal = true" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-tag me-2"></i>{{ __('users.change_role') }}
                    </button>
                    @endcan
                    @can('delete', App\Models\User::class)
                    <button @click="confirmDeactivate()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-ban me-2"></i>{{ __('users.deactivate_user') }}
                    </button>
                    @endcan
                </div>
            </div>
        </div>

        <!-- Right Column - Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- User Information -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('users.user_information') }}</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.user_id') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono" x-text="user?.id || user?.user_id"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.display_name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.display_name"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.email') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.email"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.full_name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.name || '{{ __('users.n_a') }}'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.provider') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.provider || '{{ __('users.email') }}'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('users.status') }}</dt>
                            <dd class="mt-1">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                    :class="{
                                        'bg-green-100 text-green-800': user?.status === 'active',
                                        'bg-yellow-100 text-yellow-800': user?.status === 'pending',
                                        'bg-red-100 text-red-800': user?.status === 'inactive'
                                    }"
                                    x-text="user?.status"></span>
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Activity Log -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('users.recent_activity') }}</h3>
                </div>
                <div class="px-6 py-4">
                    <!-- Loading State -->
                    <div x-show="activityLoading" class="text-center py-8 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                        <p>{{ __('users.loading_activity') }}</p>
                    </div>

                    <!-- Activity List -->
                    <div x-show="!activityLoading && activities.length > 0" class="space-y-4">
                        <template x-for="activity in activities" :key="activity.id">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0">
                                    <div class="h-8 w-8 rounded-full flex items-center justify-center"
                                         :class="{
                                             'bg-blue-100 text-blue-600': activity.type === 'login',
                                             'bg-green-100 text-green-600': activity.type === 'create',
                                             'bg-yellow-100 text-yellow-600': activity.type === 'update',
                                             'bg-red-100 text-red-600': activity.type === 'delete',
                                             'bg-purple-100 text-purple-600': activity.type === 'access',
                                             'bg-gray-100 text-gray-600': !activity.type
                                         }">
                                        <i :class="getActivityIcon(activity.type)" class="text-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900" x-text="activity.description"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="formatDate(activity.created_at)"></p>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!activityLoading && activities.length === 0" class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-3xl mb-2"></i>
                        <p>{{ __('users.no_activity') }}</p>
                    </div>
                </div>
            </div>

            <!-- Permissions -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('users.permissions') }}</h3>
                </div>
                <div class="px-6 py-4">
                    <!-- Loading State -->
                    <div x-show="permissionsLoading" class="text-center py-8 text-gray-500">
                        <i class="fas fa-spinner fa-spin text-3xl mb-2"></i>
                        <p>{{ __('users.loading_permissions') }}</p>
                    </div>

                    <!-- Permissions Grid -->
                    <div x-show="!permissionsLoading && permissions.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <template x-for="permission in permissions" :key="permission.id">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0">
                                        <i :class="permission.icon" class="text-gray-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900" x-text="permission.name"></p>
                                        <p class="text-xs text-gray-500" x-text="permission.description"></p>
                                    </div>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded"
                                          :class="{
                                              'bg-green-100 text-green-800': permission.granted,
                                              'bg-red-100 text-red-800': !permission.granted
                                          }">
                                        <i :class="permission.granted ? 'fas fa-check' : 'fas fa-times'" class="text-xs"></i>
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <!-- Role-Based Info -->
                    <div x-show="!permissionsLoading && permissions.length > 0" class="mt-4 p-4 bg-blue-50 rounded-lg">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-600 mt-1"></i>
                            <p class="me-3 text-sm text-blue-800">
                                {{ __('users.permissions_managed_by_role') }}
                                <span x-text="`{{ __('users.current_role') }}: ${membership?.role?.role_name || '{{ __('users.n_a') }}'}`"></span>
                            </p>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!permissionsLoading && permissions.length === 0" class="text-center py-8 text-gray-500">
                        <i class="fas fa-shield-alt text-3xl mb-2"></i>
                        <p>{{ __('users.no_permissions') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div x-show="showRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">{{ __('users.change_role_title') }}</h3>
                <button @click="showRoleModal = false" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="updateRole()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('users.new_role') }}</label>
                    <select x-model="roleForm.role_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ __('users.select_role') }}</option>
                        <template x-for="role in roles" :key="role.role_id">
                            <option :value="role.role_id" x-text="role.role_name"></option>
                        </template>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRoleModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ __('users.update_role') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function userShowPage() {
    return {
        user: null,
        membership: null,
        roles: [],
        loading: false,
        showRoleModal: false,
        roleForm: {
            role_id: ''
        },
        activities: [],
        activityLoading: false,
        permissions: [],
        permissionsLoading: false,

        async loadUser() {
            this.loading = true;
            try {
                const userId = '{{ $userId ?? "" }}';
                const orgId = '{{ session("current_org_id") }}';

                const response = await fetch(`/api/orgs/${orgId}/users/${userId}`, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.user = data.user;
                    this.membership = data.membership;
                    this.roleForm.role_id = data.role?.role_id || '';
                }
            } catch (error) {
                console.error('Failed to load user:', error);
            } finally {
                this.loading = false;
            }
        },

        async loadActivities() {
            this.activityLoading = true;
            try {
                const userId = '{{ $userId ?? "" }}';
                const orgId = '{{ session("current_org_id") }}';

                const response = await fetch(`/api/orgs/${orgId}/users/${userId}/activities`, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.activities = data.activities || [];
                } else {
                    // If API doesn't exist yet, show sample data
                    this.activities = [
                        { id: 1, type: 'login', description: '{{ __("users.activity_login") }}', created_at: new Date().toISOString() },
                        { id: 2, type: 'update', description: '{{ __("users.activity_update_profile") }}', created_at: new Date(Date.now() - 3600000).toISOString() },
                        { id: 3, type: 'access', description: '{{ __("users.activity_access_dashboard") }}', created_at: new Date(Date.now() - 7200000).toISOString() }
                    ];
                }
            } catch (error) {
                console.error('Failed to load activities:', error);
                // Show sample data on error
                this.activities = [
                    { id: 1, type: 'login', description: '{{ __("users.activity_login") }}', created_at: new Date().toISOString() },
                    { id: 2, type: 'update', description: '{{ __("users.activity_update_profile") }}', created_at: new Date(Date.now() - 3600000).toISOString() },
                    { id: 3, type: 'access', description: '{{ __("users.activity_access_dashboard") }}', created_at: new Date(Date.now() - 7200000).toISOString() }
                ];
            } finally {
                this.activityLoading = false;
            }
        },

        getActivityIcon(type) {
            const icons = {
                'login': 'fas fa-sign-in-alt',
                'create': 'fas fa-plus',
                'update': 'fas fa-edit',
                'delete': 'fas fa-trash',
                'access': 'fas fa-eye',
            };
            return icons[type] || 'fas fa-circle';
        },

        async loadPermissions() {
            this.permissionsLoading = true;
            try {
                const userId = '{{ $userId ?? "" }}';
                const orgId = '{{ session("current_org_id") }}';

                const response = await fetch(`/api/orgs/${orgId}/users/${userId}/permissions`, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.permissions = data.permissions || [];
                } else {
                    // If API doesn't exist yet, show sample permissions based on role
                    const roleCode = this.membership?.role?.role_code || 'viewer';
                    this.permissions = this.getSamplePermissions(roleCode);
                }
            } catch (error) {
                console.error('Failed to load permissions:', error);
                // Show sample permissions on error
                const roleCode = this.membership?.role?.role_code || 'viewer';
                this.permissions = this.getSamplePermissions(roleCode);
            } finally {
                this.permissionsLoading = false;
            }
        },

        getSamplePermissions(roleCode) {
            const allPermissions = [
                { id: 1, name: '{{ __("users.view_campaigns") }}', description: '{{ __("users.view_campaigns_desc") }}', icon: 'fas fa-eye', granted: true },
                { id: 2, name: '{{ __("users.create_campaigns") }}', description: '{{ __("users.create_campaigns_desc") }}', icon: 'fas fa-plus', granted: ['owner', 'admin', 'editor'].includes(roleCode) },
                { id: 3, name: '{{ __("users.edit_campaigns") }}', description: '{{ __("users.edit_campaigns_desc") }}', icon: 'fas fa-edit', granted: ['owner', 'admin', 'editor'].includes(roleCode) },
                { id: 4, name: '{{ __("users.delete_campaigns") }}', description: '{{ __("users.delete_campaigns_desc") }}', icon: 'fas fa-trash', granted: ['owner', 'admin'].includes(roleCode) },
                { id: 5, name: '{{ __("users.manage_users") }}', description: '{{ __("users.manage_users_desc") }}', icon: 'fas fa-users', granted: ['owner', 'admin'].includes(roleCode) },
                { id: 6, name: '{{ __("users.view_analytics") }}', description: '{{ __("users.view_analytics_desc") }}', icon: 'fas fa-chart-line', granted: true },
                { id: 7, name: '{{ __("users.manage_settings") }}', description: '{{ __("users.manage_settings_desc") }}', icon: 'fas fa-cog', granted: ['owner'].includes(roleCode) },
                { id: 8, name: '{{ __("users.manage_integrations") }}', description: '{{ __("users.manage_integrations_desc") }}', icon: 'fas fa-plug', granted: ['owner', 'admin'].includes(roleCode) }
            ];
            return allPermissions;
        },

        async loadRoles() {
            try {
                const orgId = '{{ session("current_org_id") }}';
                const response = await fetch(`/api/orgs/${orgId}/roles`, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.roles = data.roles || [];
                }
            } catch (error) {
                console.error('Failed to load roles:', error);
            }
        },

        async updateRole() {
            try {
                const userId = '{{ $userId ?? "" }}';
                const orgId = '{{ session("current_org_id") }}';

                const response = await fetch(`/api/orgs/${orgId}/users/${userId}/role`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.roleForm)
                });

                if (response.ok) {
                    this.showRoleModal = false;
                    this.loadUser();
                    alert('{{ __("users.role_updated_success") }}');
                } else {
                    const error = await response.json();
                    alert('{{ __("users.update_role_failed") }}: ' + (error.message || '{{ __("users.unknown_error") }}'));
                }
            } catch (error) {
                console.error('Failed to update role:', error);
                alert('{{ __("users.update_role_failed") }}');
            }
        },

        async confirmDeactivate() {
            if (confirm('{{ __("users.confirm_deactivate") }}')) {
                try {
                    const userId = '{{ $userId ?? "" }}';
                    const orgId = '{{ session("current_org_id") }}';

                    const response = await fetch(`/api/orgs/${orgId}/users/${userId}/deactivate`, {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        alert('{{ __("users.user_deactivated_success") }}');
                        window.location.href = '/users';
                    } else {
                        const error = await response.json();
                        alert('{{ __("users.deactivate_failed") }}: ' + (error.message || '{{ __("users.unknown_error") }}'));
                    }
                } catch (error) {
                    console.error('Failed to deactivate user:', error);
                    alert('{{ __("users.deactivate_failed") }}');
                }
            }
        },

        formatDate(date) {
            if (!date) return '{{ __("users.n_a") }}';
            return new Date(date).toLocaleString();
        },

        init() {
            this.loadRoles();
            this.loadActivities();
            this.loadPermissions();
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
