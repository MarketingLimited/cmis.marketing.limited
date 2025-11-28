@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('automation.page_title'))
@section('page-subtitle', __('automation.page_subtitle'))

@section('content')
<div x-data="automationManager()" x-init="init()">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-indigo-100 text-sm mb-1">{{ __('automation.active_rules') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.activeRules"></p>
                </div>
                <i class="fas fa-robot text-5xl text-indigo-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm mb-1">{{ __('automation.today_actions') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.todayActions"></p>
                </div>
                <i class="fas fa-bolt text-5xl text-green-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm mb-1">{{ __('automation.auto_savings') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.autoSavings.toLocaleString()"></p>
                </div>
                <i class="fas fa-piggy-bank text-5xl text-blue-300 opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm mb-1">{{ __('automation.success_rate') }}</p>
                    <p class="text-3xl font-bold" x-text="stats.successRate + '%'"></p>
                </div>
                <i class="fas fa-check-circle text-5xl text-purple-300 opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Filters and Actions -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex gap-3">
            <select x-model="statusFilter" @change="filterRules"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">{{ __('automation.all_statuses') }}</option>
                <option value="active">{{ __('automation.active') }}</option>
                <option value="paused">{{ __('automation.paused') }}</option>
                <option value="draft">{{ __('automation.draft') }}</option>
            </select>

            <select x-model="typeFilter" @change="filterRules"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">{{ __('automation.all_types') }}</option>
                <option value="budget">{{ __('automation.budget') }}</option>
                <option value="performance">{{ __('automation.performance') }}</option>
                <option value="schedule">{{ __('automation.schedule') }}</option>
                <option value="alert">{{ __('automation.alert') }}</option>
            </select>
        </div>

        <button @click="showCreateModal = true"
                class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
            <i class="fas fa-plus me-2"></i>
            {{ __('automation.new_rule') }}
        </button>
    </div>

    <!-- Automation Rules Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
        <template x-for="rule in filteredRules" :key="rule.rule_id">
            <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden">
                <!-- Header -->
                <div class="p-6 border-b">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-1" x-text="rule.rule_name"></h3>
                            <p class="text-sm text-gray-600" x-text="getRuleTypeLabel(rule.rule_type)"></p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" :checked="rule.status === 'active'" @change="toggleRule(rule.rule_id)" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>

                <!-- Conditions & Actions -->
                <div class="p-6">
                    <!-- Condition -->
                    <div class="mb-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-filter text-indigo-600 text-xs me-2"></i>
                            {{ __('automation.condition') }}
                        </h4>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-700" x-text="rule.condition_description"></p>
                        </div>
                    </div>

                    <!-- Action -->
                    <div class="mb-4">
                        <h4 class="text-sm font-bold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-bolt text-yellow-600 text-xs me-2"></i>
                            {{ __('automation.action') }}
                        </h4>
                        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 p-3 rounded-lg">
                            <p class="text-sm text-gray-700" x-text="rule.action_description"></p>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-2 mb-4 text-xs">
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-gray-600 mb-1">{{ __('automation.execution_count') }}</p>
                            <p class="font-bold text-gray-900" x-text="rule.execution_count || 0"></p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-gray-600 mb-1">{{ __('automation.last_executed') }}</p>
                            <p class="font-bold text-gray-900" x-text="rule.last_executed ? formatDateShort(rule.last_executed) : '-'"></p>
                        </div>
                        <div class="bg-gray-50 p-2 rounded text-center">
                            <p class="text-gray-600 mb-1">{{ __('automation.success_rate') }}</p>
                            <p class="font-bold text-gray-900" x-text="(rule.success_rate || 0) + '%'"></p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2 pt-4 border-t">
                        <a :href="`/orgs/{{ $currentOrg }}/automation/rules/${rule.rule_id}`"
                           class="flex-1 bg-indigo-50 text-indigo-600 text-center py-2 rounded-lg font-medium hover:bg-indigo-100 transition text-sm">
                            <i class="fas fa-eye me-2"></i>
                            {{ __('automation.details') }}
                        </a>
                        <a :href="`/orgs/{{ $currentOrg }}/automation/rules/${rule.rule_id}/edit`"
                           class="bg-gray-50 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition text-sm">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button @click="deleteRule(rule.rule_id)"
                                class="bg-red-50 text-red-600 px-4 py-2 rounded-lg hover:bg-red-100 transition text-sm">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- Empty State -->
    <template x-if="filteredRules.length === 0">
        <div class="bg-white rounded-xl shadow-sm p-12 text-center">
            <i class="fas fa-robot text-gray-300 text-6xl mb-4"></i>
            <h3 class="text-xl font-bold text-gray-900 mb-2">{{ __('automation.no_rules') }}</h3>
            <p class="text-gray-600 mb-6">{{ __('automation.no_rules_empty_description') }}</p>
            <button @click="showCreateModal = true"
                    class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-3 rounded-lg font-medium hover:shadow-lg transition">
                <i class="fas fa-plus me-2"></i>
                {{ __('automation.create_new_rule') }}
            </button>
        </div>
    </template>

    <!-- Create Modal -->
    <div x-show="showCreateModal" @click.away="showCreateModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50" x-cloak>
        <div class="bg-white rounded-2xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h3 class="text-2xl font-bold text-gray-900 mb-4">{{ __('automation.create_new_rule_title') }}</h3>
            <form @submit.prevent="createRule">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('automation.rule_name') }}</label>
                        <input type="text" x-model="newRule.name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('automation.action_type') }}</label>
                        <select x-model="newRule.type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('automation.all_types') }}</option>
                            <option value="budget">{{ __('automation.rule_type_budget') }}</option>
                            <option value="performance">{{ __('automation.rule_type_performance') }}</option>
                            <option value="schedule">{{ __('automation.rule_type_schedule') }}</option>
                            <option value="alert">{{ __('automation.rule_type_alert') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('automation.when_condition') }}</label>
                        <textarea x-model="newRule.condition" rows="2" required placeholder="{{ __('automation.rule_name_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('automation.then_action') }}</label>
                        <textarea x-model="newRule.action" rows="2" required placeholder="{{ __('automation.description_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <button type="submit"
                                class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-lg font-medium hover:bg-indigo-700 transition">
                            {{ __('automation.create_rule') }}
                        </button>
                        <button type="button" @click="showCreateModal = false"
                                class="flex-1 bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium hover:bg-gray-300 transition">
                            {{ __('automation.cancel') }}
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
function automationManager() {
    return {
        rules: @json($rules ?? []),
        statusFilter: 'all',
        typeFilter: 'all',
        showCreateModal: false,
        stats: {
            activeRules: 0,
            todayActions: 0,
            autoSavings: 0,
            successRate: 0
        },
        newRule: {
            name: '',
            type: '',
            condition: '',
            action: ''
        },

        init() {
            this.calculateStats();
        },

        get filteredRules() {
            return this.rules.filter(rule => {
                const statusMatch = this.statusFilter === 'all' || rule.status === this.statusFilter;
                const typeMatch = this.typeFilter === 'all' || rule.rule_type === this.typeFilter;
                return statusMatch && typeMatch;
            });
        },

        calculateStats() {
            this.stats.activeRules = this.rules.filter(r => r.status === 'active').length;
            this.stats.todayActions = this.rules.reduce((sum, r) => sum + (r.today_executions || 0), 0);
            this.stats.autoSavings = this.rules.reduce((sum, r) => sum + (r.total_savings || 0), 0);

            const successfulRules = this.rules.filter(r => r.success_rate >= 80).length;
            this.stats.successRate = this.rules.length > 0
                ? Math.round((successfulRules / this.rules.length) * 100)
                : 0;
        },

        filterRules() {
            // Handled by computed property
        },

        getRuleTypeLabel(type) {
            const labels = {
                'budget': '{{ __('automation.rule_type_budget') }}',
                'performance': '{{ __('automation.rule_type_performance') }}',
                'schedule': '{{ __('automation.rule_type_schedule') }}',
                'alert': '{{ __('automation.rule_type_alert') }}'
            };
            return labels[type] || type;
        },

        formatDateShort(date) {
            if (!date) return '-';
            const d = new Date(date);
            const locale = '{{ app()->getLocale() }}' === 'ar' ? 'ar-SA' : 'en-US';
            return d.toLocaleDateString(locale, { month: 'short', day: 'numeric' });
        },

        async createRule() {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/automation/rules`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(this.newRule)
                });

                if (response.ok) {
                    const data = await response.json();
                    this.rules.unshift(data.rule);
                    this.showCreateModal = false;
                    this.newRule = { name: '', type: '', condition: '', action: '' };
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to create rule:', error);
                alert('{{ __('automation.rule_create_failed') }}');
            }
        },

        async toggleRule(ruleId) {
            try {
                const rule = this.rules.find(r => r.rule_id === ruleId);
                const newStatus = rule.status === 'active' ? 'paused' : 'active';

                const response = await fetch(`/orgs/{{ $currentOrg }}/automation/rules/${ruleId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ status: newStatus })
                });

                if (response.ok) {
                    rule.status = newStatus;
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to toggle rule:', error);
            }
        },

        async deleteRule(ruleId) {
            if (!confirm('{{ __('automation.rule_delete_confirm') }}')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/automation/rules/${ruleId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.rules = this.rules.filter(r => r.rule_id !== ruleId);
                    this.calculateStats();
                }
            } catch (error) {
                console.error('Failed to delete rule:', error);
                alert('{{ __('automation.failed_delete_rule') }}');
            }
        }
    };
}
</script>
@endpush
