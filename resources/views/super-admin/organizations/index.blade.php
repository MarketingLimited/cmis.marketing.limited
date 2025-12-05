@extends('super-admin.layouts.app')

@section('title', __('super_admin.organizations'))

@section('breadcrumb')
<span class="text-gray-400">/</span>
<span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.organizations') }}</span>
@endsection

@section('content')
<div x-data="organizationsManager()" x-init="init()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.organizations') }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.organizations_subtitle') }}</p>
        </div>

        <!-- Bulk Actions -->
        <div class="flex items-center gap-2" x-show="selectedOrgs.length > 0">
            <span class="text-sm text-gray-500" x-text="selectedOrgs.length + ' {{ __('super_admin.selected') }}'"></span>
            <button @click="bulkAction('suspend')" class="px-3 py-2 bg-yellow-500 text-white text-sm rounded-lg hover:bg-yellow-600 transition">
                <i class="fas fa-pause {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                {{ __('super_admin.suspend') }}
            </button>
            <button @click="bulkAction('restore')" class="px-3 py-2 bg-green-500 text-white text-sm rounded-lg hover:bg-green-600 transition">
                <i class="fas fa-play {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                {{ __('super_admin.restore') }}
            </button>
            <button @click="bulkAction('block')" class="px-3 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition">
                <i class="fas fa-ban {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                {{ __('super_admin.block') }}
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col sm:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.300ms="loadOrganizations()"
                           placeholder="{{ __('super_admin.search_organizations') }}"
                           class="w-full px-4 py-2 {{ $isRtl ? 'pr-10' : 'pl-10' }} border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white">
                    <i class="fas fa-search absolute {{ $isRtl ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Status Filter -->
            <select x-model="filters.status"
                    @change="loadOrganizations()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('super_admin.all_statuses') }}</option>
                <option value="active">{{ __('super_admin.status_active') }}</option>
                <option value="suspended">{{ __('super_admin.status_suspended') }}</option>
                <option value="blocked">{{ __('super_admin.status_blocked') }}</option>
            </select>

            <!-- Plan Filter -->
            <select x-model="filters.plan_id"
                    @change="loadOrganizations()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white">
                <option value="">{{ __('super_admin.all_plans') }}</option>
                @foreach($plans ?? [] as $plan)
                <option value="{{ $plan->plan_id }}">{{ $plan->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- Organizations Table -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-start">
                            <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                        </th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('super_admin.organization') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('super_admin.plan') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('super_admin.users') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('super_admin.status') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('super_admin.created_at') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('super_admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <template x-for="org in organizations" :key="org.org_id">
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                            <td class="px-4 py-4">
                                <input type="checkbox" :value="org.org_id" x-model="selectedOrgs" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                         x-text="org.name.substring(0, 2).toUpperCase()"></div>
                                    <div>
                                        <a :href="'{{ route('super-admin.orgs.show', '') }}/' + org.org_id" class="font-medium text-gray-900 dark:text-white hover:text-red-600" x-text="org.name"></a>
                                        <p class="text-xs text-gray-500" x-text="org.email"></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="{
                                          'bg-gray-100 text-gray-800': org.plan?.code === 'free',
                                          'bg-blue-100 text-blue-800': org.plan?.code === 'starter',
                                          'bg-purple-100 text-purple-800': org.plan?.code === 'professional',
                                          'bg-yellow-100 text-yellow-800': org.plan?.code === 'enterprise'
                                      }"
                                      x-text="org.plan?.name || '{{ __('super_admin.no_plan') }}'"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-600 dark:text-gray-400" x-text="org.users_count || 0"></td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-1 text-xs font-medium rounded-full"
                                      :class="{
                                          'bg-green-100 text-green-800': org.status === 'active',
                                          'bg-yellow-100 text-yellow-800': org.status === 'suspended',
                                          'bg-red-100 text-red-800': org.status === 'blocked'
                                      }"
                                      x-text="org.status"></span>
                            </td>
                            <td class="px-4 py-4 text-gray-600 dark:text-gray-400 text-sm" x-text="org.created_at"></td>
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <a :href="'{{ route('super-admin.orgs.show', '') }}/' + org.org_id"
                                       class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                       title="{{ __('super_admin.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button @click="openActionModal(org, 'suspend')"
                                            x-show="org.status !== 'suspended'"
                                            class="p-2 text-gray-500 hover:text-yellow-600 hover:bg-yellow-50 rounded-lg transition"
                                            title="{{ __('super_admin.suspend') }}">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    <button @click="openActionModal(org, 'block')"
                                            x-show="org.status !== 'blocked'"
                                            class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="{{ __('super_admin.block') }}">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <button @click="restoreOrg(org)"
                                            x-show="org.status !== 'active'"
                                            class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition"
                                            title="{{ __('super_admin.restore') }}">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            <p class="text-gray-500 mt-2">{{ __('common.loading') }}</p>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && organizations.length === 0" class="p-8 text-center">
            <i class="fas fa-building text-4xl text-gray-300"></i>
            <p class="text-gray-500 mt-2">{{ __('super_admin.no_organizations_found') }}</p>
        </div>

        <!-- Pagination -->
        <div x-show="pagination.total > pagination.per_page" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-500" x-text="'{{ __('super_admin.showing') }} ' + pagination.from + '-' + pagination.to + ' {{ __('super_admin.of') }} ' + pagination.total"></p>
                <div class="flex gap-2">
                    <button @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                        <i class="fas fa-chevron-{{ $isRtl ? 'right' : 'left' }}"></i>
                    </button>
                    <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1 border border-gray-300 rounded-lg text-sm disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50">
                        <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }}"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Modal -->
    <div x-show="actionModal.show" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        <div @click.away="actionModal.show = false"
             class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4" x-text="actionModal.title"></h3>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('super_admin.reason') }}</label>
                    <textarea x-model="actionModal.reason"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 dark:bg-gray-700 dark:text-white"
                              :placeholder="'{{ __('super_admin.enter_reason') }}'"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button @click="actionModal.show = false"
                            class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="executeAction()"
                            :disabled="processing"
                            class="px-4 py-2 text-white rounded-lg transition disabled:opacity-50"
                            :class="{
                                'bg-yellow-500 hover:bg-yellow-600': actionModal.action === 'suspend',
                                'bg-red-500 hover:bg-red-600': actionModal.action === 'block'
                            }">
                        <i x-show="processing" class="fas fa-spinner fa-spin {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                        <span x-text="actionModal.buttonText"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function organizationsManager() {
    return {
        loading: false,
        processing: false,
        organizations: [],
        selectedOrgs: [],
        selectAll: false,
        filters: {
            search: '',
            status: '',
            plan_id: ''
        },
        pagination: {
            current_page: 1,
            per_page: 20,
            total: 0,
            from: 0,
            to: 0,
            last_page: 1
        },
        actionModal: {
            show: false,
            org: null,
            action: '',
            reason: '',
            title: '',
            buttonText: ''
        },

        async init() {
            await this.loadOrganizations();
        },

        async loadOrganizations() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    per_page: this.pagination.per_page,
                    ...this.filters
                });

                const response = await fetch(`{{ route('super-admin.orgs.index') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    const data = result.data || result;
                    this.organizations = data.data || [];
                    this.pagination = {
                        current_page: data.current_page || 1,
                        per_page: data.per_page || 20,
                        total: data.total || 0,
                        from: data.from || 0,
                        to: data.to || 0,
                        last_page: data.last_page || 1
                    };
                }
            } catch (error) {
                console.error('Failed to load organizations:', error);
            } finally {
                this.loading = false;
            }
        },

        toggleSelectAll() {
            if (this.selectAll) {
                this.selectedOrgs = this.organizations.map(o => o.org_id);
            } else {
                this.selectedOrgs = [];
            }
        },

        openActionModal(org, action) {
            this.actionModal = {
                show: true,
                org: org,
                action: action,
                reason: '',
                title: action === 'suspend' ? '{{ __('super_admin.suspend_organization') }}' : '{{ __('super_admin.block_organization') }}',
                buttonText: action === 'suspend' ? '{{ __('super_admin.suspend') }}' : '{{ __('super_admin.block') }}'
            };
        },

        async executeAction() {
            if (!this.actionModal.org) return;

            this.processing = true;
            try {
                const route = this.actionModal.action === 'suspend'
                    ? `{{ route('super-admin.orgs.suspend', '') }}/${this.actionModal.org.org_id}`
                    : `{{ route('super-admin.orgs.block', '') }}/${this.actionModal.org.org_id}`;

                const response = await fetch(route, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ reason: this.actionModal.reason })
                });

                if (response.ok) {
                    this.actionModal.show = false;
                    await this.loadOrganizations();
                }
            } catch (error) {
                console.error('Action failed:', error);
            } finally {
                this.processing = false;
            }
        },

        async restoreOrg(org) {
            this.processing = true;
            try {
                const response = await fetch(`{{ route('super-admin.orgs.restore', '') }}/${org.org_id}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.loadOrganizations();
                }
            } catch (error) {
                console.error('Restore failed:', error);
            } finally {
                this.processing = false;
            }
        },

        async bulkAction(action) {
            if (this.selectedOrgs.length === 0) return;

            this.processing = true;
            try {
                const response = await fetch('{{ route('super-admin.orgs.bulk') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        action: action,
                        org_ids: this.selectedOrgs
                    })
                });

                if (response.ok) {
                    this.selectedOrgs = [];
                    this.selectAll = false;
                    await this.loadOrganizations();
                }
            } catch (error) {
                console.error('Bulk action failed:', error);
            } finally {
                this.processing = false;
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.loadOrganizations();
        }
    };
}
</script>
@endpush
