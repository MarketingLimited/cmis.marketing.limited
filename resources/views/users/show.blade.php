@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div x-data="userShowPage()" x-init="loadUser()">
    <div class="mb-6">
        <a href="{{ route('users.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            <i class="fas fa-arrow-left mr-2"></i>Back to Users
        </a>
        <h1 class="text-3xl font-bold text-gray-900">User Details</h1>
    </div>

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-lg shadow p-8 text-center">
        <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
        <p class="text-gray-600 mt-2">Loading user details...</p>
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
                            <dt class="text-sm font-medium text-gray-500">Role</dt>
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
                            <dt class="text-sm font-medium text-gray-500">Joined</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(membership?.joined_at)"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Accessed</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="formatDate(membership?.last_accessed)"></dd>
                        </div>
                        <div x-show="membership?.invited_by">
                            <dt class="text-sm font-medium text-gray-500">Invited By</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="membership?.invited_by"></dd>
                        </div>
                    </dl>
                </div>

                <!-- Actions -->
                <div class="mt-6 border-t pt-6 space-y-2">
                    @can('assignRole', App\Models\User::class)
                    <button @click="showRoleModal = true" class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-user-tag mr-2"></i>Change Role
                    </button>
                    @endcan
                    @can('delete', App\Models\User::class)
                    <button @click="confirmDeactivate()" class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-ban mr-2"></i>Deactivate User
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
                    <h3 class="text-lg font-medium text-gray-900">User Information</h3>
                </div>
                <div class="px-6 py-4">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">User ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 font-mono" x-text="user?.user_id"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Display Name</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.display_name"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.email"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.name || 'N/A'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Provider</dt>
                            <dd class="mt-1 text-sm text-gray-900" x-text="user?.provider || 'Email'"></dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
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

            <!-- Activity Log (Placeholder) -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Activity</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-history text-3xl mb-2"></i>
                        <p>Activity tracking coming soon</p>
                    </div>
                </div>
            </div>

            <!-- Permissions (Placeholder) -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Permissions</h3>
                </div>
                <div class="px-6 py-4">
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-shield-alt text-3xl mb-2"></i>
                        <p>Permission management coming soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Change Role Modal -->
    <div x-show="showRoleModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Change User Role</h3>
                <button @click="showRoleModal = false" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="updateRole()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Role</label>
                    <select x-model="roleForm.role_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a role</option>
                        <template x-for="role in roles" :key="role.role_id">
                            <option :value="role.role_id" x-text="role.role_name"></option>
                        </template>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showRoleModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Role
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
                    alert('User role updated successfully!');
                } else {
                    const error = await response.json();
                    alert('Failed to update role: ' + (error.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to update role:', error);
                alert('Failed to update role');
            }
        },

        async confirmDeactivate() {
            if (confirm('Are you sure you want to deactivate this user?')) {
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
                        alert('User deactivated successfully!');
                        window.location.href = '/users';
                    } else {
                        const error = await response.json();
                        alert('Failed to deactivate user: ' + (error.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('Failed to deactivate user:', error);
                    alert('Failed to deactivate user');
                }
            }
        },

        formatDate(date) {
            if (!date) return 'N/A';
            return new Date(date).toLocaleString();
        },

        init() {
            this.loadRoles();
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
