@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('dashboard_builder.page_title'))
@section('page-subtitle', __('dashboard_builder.page_subtitle'))

@section('content')
<div x-data="dashboardBuilder()" x-init="init()">
    <!-- Header Actions -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3">
            <select x-model="selectedDashboard" @change="loadDashboard"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="">{{ __('dashboard_builder.select_dashboard') }}</option>
                <template x-for="dashboard in dashboards" :key="dashboard.dashboard_id">
                    <option :value="dashboard.dashboard_id" x-text="dashboard.dashboard_name"></option>
                </template>
            </select>
        </div>

        <div class="flex gap-2">
            <button @click="showWidgetLibrary = true"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition">
                <i class="fas fa-plus me-2"></i>
                {{ __('dashboard_builder.add_widget') }}
            </button>
            <button @click="saveDashboard"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition">
                <i class="fas fa-save me-2"></i>
                {{ __('dashboard_builder.save') }}
            </button>
            <button @click="showCreateModal = true"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-lg font-medium hover:shadow-lg transition">
                <i class="fas fa-plus me-2"></i>
                {{ __('dashboard_builder.new_dashboard') }}
            </button>
        </div>
    </div>

    <!-- Dashboard Canvas -->
    <div class="bg-gray-100 rounded-xl p-6 min-h-[600px]">
        <template x-if="widgets.length === 0">
            <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                <i class="fas fa-th-large text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('dashboard_builder.empty_dashboard') }}</h3>
                <p class="text-gray-600 mb-6">{{ __('dashboard_builder.empty_dashboard_desc') }}</p>
                <button @click="showWidgetLibrary = true"
                        class="bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
                    <i class="fas fa-plus me-2"></i>
                    {{ __('dashboard_builder.add_first_widget') }}
                </button>
            </div>
        </template>

        <!-- Widgets Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <template x-for="widget in widgets" :key="widget.widget_id">
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden"
                     :class="{'md:col-span-2': widget.size === 'large', 'md:col-span-1': widget.size === 'medium'}">
                    <!-- Widget Header -->
                    <div class="bg-gray-50 px-4 py-3 border-b flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i :class="'fas fa-' + widget.icon + ' text-indigo-600'"></i>
                            <h4 class="font-bold text-gray-900" x-text="widget.widget_title"></h4>
                        </div>
                        <div class="flex gap-2">
                            <button @click="editWidget(widget.widget_id)"
                                    class="text-gray-600 hover:text-indigo-600 transition">
                                <i class="fas fa-cog text-sm"></i>
                            </button>
                            <button @click="removeWidget(widget.widget_id)"
                                    class="text-gray-600 hover:text-red-600 transition">
                                <i class="fas fa-times text-sm"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Widget Content -->
                    <div class="p-6">
                        <!-- Stats Widget -->
                        <template x-if="widget.widget_type === 'stats'">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600 mb-1" x-text="t.metric_1"></p>
                                    <p class="text-2xl font-bold text-gray-900">12,345</p>
                                </div>
                                <div class="bg-gradient-to-br from-green-50 to-green-100 p-4 rounded-lg">
                                    <p class="text-sm text-gray-600 mb-1" x-text="t.metric_2"></p>
                                    <p class="text-2xl font-bold text-gray-900">98.5%</p>
                                </div>
                            </div>
                        </template>

                        <!-- Chart Widget -->
                        <template x-if="widget.widget_type === 'chart'">
                            <div class="h-64 bg-gray-50 rounded-lg flex items-center justify-center">
                                <div class="text-center">
                                    <i class="fas fa-chart-area text-4xl text-gray-300 mb-2"></i>
                                    <p class="text-sm text-gray-600" x-text="t.interactive_chart"></p>
                                </div>
                            </div>
                        </template>

                        <!-- Table Widget -->
                        <template x-if="widget.widget_type === 'table'">
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-500" x-text="t.campaign"></th>
                                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-500" x-text="t.spend"></th>
                                            <th class="px-3 py-2 text-start text-xs font-medium text-gray-500">ROAS</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y">
                                        <tr>
                                            <td class="px-3 py-2 text-gray-900" x-text="t.sample_campaign"></td>
                                            <td class="px-3 py-2 text-gray-600">5,000 {{ __('common.currency_sar') }}</td>
                                            <td class="px-3 py-2 text-green-600">3.2x</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>

                        <!-- List Widget -->
                        <template x-if="widget.widget_type === 'list'">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-900" x-text="t.item_1"></span>
                                    <span class="text-sm font-bold text-gray-600">100</span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded">
                                    <span class="text-sm text-gray-900" x-text="t.item_2"></span>
                                    <span class="text-sm font-bold text-gray-600">85</span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Widget Library Modal -->
    <div x-show="showWidgetLibrary" @click.away="showWidgetLibrary = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ __('dashboard_builder.widget_library') }}</h3>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <!-- Stats Widget -->
                <button @click="addWidget('stats')"
                        class="bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border border-blue-200 rounded-xl p-4 text-start transition group">
                    <div class="bg-blue-600 p-3 rounded-lg inline-block mb-3 group-hover:scale-110 transition">
                        <i class="fas fa-chart-bar text-white text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">{{ __('dashboard_builder.widget_stats') }}</h4>
                    <p class="text-sm text-gray-600">{{ __('dashboard_builder.widget_stats_desc') }}</p>
                </button>

                <!-- Chart Widget -->
                <button @click="addWidget('chart')"
                        class="bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border border-green-200 rounded-xl p-4 text-start transition group">
                    <div class="bg-green-600 p-3 rounded-lg inline-block mb-3 group-hover:scale-110 transition">
                        <i class="fas fa-chart-area text-white text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">{{ __('dashboard_builder.widget_chart') }}</h4>
                    <p class="text-sm text-gray-600">{{ __('dashboard_builder.widget_chart_desc') }}</p>
                </button>

                <!-- Table Widget -->
                <button @click="addWidget('table')"
                        class="bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 border border-purple-200 rounded-xl p-4 text-start transition group">
                    <div class="bg-purple-600 p-3 rounded-lg inline-block mb-3 group-hover:scale-110 transition">
                        <i class="fas fa-table text-white text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">{{ __('dashboard_builder.widget_table') }}</h4>
                    <p class="text-sm text-gray-600">{{ __('dashboard_builder.widget_table_desc') }}</p>
                </button>

                <!-- List Widget -->
                <button @click="addWidget('list')"
                        class="bg-gradient-to-br from-yellow-50 to-yellow-100 hover:from-yellow-100 hover:to-yellow-200 border border-yellow-200 rounded-xl p-4 text-start transition group">
                    <div class="bg-yellow-600 p-3 rounded-lg inline-block mb-3 group-hover:scale-110 transition">
                        <i class="fas fa-list text-white text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">{{ __('dashboard_builder.widget_list') }}</h4>
                    <p class="text-sm text-gray-600">{{ __('dashboard_builder.widget_list_desc') }}</p>
                </button>

                <!-- Progress Widget -->
                <button @click="addWidget('progress')"
                        class="bg-gradient-to-br from-indigo-50 to-indigo-100 hover:from-indigo-100 hover:to-indigo-200 border border-indigo-200 rounded-xl p-4 text-start transition group">
                    <div class="bg-indigo-600 p-3 rounded-lg inline-block mb-3 group-hover:scale-110 transition">
                        <i class="fas fa-tasks text-white text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">{{ __('dashboard_builder.widget_progress') }}</h4>
                    <p class="text-sm text-gray-600">{{ __('dashboard_builder.widget_progress_desc') }}</p>
                </button>

                <!-- Alert Widget -->
                <button @click="addWidget('alert')"
                        class="bg-gradient-to-br from-red-50 to-red-100 hover:from-red-100 hover:to-red-200 border border-red-200 rounded-xl p-4 text-start transition group">
                    <div class="bg-red-600 p-3 rounded-lg inline-block mb-3 group-hover:scale-110 transition">
                        <i class="fas fa-bell text-white text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-1">{{ __('dashboard_builder.widget_alert') }}</h4>
                    <p class="text-sm text-gray-600">{{ __('dashboard_builder.widget_alert_desc') }}</p>
                </button>
            </div>

            <div class="flex justify-end mt-6">
                <button @click="showWidgetLibrary = false"
                        class="bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                    {{ __('dashboard_builder.close') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Create Dashboard Modal -->
    <div x-show="showCreateModal" @click.away="showCreateModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-md w-full mx-4">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ __('dashboard_builder.new_dashboard_title') }}</h3>
            <form @submit.prevent="createDashboard">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('dashboard_builder.dashboard_name') }}</label>
                        <input type="text" x-model="newDashboard.name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('dashboard_builder.description') }}</label>
                        <textarea x-model="newDashboard.description" rows="3"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
                            {{ __('dashboard_builder.create') }}
                        </button>
                        <button type="button" @click="showCreateModal = false"
                                class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                            {{ __('dashboard_builder.cancel') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboardBuilder() {
    return {
        dashboards: @json($dashboards ?? []),
        selectedDashboard: '',
        widgets: [],
        showWidgetLibrary: false,
        showCreateModal: false,
        newDashboard: {
            name: '',
            description: ''
        },
        // Translations object for JavaScript
        t: {
            metric_1: '{{ __('dashboard_builder.metric_1') }}',
            metric_2: '{{ __('dashboard_builder.metric_2') }}',
            interactive_chart: '{{ __('dashboard_builder.interactive_chart') }}',
            campaign: '{{ __('dashboard_builder.campaign') }}',
            spend: '{{ __('dashboard_builder.spend') }}',
            sample_campaign: '{{ __('dashboard_builder.sample_campaign') }}',
            item_1: '{{ __('dashboard_builder.item_1') }}',
            item_2: '{{ __('dashboard_builder.item_2') }}',
            widget_stats: '{{ __('dashboard_builder.widget_stats') }}',
            widget_chart: '{{ __('dashboard_builder.widget_chart') }}',
            widget_table: '{{ __('dashboard_builder.widget_table') }}',
            widget_list: '{{ __('dashboard_builder.widget_list') }}',
            widget_progress: '{{ __('dashboard_builder.widget_progress') }}',
            widget_alert: '{{ __('dashboard_builder.widget_alert') }}',
            create_failed: '{{ __('dashboard_builder.create_failed') }}',
            remove_widget_confirm: '{{ __('dashboard_builder.remove_widget_confirm') }}',
            edit_in_development: '{{ __('dashboard_builder.edit_in_development') }}',
            select_dashboard_first: '{{ __('dashboard_builder.select_dashboard_first') }}',
            saved_successfully: '{{ __('dashboard_builder.saved_successfully') }}',
            save_failed: '{{ __('dashboard_builder.save_failed') }}'
        },

        init() {
            // Load first dashboard if exists
            if (this.dashboards.length > 0) {
                this.selectedDashboard = this.dashboards[0].dashboard_id;
                this.loadDashboard();
            }
        },

        async loadDashboard() {
            if (!this.selectedDashboard) {
                this.widgets = [];
                return;
            }

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/dashboard-builder/${this.selectedDashboard}`);
                if (response.ok) {
                    const data = await response.json();
                    this.widgets = data.widgets || [];
                }
            } catch (error) {
                console.error('Failed to load dashboard:', error);
            }
        },

        async createDashboard() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/dashboard-builder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newDashboard)
                });

                if (response.ok) {
                    const data = await response.json();
                    this.dashboards.push(data.dashboard);
                    this.selectedDashboard = data.dashboard.dashboard_id;
                    this.showCreateModal = false;
                    this.newDashboard = { name: '', description: '' };
                    this.widgets = [];
                }
            } catch (error) {
                console.error('Failed to create dashboard:', error);
                alert(this.t.create_failed);
            }
        },

        addWidget(type) {
            const widget = {
                widget_id: `widget-${Date.now()}`,
                widget_type: type,
                widget_title: this.getWidgetTitle(type),
                icon: this.getWidgetIcon(type),
                size: 'medium',
                config: {}
            };

            this.widgets.push(widget);
            this.showWidgetLibrary = false;
        },

        removeWidget(widgetId) {
            if (!confirm(this.t.remove_widget_confirm)) return;
            this.widgets = this.widgets.filter(w => w.widget_id !== widgetId);
        },

        editWidget(widgetId) {
            alert(this.t.edit_in_development);
        },

        async saveDashboard() {
            if (!this.selectedDashboard) {
                alert(this.t.select_dashboard_first);
                return;
            }

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/dashboard-builder/${this.selectedDashboard}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ widgets: this.widgets })
                });

                if (response.ok) {
                    alert(this.t.saved_successfully);
                }
            } catch (error) {
                console.error('Failed to save dashboard:', error);
                alert(this.t.save_failed);
            }
        },

        getWidgetTitle(type) {
            const titles = {
                'stats': this.t.widget_stats,
                'chart': this.t.widget_chart,
                'table': this.t.widget_table,
                'list': this.t.widget_list,
                'progress': this.t.widget_progress,
                'alert': this.t.widget_alert
            };
            return titles[type] || type;
        },

        getWidgetIcon(type) {
            const icons = {
                'stats': 'chart-bar',
                'chart': 'chart-area',
                'table': 'table',
                'list': 'list',
                'progress': 'tasks',
                'alert': 'bell'
            };
            return icons[type] || 'cube';
        }
    };
}
</script>
@endpush
