@extends('layouts.admin')

@section('title', __('users.title'))

@section('content')
<div x-data="usersPage()" x-init="loadUsers()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">{{ __('users.title') }}</h1>
            <p class="text-gray-600 mt-1">{{ __('users.manage_users_desc') }}</p>
        </div>
        @can('invite', App\Models\User::class)
        <button @click="showInviteModal = true" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-user-plus me-2"></i>
            {{ __('users.invite_user') }}
        </button>
        @endcan
    </div>

    <!-- Search and Filter -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex gap-4">
            <div class="flex-1">
                <input type="text" x-model="search" @input="loadUsers()" placeholder="{{ __('users.search_users') }}"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <button @click="loadUsers()" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg">
                <i class="fas fa-search"></i>
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('users.user') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('users.email') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('users.role') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('users.status') }}</th>
                    <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('users.joined') }}</th>
                    <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <template x-for="user in users" :key="user.id || user.user_id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 flex-shrink-0 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 font-medium" x-text="user.display_name?.charAt(0) || 'U'"></span>
                                </div>
                                <div class="ms-4">
                                    <div class="text-sm font-medium text-gray-900" x-text="user.display_name"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.email"></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-purple-100 text-purple-800': user.role_code === 'owner',
                                    'bg-blue-100 text-blue-800': user.role_code === 'admin',
                                    'bg-green-100 text-green-800': user.role_code === 'editor',
                                    'bg-gray-100 text-gray-800': user.role_code === 'viewer'
                                }"
                                x-text="user.role_name || user.role_code"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                :class="{
                                    'bg-green-100 text-green-800': user.status === 'active',
                                    'bg-yellow-100 text-yellow-800': user.status === 'pending',
                                    'bg-red-100 text-red-800': user.status === 'inactive'
                                }"
                                x-text="user.status"></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(user.joined_at)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                            <button @click="viewUser(user.id || user.user_id)" class="text-blue-600 hover:text-blue-900 me-3">
                                <i class="fas fa-eye"></i>
                            </button>
                            @can('assignRole', App\Models\User::class)
                            <button @click="editRole(user)" class="text-indigo-600 hover:text-indigo-900 me-3">
                                <i class="fas fa-user-tag"></i>
                            </button>
                            @endcan
                            @can('delete', App\Models\User::class)
                            <button @click="confirmDeactivate(user)" class="text-red-600 hover:text-red-900">
                                <i class="fas fa-ban"></i>
                            </button>
                            @endcan
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            <p class="text-gray-600 mt-2">{{ __('users.loading_users') }}</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && users.length === 0" class="p-8 text-center">
            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
            <p class="text-gray-600">{{ __('users.no_users_found') }}</p>
        </div>
    </div>

    <!-- Pagination -->
    <div x-show="pagination.total > pagination.per_page" class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-700">
            {{ __('users.showing') }} <span x-text="pagination.from"></span> {{ __('users.to') }} <span x-text="pagination.to"></span> {{ __('users.of') }} <span x-text="pagination.total"></span> {{ __('users.users_count') }}
        </div>
        <div class="flex gap-2">
            <button @click="loadUsers(pagination.current_page - 1)" :disabled="pagination.current_page === 1"
                class="px-3 py-1 border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                {{ __('common.previous') }}
            </button>
            <button @click="loadUsers(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page"
                class="px-3 py-1 border border-gray-300 rounded-md disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                {{ __('common.next') }}
            </button>
        </div>
    </div>

    <!-- Invite User Modal -->
    <div x-show="showInviteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">{{ __('users.invite_user_title') }}</h3>
                <button @click="showInviteModal = false" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form @submit.prevent="inviteUser()">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('users.email') }}</label>
                    <input type="email" x-model="inviteForm.email" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('users.role') }}</label>
                    <select x-model="inviteForm.role_id" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ __('users.select_role') }}</option>
                        <template x-for="role in roles" :key="role.role_id">
                            <option :value="role.role_id" x-text="role.role_name"></option>
                        </template>
                    </select>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="showInviteModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        {{ __('users.send_invitation') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function usersPage() {
    return {
        users: [],
        roles: [],
        loading: false,
        search: '',
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0
        },
        showInviteModal: false,
        inviteForm: {
            email: '',
            role_id: ''
        },

        async loadUsers(page = 1) {
            this.loading = true;
            try {
                const orgId = '{{ session("current_org_id") }}';
                const params = new URLSearchParams({
                    per_page: this.pagination.per_page,
                    search: this.search,
                    page: page
                });

                const response = await fetch(`/api/orgs/${orgId}/users?${params}`, {
                    headers: {
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.users = data.data;
                    this.pagination = {
                        current_page: data.current_page,
                        last_page: data.last_page,
                        per_page: data.per_page,
                        total: data.total,
                        from: data.from,
                        to: data.to
                    };
                }
            } catch (error) {
                console.error('Failed to load users:', error);
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

        viewUser(userId) {
            window.location.href = `/users/${userId}`;
        },

        async inviteUser() {
            try {
                const orgId = '{{ session("current_org_id") }}';
                const response = await fetch(`/api/orgs/${orgId}/users/invite`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.inviteForm)
                });

                if (response.ok) {
                    this.showInviteModal = false;
                    this.inviteForm = { email: '', role_id: '' };
                    this.loadUsers();
                    alert('{{ __("users.user_invited_success") }}');
                } else {
                    const error = await response.json();
                    alert('{{ __("users.invite_failed") }}: ' + (error.message || '{{ __("users.unknown_error") }}'));
                }
            } catch (error) {
                console.error('Failed to invite user:', error);
                alert('{{ __("users.invite_failed") }}');
            }
        },

        formatDate(date) {
            if (!date) return '{{ __("users.n_a") }}';
            return new Date(date).toLocaleDateString();
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
