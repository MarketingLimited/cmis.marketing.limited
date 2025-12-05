@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.users.title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.users.title') }}</span>
@endsection

@section('content')
<div x-data="usersManager()" x-init="loadUsers()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.users.title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.users.subtitle') }}</p>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.300ms="loadUsers()"
                           placeholder="{{ __('super_admin.users.search_placeholder') }}"
                           class="w-full {{ $isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <i class="fas fa-search absolute {{ $isRtl ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.status"
                        @change="loadUsers()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">{{ __('super_admin.users.all_statuses') }}</option>
                    <option value="active">{{ __('super_admin.users.status_active') }}</option>
                    <option value="suspended">{{ __('super_admin.users.status_suspended') }}</option>
                    <option value="blocked">{{ __('super_admin.users.status_blocked') }}</option>
                </select>
            </div>

            <!-- Role Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.role"
                        @change="loadUsers()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">{{ __('super_admin.users.all_roles') }}</option>
                    <option value="super_admin">{{ __('super_admin.users.role_super_admin') }}</option>
                    <option value="owner">{{ __('super_admin.users.role_owner') }}</option>
                    <option value="admin">{{ __('super_admin.users.role_admin') }}</option>
                    <option value="member">{{ __('super_admin.users.role_member') }}</option>
                </select>
            </div>

            <!-- Organization Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.org_id"
                        @change="loadUsers()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">{{ __('super_admin.users.all_organizations') }}</option>
                    <template x-for="org in organizations" :key="org.org_id">
                        <option :value="org.org_id" x-text="org.name"></option>
                    </template>
                </select>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div x-show="selectedUsers.length > 0" x-transition class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    <span x-text="selectedUsers.length"></span> {{ __('super_admin.users.selected') }}
                </span>
                <button @click="bulkAction('suspend')"
                        class="px-3 py-1.5 text-sm bg-yellow-100 text-yellow-800 hover:bg-yellow-200 rounded-lg transition">
                    <i class="fas fa-pause {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                    {{ __('super_admin.actions.suspend') }}
                </button>
                <button @click="bulkAction('restore')"
                        class="px-3 py-1.5 text-sm bg-green-100 text-green-800 hover:bg-green-200 rounded-lg transition">
                    <i class="fas fa-play {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                    {{ __('super_admin.actions.restore') }}
                </button>
                <button @click="bulkAction('block')"
                        class="px-3 py-1.5 text-sm bg-red-100 text-red-800 hover:bg-red-200 rounded-lg transition">
                    <i class="fas fa-ban {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                    {{ __('super_admin.actions.block') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('common.loading') }}</p>
        </div>

        <!-- Table -->
        <div x-show="!loading" class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }}">
                            <input type="checkbox"
                                   @change="toggleSelectAll($event.target.checked)"
                                   :checked="selectedUsers.length === users.length && users.length > 0"
                                   class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.users.user') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.users.organization') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.users.role') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.users.status') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.users.last_login') }}
                        </th>
                        <th class="px-4 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase">
                            {{ __('super_admin.users.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="user in users" :key="user.user_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                            <td class="px-4 py-3">
                                <input type="checkbox"
                                       :value="user.user_id"
                                       x-model="selectedUsers"
                                       class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-500 to-red-700 flex items-center justify-center text-white font-semibold text-sm"
                                         x-text="user.name ? user.name.substring(0, 2).toUpperCase() : 'U'"></div>
                                    <div>
                                        <a :href="'{{ route('super-admin.users.show', '') }}/' + user.user_id"
                                           class="font-medium text-gray-900 dark:text-white hover:text-red-600 dark:hover:text-red-400"
                                           x-text="user.name"></a>
                                        <p class="text-sm text-gray-500" x-text="user.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <template x-if="user.organizations && user.organizations.length > 0">
                                    <div class="flex flex-wrap gap-1">
                                        <template x-for="org in user.organizations.slice(0, 2)" :key="org.org_id">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300"
                                                  x-text="org.name"></span>
                                        </template>
                                        <template x-if="user.organizations.length > 2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-500"
                                                  x-text="'+' + (user.organizations.length - 2)"></span>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="!user.organizations || user.organizations.length === 0">
                                    <span class="text-gray-400 text-sm">{{ __('super_admin.users.no_organization') }}</span>
                                </template>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400': user.is_super_admin,
                                          'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': !user.is_super_admin && user.primary_role === 'owner',
                                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': !user.is_super_admin && user.primary_role === 'admin',
                                          'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': !user.is_super_admin && user.primary_role === 'member'
                                      }">
                                    <template x-if="user.is_super_admin">
                                        <span>{{ __('super_admin.users.role_super_admin') }}</span>
                                    </template>
                                    <template x-if="!user.is_super_admin">
                                        <span x-text="user.primary_role || '{{ __('super_admin.users.role_member') }}'"></span>
                                    </template>
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': !user.is_suspended && !user.is_blocked,
                                          'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': user.is_suspended && !user.is_blocked,
                                          'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': user.is_blocked
                                      }">
                                    <template x-if="user.is_blocked">
                                        <span>{{ __('super_admin.users.status_blocked') }}</span>
                                    </template>
                                    <template x-if="user.is_suspended && !user.is_blocked">
                                        <span>{{ __('super_admin.users.status_suspended') }}</span>
                                    </template>
                                    <template x-if="!user.is_suspended && !user.is_blocked">
                                        <span>{{ __('super_admin.users.status_active') }}</span>
                                    </template>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="user.last_login_at ? new Date(user.last_login_at).toLocaleDateString() : '{{ __('super_admin.users.never') }}'"></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a :href="'{{ route('super-admin.users.show', '') }}/' + user.user_id"
                                       class="p-2 text-gray-600 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                       title="{{ __('super_admin.actions.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <template x-if="!user.is_super_admin">
                                        <button @click="impersonateUser(user)"
                                                class="p-2 text-gray-600 hover:text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition"
                                                title="{{ __('super_admin.actions.impersonate') }}">
                                            <i class="fas fa-user-secret"></i>
                                        </button>
                                    </template>
                                    <template x-if="!user.is_blocked && !user.is_suspended && !user.is_super_admin">
                                        <button @click="openActionModal(user, 'suspend')"
                                                class="p-2 text-gray-600 hover:text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 rounded-lg transition"
                                                title="{{ __('super_admin.actions.suspend') }}">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                    </template>
                                    <template x-if="user.is_suspended && !user.is_blocked && !user.is_super_admin">
                                        <button @click="restoreUser(user)"
                                                class="p-2 text-gray-600 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition"
                                                title="{{ __('super_admin.actions.restore') }}">
                                            <i class="fas fa-play"></i>
                                        </button>
                                    </template>
                                    <template x-if="!user.is_blocked && !user.is_super_admin">
                                        <button @click="openActionModal(user, 'block')"
                                                class="p-2 text-gray-600 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                                title="{{ __('super_admin.actions.block') }}">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </template>
                                    <template x-if="user.is_blocked && !user.is_super_admin">
                                        <button @click="restoreUser(user)"
                                                class="p-2 text-gray-600 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition"
                                                title="{{ __('super_admin.actions.unblock') }}">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </template>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && users.length === 0" class="p-8 text-center">
            <i class="fas fa-users text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
            <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.users.no_users_found') }}</p>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && pagination.total > pagination.per_page" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('super_admin.pagination.showing') }}
                    <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>
                    {{ __('super_admin.pagination.to') }}
                    <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                    {{ __('super_admin.pagination.of') }}
                    <span x-text="pagination.total"></span>
                    {{ __('super_admin.pagination.results') }}
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <i class="fas {{ $isRtl ? 'fa-chevron-right' : 'fa-chevron-left' }}"></i>
                    </button>
                    <template x-for="page in pagination.last_page" :key="page">
                        <button @click="goToPage(page)"
                                x-show="Math.abs(page - pagination.current_page) < 3 || page === 1 || page === pagination.last_page"
                                :class="page === pagination.current_page ? 'bg-red-600 text-white' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                                class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg transition"
                                x-text="page"></button>
                    </template>
                    <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <i class="fas {{ $isRtl ? 'fa-chevron-left' : 'fa-chevron-right' }}"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div x-show="actionModal.show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50"
         x-cloak>
        <div @click.away="actionModal.show = false"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center"
                         :class="actionModal.type === 'block' ? 'bg-red-100 dark:bg-red-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30'">
                        <i class="fas text-xl"
                           :class="actionModal.type === 'block' ? 'fa-ban text-red-600' : 'fa-pause text-yellow-600'"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white"
                            x-text="actionModal.type === 'block' ? '{{ __('super_admin.users.block_user') }}' : '{{ __('super_admin.users.suspend_user') }}'"></h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="actionModal.user?.name"></p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __('super_admin.users.reason') }}
                    </label>
                    <textarea x-model="actionModal.reason"
                              rows="3"
                              class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              :placeholder="actionModal.type === 'block' ? '{{ __('super_admin.users.block_reason_placeholder') }}' : '{{ __('super_admin.users.suspend_reason_placeholder') }}'"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <button @click="actionModal.show = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="executeAction()"
                            :disabled="actionModal.processing"
                            class="px-4 py-2 text-white rounded-lg transition disabled:opacity-50"
                            :class="actionModal.type === 'block' ? 'bg-red-600 hover:bg-red-700' : 'bg-yellow-600 hover:bg-yellow-700'">
                        <i x-show="actionModal.processing" class="fas fa-spinner fa-spin {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <span x-text="actionModal.type === 'block' ? '{{ __('super_admin.actions.block') }}' : '{{ __('super_admin.actions.suspend') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function usersManager() {
    return {
        loading: true,
        users: [],
        organizations: [],
        selectedUsers: [],
        filters: {
            search: '',
            status: '',
            role: '',
            org_id: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0
        },
        actionModal: {
            show: false,
            type: '',
            user: null,
            reason: '',
            processing: false
        },

        async loadUsers() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    ...this.filters
                });

                const response = await fetch(`{{ route('super-admin.users.index') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                this.users = data.users?.data || data.users || [];
                this.organizations = data.organizations || [];

                if (data.users?.current_page) {
                    this.pagination = {
                        current_page: data.users.current_page,
                        last_page: data.users.last_page,
                        per_page: data.users.per_page,
                        total: data.users.total
                    };
                }
            } catch (error) {
                console.error('Error loading users:', error);
            } finally {
                this.loading = false;
            }
        },

        toggleSelectAll(checked) {
            if (checked) {
                this.selectedUsers = this.users.map(u => u.user_id);
            } else {
                this.selectedUsers = [];
            }
        },

        openActionModal(user, type) {
            this.actionModal = {
                show: true,
                type: type,
                user: user,
                reason: '',
                processing: false
            };
        },

        async executeAction() {
            if (!this.actionModal.user || !this.actionModal.type) return;

            this.actionModal.processing = true;
            try {
                const route = this.actionModal.type === 'block'
                    ? `{{ url('super-admin/users') }}/${this.actionModal.user.user_id}/block`
                    : `{{ url('super-admin/users') }}/${this.actionModal.user.user_id}/suspend`;

                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        reason: this.actionModal.reason
                    })
                });

                if (response.ok) {
                    this.actionModal.show = false;
                    await this.loadUsers();
                }
            } catch (error) {
                console.error('Error executing action:', error);
            } finally {
                this.actionModal.processing = false;
            }
        },

        async restoreUser(user) {
            try {
                const response = await fetch(`{{ url('super-admin/users') }}/${user.user_id}/restore`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.loadUsers();
                }
            } catch (error) {
                console.error('Error restoring user:', error);
            }
        },

        async impersonateUser(user) {
            if (confirm('{{ __('super_admin.users.impersonate_confirm') }}')) {
                try {
                    const response = await fetch(`{{ url('super-admin/users') }}/${user.user_id}/impersonate`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (response.ok) {
                        window.location.href = '{{ route('dashboard') }}';
                    }
                } catch (error) {
                    console.error('Error impersonating user:', error);
                }
            }
        },

        async bulkAction(action) {
            if (this.selectedUsers.length === 0) return;

            const confirmMessage = action === 'block'
                ? '{{ __('super_admin.users.bulk_block_confirm') }}'
                : action === 'suspend'
                    ? '{{ __('super_admin.users.bulk_suspend_confirm') }}'
                    : '{{ __('super_admin.users.bulk_restore_confirm') }}';

            if (!confirm(confirmMessage.replace(':count', this.selectedUsers.length))) return;

            try {
                const response = await fetch('{{ route('super-admin.users.bulk') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        action: action,
                        user_ids: this.selectedUsers
                    })
                });

                if (response.ok) {
                    this.selectedUsers = [];
                    await this.loadUsers();
                }
            } catch (error) {
                console.error('Error executing bulk action:', error);
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.loadUsers();
        }
    };
}
</script>
@endpush
