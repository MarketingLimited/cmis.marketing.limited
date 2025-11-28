@extends('layouts.admin')

@section('title', __('orchestration.title'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div x-data="orchestrationDashboard()" x-init="init()" class="space-y-6" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('orchestration.title') }}</span>
        </nav>
        <div class="flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('orchestration.title') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('orchestration.subtitle') }}</p>
            </div>
            <div class="flex {{ $isRtl ? 'space-x-reverse space-x-3' : 'space-x-3' }}">
                <button @click="showCreateModal = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    <span>{{ __('orchestration.create_campaign') }}</span>
                </button>
                <button @click="refreshData()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                        :disabled="loading">
                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Alert Messages --}}
    <div x-show="alert.show"
         x-transition
         class="rounded-lg p-4 mb-6"
         :class="{
             'bg-green-50 border-l-4 border-green-400': alert.type === 'success',
             'bg-red-50 border-l-4 border-red-400': alert.type === 'error',
             'bg-yellow-50 border-l-4 border-yellow-400': alert.type === 'warning'
         }">
        <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="flex-shrink-0">
                <i class="fas"
                   :class="{
                       'fa-check-circle text-green-400': alert.type === 'success',
                       'fa-exclamation-circle text-red-400': alert.type === 'error',
                       'fa-exclamation-triangle text-yellow-400': alert.type === 'warning'
                   }"></i>
            </div>
            <div class="{{ $isRtl ? 'mr-3 text-right' : 'ml-3' }}">
                <p class="text-sm font-medium"
                   :class="{
                       'text-green-800': alert.type === 'success',
                       'text-red-800': alert.type === 'error',
                       'text-yellow-800': alert.type === 'warning'
                   }"
                   x-text="alert.message"></p>
            </div>
        </div>
    </div>

    {{-- Platform Status Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-6">
        <template x-for="platform in platforms" :key="platform.id">
            <div class="bg-white rounded-lg shadow p-4 border-l-4"
                 :class="platform.connected ? 'border-green-500' : 'border-gray-300'">
                <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                        <div class="flex items-center gap-2 mb-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <i :class="platform.icon" class="text-xl" :style="'color: ' + platform.color"></i>
                            <span class="font-semibold text-gray-900" x-text="platform.name"></span>
                        </div>
                        <div class="flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                  :class="platform.connected ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                                <span x-text="platform.connected ? '{{ __('orchestration.connected') }}' : '{{ __('orchestration.not_connected') }}'"></span>
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <span x-text="platform.campaigns"></span> {{ __('orchestration.active_campaigns_count') }}
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <i class="fas fa-rocket text-2xl text-blue-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-600">{{ __('orchestration.active_campaigns') }}</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.activeCampaigns">0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-600">{{ __('orchestration.total_budget') }}</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(stats.totalBudget)">$0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                    <i class="fas fa-chart-line text-2xl text-purple-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-600">{{ __('orchestration.total_spend') }}</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="formatCurrency(stats.totalSpend)">$0</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0 bg-orange-100 rounded-lg p-3">
                    <i class="fas fa-bullseye text-2xl text-orange-600"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-4 text-right' : 'ml-4' }}">
                    <p class="text-sm font-medium text-gray-600">{{ __('orchestration.avg_roas') }}</p>
                    <p class="text-2xl font-bold text-gray-900" x-text="stats.avgROAS.toFixed(2) + 'x'">0.00x</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Campaigns Table --}}
    <div class="bg-white rounded-lg shadow">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('orchestration.orchestrated_campaigns') }}</h2>
                <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <input type="text"
                           x-model="searchQuery"
                           @input="filterCampaigns()"
                           placeholder="{{ __('orchestration.search_placeholder') }}"
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <select x-model="filterStatus"
                            @change="filterCampaigns()"
                            class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ __('orchestration.all_status') }}</option>
                        <option value="active">{{ __('orchestration.status_active') }}</option>
                        <option value="paused">{{ __('orchestration.status_paused') }}</option>
                        <option value="scheduled">{{ __('orchestration.status_scheduled') }}</option>
                        <option value="completed">{{ __('orchestration.status_completed') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('orchestration.campaign') }}
                        </th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('orchestration.platforms') }}
                        </th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('common.status') }}
                        </th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('orchestration.budget') }}
                        </th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('orchestration.spend') }}
                        </th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('orchestration.roas') }}
                        </th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-if="loading">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                                <p class="mt-2 text-gray-500">{{ __('orchestration.loading_campaigns') }}</p>
                            </td>
                        </tr>
                    </template>
                    <template x-if="!loading && filteredCampaigns.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">{{ __('orchestration.no_campaigns') }}</p>
                                <button @click="showCreateModal = true"
                                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    {{ __('orchestration.create_first_campaign') }}
                                </button>
                            </td>
                        </tr>
                    </template>
                    <template x-for="campaign in filteredCampaigns" :key="campaign.id">
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="{{ $isRtl ? 'text-right' : '' }}">
                                    <div class="text-sm font-medium text-gray-900" x-text="campaign.name"></div>
                                    <div class="text-sm text-gray-500" x-text="campaign.objective"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <template x-for="platform in campaign.platforms" :key="platform">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                              x-text="platform"></span>
                                    </template>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': campaign.status === 'active',
                                          'bg-yellow-100 text-yellow-800': campaign.status === 'paused',
                                          'bg-blue-100 text-blue-800': campaign.status === 'scheduled',
                                          'bg-gray-100 text-gray-800': campaign.status === 'completed'
                                      }"
                                      x-text="campaign.status"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatCurrency(campaign.budget)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatCurrency(campaign.spend)"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-medium"
                                      :class="campaign.roas >= 2 ? 'text-green-600' : 'text-red-600'"
                                      x-text="campaign.roas.toFixed(2) + 'x'"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <button @click="viewCampaign(campaign.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                            title="{{ __('orchestration.view_details') }}">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button @click="pauseCampaign(campaign.id)"
                                            x-show="campaign.status === 'active'"
                                            class="text-yellow-600 hover:text-yellow-900"
                                            title="{{ __('orchestration.pause') }}">
                                        <i class="fas fa-pause"></i>
                                    </button>
                                    <button @click="resumeCampaign(campaign.id)"
                                            x-show="campaign.status === 'paused'"
                                            class="text-green-600 hover:text-green-900"
                                            title="{{ __('orchestration.resume') }}">
                                        <i class="fas fa-play"></i>
                                    </button>
                                    <button @click="duplicateCampaign(campaign.id)"
                                            class="text-purple-600 hover:text-purple-900"
                                            title="{{ __('orchestration.duplicate') }}">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
function orchestrationDashboard() {
    return {
        loading: false,
        showCreateModal: false,
        searchQuery: '',
        filterStatus: '',
        campaigns: [],
        filteredCampaigns: [],
        alert: {
            show: false,
            type: 'success',
            message: ''
        },
        platforms: [
            { id: 'meta', name: 'Meta', icon: 'fab fa-meta', color: '#0668E1', connected: true, campaigns: 0 },
            { id: 'google', name: 'Google', icon: 'fab fa-google', color: '#4285F4', connected: true, campaigns: 0 },
            { id: 'tiktok', name: 'TikTok', icon: 'fab fa-tiktok', color: '#000000', connected: false, campaigns: 0 },
            { id: 'linkedin', name: 'LinkedIn', icon: 'fab fa-linkedin', color: '#0A66C2', connected: false, campaigns: 0 },
            { id: 'twitter', name: 'Twitter', icon: 'fab fa-twitter', color: '#1DA1F2', connected: false, campaigns: 0 },
            { id: 'snapchat', name: 'Snapchat', icon: 'fab fa-snapchat', color: '#FFFC00', connected: false, campaigns: 0 }
        ],
        stats: {
            activeCampaigns: 0,
            totalBudget: 0,
            totalSpend: 0,
            avgROAS: 0
        },

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const orgId = '{{ $currentOrg }}';
                const response = await fetch(`/api/orgs/${orgId}/orchestration/campaigns`);
                const data = await response.json();

                if (data.success) {
                    this.campaigns = data.data || [];
                    this.filteredCampaigns = this.campaigns;
                    this.updateStats();
                    this.updatePlatformCounts();
                }
            } catch (error) {
                console.error('Error loading data:', error);
                this.showAlert('error', 'Failed to load campaigns');
            } finally {
                this.loading = false;
            }
        },

        filterCampaigns() {
            this.filteredCampaigns = this.campaigns.filter(campaign => {
                const matchesSearch = campaign.name.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesStatus = !this.filterStatus || campaign.status === this.filterStatus;
                return matchesSearch && matchesStatus;
            });
        },

        updateStats() {
            this.stats.activeCampaigns = this.campaigns.filter(c => c.status === 'active').length;
            this.stats.totalBudget = this.campaigns.reduce((sum, c) => sum + parseFloat(c.budget || 0), 0);
            this.stats.totalSpend = this.campaigns.reduce((sum, c) => sum + parseFloat(c.spend || 0), 0);
            const totalROAS = this.campaigns.reduce((sum, c) => sum + parseFloat(c.roas || 0), 0);
            this.stats.avgROAS = this.campaigns.length > 0 ? totalROAS / this.campaigns.length : 0;
        },

        updatePlatformCounts() {
            this.platforms.forEach(platform => {
                platform.campaigns = this.campaigns.filter(c =>
                    c.platforms && c.platforms.includes(platform.id)
                ).length;
            });
        },

        async refreshData() {
            await this.loadData();
            this.showAlert('success', '{{ __("orchestration.data_refreshed") }}');
        },

        async pauseCampaign(id) {
            if (!confirm('{{ __("orchestration.confirm_pause") }}')) return;

            try {
                const orgId = '{{ $currentOrg }}';
                const response = await fetch(`/api/orgs/${orgId}/orchestration/campaigns/${id}/pause`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showAlert('success', '{{ __("orchestration.campaign_paused") }}');
                    await this.loadData();
                } else {
                    this.showAlert('error', data.message || '{{ __("orchestration.failed_pause") }}');
                }
            } catch (error) {
                console.error('Error pausing campaign:', error);
                this.showAlert('error', '{{ __("orchestration.failed_pause") }}');
            }
        },

        async resumeCampaign(id) {
            try {
                const orgId = '{{ $currentOrg }}';
                const response = await fetch(`/api/orgs/${orgId}/orchestration/campaigns/${id}/resume`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();

                if (data.success) {
                    this.showAlert('success', '{{ __("orchestration.campaign_resumed") }}');
                    await this.loadData();
                } else {
                    this.showAlert('error', data.message || '{{ __("orchestration.failed_resume") }}');
                }
            } catch (error) {
                console.error('Error resuming campaign:', error);
                this.showAlert('error', '{{ __("orchestration.failed_resume") }}');
            }
        },

        viewCampaign(id) {
            const orgId = '{{ $currentOrg }}';
            window.location.href = `/orgs/${orgId}/orchestration/campaigns/${id}`;
        },

        duplicateCampaign(id) {
            const orgId = '{{ $currentOrg }}';
            window.location.href = `/orgs/${orgId}/orchestration/campaigns/${id}/duplicate`;
        },

        formatCurrency(value) {
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: 'USD',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value || 0);
        },

        showAlert(type, message) {
            this.alert = { show: true, type, message };
            setTimeout(() => {
                this.alert.show = false;
            }, 5000);
        }
    };
}
</script>
@endpush
@endsection
