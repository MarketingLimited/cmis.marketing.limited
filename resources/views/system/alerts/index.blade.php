@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('alerts.page_title'))
@section('page-subtitle', __('alerts.page_subtitle'))

@section('content')
<div x-data="alertsManager()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-red-100 text-sm mb-1">{{ __('alerts.critical') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.critical"></p>
                </div>
                <i class="fas fa-exclamation-circle text-5xl text-red-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm mb-1">{{ __('alerts.warnings') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.warnings"></p>
                </div>
                <i class="fas fa-exclamation-triangle text-5xl text-yellow-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">{{ __('alerts.info') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.info"></p>
                </div>
                <i class="fas fa-info-circle text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">{{ __('alerts.resolved') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.resolved"></p>
                </div>
                <i class="fas fa-check-circle text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3">
            <select x-model="severityFilter" @change="filterAlerts"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">{{ __('alerts.all_severities') }}</option>
                <option value="critical">{{ __('alerts.critical_severity') }}</option>
                <option value="warning">{{ __('alerts.warning_severity') }}</option>
                <option value="info">{{ __('alerts.info_severity') }}</option>
            </select>

            <select x-model="typeFilter" @change="filterAlerts"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">{{ __('alerts.all_types') }}</option>
                <option value="budget">{{ __('alerts.budget_type') }}</option>
                <option value="performance">{{ __('alerts.performance_type') }}</option>
                <option value="system">{{ __('alerts.system_type') }}</option>
                <option value="campaign">{{ __('alerts.campaign_type') }}</option>
            </select>

            <select x-model="statusFilter" @change="filterAlerts"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="active">{{ __('alerts.active_status') }}</option>
                <option value="resolved">{{ __('alerts.resolved_status') }}</option>
                <option value="all">{{ __('alerts.all_status') }}</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button @click="markAllAsRead"
                    class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg font-medium hover:bg-gray-200 transition">
                <i class="fas fa-check-double ml-2"></i>
                {{ __('alerts.mark_all_as_read') }}
            </button>
            <button @click="showSettingsModal = true"
                    class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition">
                <i class="fas fa-cog ml-2"></i>
                {{ __('alerts.settings') }}
            </button>
        </div>
    </div>

    <!-- Alerts List -->
    <div class="space-y-4">
        <template x-for="alert in filteredAlerts" :key="alert.alert_id">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden"
                 :class="{'border-r-4': true, 'border-red-500': alert.severity === 'critical', 'border-yellow-500': alert.severity === 'warning', 'border-blue-500': alert.severity === 'info'}">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <!-- Alert Content -->
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <!-- Severity Icon -->
                                <div class="flex-shrink-0">
                                    <template x-if="alert.severity === 'critical'">
                                        <div class="bg-red-100 p-2 rounded-lg">
                                            <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                                        </div>
                                    </template>
                                    <template x-if="alert.severity === 'warning'">
                                        <div class="bg-yellow-100 p-2 rounded-lg">
                                            <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                                        </div>
                                    </template>
                                    <template x-if="alert.severity === 'info'">
                                        <div class="bg-blue-100 p-2 rounded-lg">
                                            <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                                        </div>
                                    </template>
                                </div>

                                <!-- Alert Details -->
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <h3 class="text-lg font-bold text-gray-900" x-text="alert.alert_title"></h3>
                                        <template x-if="!alert.is_read">
                                            <span class="w-2 h-2 bg-indigo-600 rounded-full"></span>
                                        </template>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2" x-text="alert.alert_message"></p>

                                    <!-- Metadata -->
                                    <div class="flex items-center gap-4 text-xs text-gray-500">
                                        <span class="flex items-center">
                                            <i class="fas fa-tag text-xs ml-1"></i>
                                            <span x-text="getTypeLabel(alert.alert_type)"></span>
                                        </span>
                                        <template x-if="alert.campaign_name">
                                            <span class="flex items-center">
                                                <i class="fas fa-bullhorn text-xs ml-1"></i>
                                                <span x-text="alert.campaign_name"></span>
                                            </span>
                                        </template>
                                        <span class="flex items-center">
                                            <i class="fas fa-clock text-xs ml-1"></i>
                                            <span x-text="formatDate(alert.created_at)"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Required -->
                            <template x-if="alert.action_required">
                                <div class="bg-gradient-to-r from-orange-50 to-red-50 border border-orange-200 rounded-lg p-3 mt-3">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-hand-point-left text-orange-600"></i>
                                        <p class="text-sm font-medium text-orange-900" x-text="alert.action_required"></p>
                                    </div>
                                </div>
                            </template>

                            <!-- Recommended Actions -->
                            <template x-if="alert.recommended_actions && alert.recommended_actions.length > 0">
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <template x-for="action in alert.recommended_actions" :key="action">
                                        <button @click="performAction(alert.alert_id, action)"
                                                class="bg-indigo-50 text-indigo-600 px-3 py-1 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
                                            <i class="fas fa-bolt text-xs ml-1"></i>
                                            <span x-text="action"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Alert Actions -->
                        <div class="flex flex-col gap-2 mr-4">
                            <template x-if="alert.status === 'active'">
                                <button @click="resolveAlert(alert.alert_id)"
                                        class="bg-green-50 text-green-600 px-3 py-2 rounded-lg hover:bg-green-100 transition text-sm"
                                        title="وضع علامة محلولة">
                                    <i class="fas fa-check"></i>
                                </button>
                            </template>
                            <template x-if="!alert.is_read">
                                <button @click="markAsRead(alert.alert_id)"
                                        class="bg-blue-50 text-blue-600 px-3 py-2 rounded-lg hover:bg-blue-100 transition text-sm"
                                        title="وضع علامة مقروءة">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </template>
                            <button @click="dismissAlert(alert.alert_id)"
                                    class="bg-gray-50 text-gray-600 px-3 py-2 rounded-lg hover:bg-gray-100 transition text-sm"
                                    title="إخفاء">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredAlerts.length === 0">
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-bell-slash text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('alerts.no_alerts') }}</h3>
            <p class="text-gray-600">{{ __('alerts.all_systems_normal') }}</p>
        </div>
    </template>

    <!-- Settings Modal -->
    <div x-show="showSettingsModal" @click.away="showSettingsModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ __('alerts.settings_title') }}</h3>

            <div class="space-y-4">
                <!-- Budget Alerts -->
                <div class="border-b pb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ __('alerts.budget_alerts') }}</h4>
                            <p class="text-sm text-gray-600">{{ __('alerts.budget_alerts_description') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.budgetAlerts" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                    <template x-if="settings.budgetAlerts">
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('alerts.alert_threshold_percentage') }}</label>
                            <input type="number" x-model="settings.budgetThreshold" min="50" max="100"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </template>
                </div>

                <!-- Performance Alerts -->
                <div class="border-b pb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ __('alerts.performance_alerts') }}</h4>
                            <p class="text-sm text-gray-600">{{ __('alerts.performance_alerts_description') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.performanceAlerts" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Email Notifications -->
                <div class="border-b pb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ __('alerts.email_notifications') }}</h4>
                            <p class="text-sm text-gray-600">{{ __('alerts.email_notifications_description') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.emailNotifications" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <!-- SMS Notifications -->
                <div class="pb-4">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <h4 class="font-bold text-gray-900">{{ __('alerts.sms_notifications') }}</h4>
                            <p class="text-sm text-gray-600">{{ __('alerts.sms_notifications_description') }}</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" x-model="settings.smsNotifications" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-3 pt-4">
                    <button @click="saveSettings"
                            class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
                        {{ __('alerts.save_settings') }}
                    </button>
                    <button @click="showSettingsModal = false"
                            class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function alertsManager() {
    return {
        alerts: @json($alerts ?? []),
        severityFilter: 'all',
        typeFilter: 'all',
        statusFilter: 'active',
        showSettingsModal: false,
        stats: {
            critical: 0,
            warnings: 0,
            info: 0,
            resolved: 0
        },
        settings: {
            budgetAlerts: true,
            budgetThreshold: 80,
            performanceAlerts: true,
            emailNotifications: true,
            smsNotifications: false
        },

        init() {
            this.calculateStats();
            this.loadSettings();
        },

        get filteredAlerts() {
            return this.alerts.filter(alert => {
                const severityMatch = this.severityFilter === 'all' || alert.severity === this.severityFilter;
                const typeMatch = this.typeFilter === 'all' || alert.alert_type === this.typeFilter;
                const statusMatch = this.statusFilter === 'all' || alert.status === this.statusFilter;
                return severityMatch && typeMatch && statusMatch;
            });
        },

        calculateStats() {
            this.stats.critical = this.alerts.filter(a => a.severity === 'critical' && a.status === 'active').length;
            this.stats.warnings = this.alerts.filter(a => a.severity === 'warning' && a.status === 'active').length;
            this.stats.info = this.alerts.filter(a => a.severity === 'info' && a.status === 'active').length;
            this.stats.resolved = this.alerts.filter(a => a.status === 'resolved').length;
        },

        filterAlerts() {
            // Handled by computed property
        },

        getTypeLabel(type) {
            const labels = {
                'budget': "{{ __('alerts.budget_type') }}",
                'performance': "{{ __('alerts.performance_type') }}",
                'system': "{{ __('alerts.system_type') }}",
                'campaign': "{{ __('alerts.campaign_type') }}"
            };
            return labels[type] || type;
        },

        formatDate(date) {
            if (!date) return '-';
            return new Date(date).toLocaleDateString('ar-SA', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        async markAsRead(alertId) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/${alertId}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    const alert = this.alerts.find(a => a.alert_id === alertId);
                    if (alert) alert.is_read = true;
                }
            } catch (error) {
                console.error('Failed to mark as read:', error);
            }
        },

        async markAllAsRead() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/read-all`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.alerts.forEach(alert => alert.is_read = true);
                }
            } catch (error) {
                console.error('Failed to mark all as read:', error);
            }
        },

        async resolveAlert(alertId) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/${alertId}/resolve`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    const alert = this.alerts.find(a => a.alert_id === alertId);
                    if (alert) {
                        alert.status = 'resolved';
                        this.calculateStats();
                    }
                }
            } catch (error) {
                console.error('Failed to resolve alert:', error);
            }
        },

        async dismissAlert(alertId) {
            if (!confirm("{{ __('alerts.confirm_dismiss') }}")) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/${alertId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.alerts = this.alerts.filter(a => a.alert_id !== alertId);
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to dismiss alert:', error);
            }
        },

        async performAction(alertId, action) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/${alertId}/action`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ action })
                });

                if (response.ok) {
                    alert("{{ __('alerts.action_executed', ['action' => '']) }}".replace(': ', ': ' + action));
                }
            } catch (error) {
                console.error('Failed to perform action:', error);
            }
        },

        async loadSettings() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/settings`);
                if (response.ok) {
                    const data = await response.json();
                    this.settings = { ...this.settings, ...data.settings };
                }
            } catch (error) {
                console.error('Failed to load settings:', error);
            }
        },

        async saveSettings() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/alerts/settings`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.settings)
                });

                if (response.ok) {
                    this.showSettingsModal = false;
                    alert("{{ __('alerts.settings_saved') }}");
                }
            } catch (error) {
                console.error('Failed to save settings:', error);
                alert("{{ __('alerts.settings_save_failed') }}");
            }
        }
    };
}
</script>
@endpush
