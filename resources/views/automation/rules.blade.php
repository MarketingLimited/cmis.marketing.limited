<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('automation.automation_rules') }} - CMIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    @php
        $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    @endphp
    <div x-data="automationRules()" x-init="loadRules()" class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('automation.automation_rules') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('automation.rules_description') }}</p>
        </div>

        <!-- Action Buttons -->
        <div class="mb-6 flex justify-between items-center">
            <div class="flex gap-3">
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    + {{ __('automation.create_new_rule') }}
                </button>
                <button @click="loadTemplates()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    📋 {{ __('automation.use_template') }}
                </button>
            </div>
            <button @click="loadRules()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                🔄 {{ __('automation.refresh') }}
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">{{ __('automation.loading_rules') }}</p>
        </div>

        <!-- Rules List -->
        <div x-show="!loading" class="space-y-4">
            <!-- Empty State -->
            <div x-show="rules.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
                <div class="text-6xl mb-4">🤖</div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">{{ __('automation.no_rules_yet') }}</h3>
                <p class="text-gray-500 mb-6">{{ __('automation.no_rules_description') }}</p>
                <button @click="showCreateModal = true" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    {{ __('automation.create_first_rule') }}
                </button>
            </div>

            <!-- Rules Cards -->
            <template x-for="rule in rules" :key="rule.id">
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex justify-between items-start mb-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <h3 class="text-lg font-semibold text-gray-900" x-text="rule.name"></h3>
                                <span :class="rule.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                      class="px-2 py-1 rounded-full text-xs font-medium"
                                      x-text="rule.is_active ? '{{ __('automation.active') }}' : '{{ __('automation.inactive') }}'">
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm" x-text="rule.description"></p>
                        </div>
                        <div class="flex gap-2 ms-4">
                            <button @click="toggleRule(rule)"
                                    :class="rule.is_active ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
                                    class="px-3 py-1 rounded text-sm font-medium transition">
                                <span x-text="rule.is_active ? '{{ __('automation.pause') }}' : '{{ __('automation.activate') }}'"></span>
                            </button>
                            <button @click="editRule(rule)" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm font-medium hover:bg-blue-200 transition">
                                {{ __('automation.edit') }}
                            </button>
                            <button @click="deleteRule(rule)" class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm font-medium hover:bg-red-200 transition">
                                {{ __('automation.delete') }}
                            </button>
                        </div>
                    </div>

                    <!-- Rule Details -->
                    <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200">
                        <!-- Condition -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-xs font-semibold text-blue-800 uppercase mb-2">{{ __('automation.condition') }}</div>
                            <div class="text-sm text-gray-900">
                                <span class="font-medium" x-text="formatMetric(rule.condition.metric)"></span>
                                <span x-text="rule.condition.operator" class="mx-2 font-bold"></span>
                                <span class="font-medium" x-text="formatValue(rule.condition.value, rule.condition.metric)"></span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-xs font-semibold text-green-800 uppercase mb-2">{{ __('automation.action') }}</div>
                            <div class="text-sm text-gray-900">
                                <span x-text="formatAction(rule.action.type)"></span>
                                <span x-show="rule.action.value" class="ms-1">
                                    (<span x-text="rule.action.value"></span><span x-show="['increase_budget', 'decrease_budget'].includes(rule.action.type)">%</span>)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="mt-4 flex justify-between text-xs text-gray-500">
                        <span>{{ __('automation.created') }}: <span x-text="formatDate(rule.created_at)"></span></span>
                        <span>{{ __('automation.updated') }}: <span x-text="formatDate(rule.updated_at)"></span></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCreateModal = false">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6" x-text="editingRule ? '{{ __('automation.edit_rule') }}' : '{{ __('automation.create_new_rule_title') }}'"></h2>

                    <form @submit.prevent="saveRule()">
                        <!-- Rule Name -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('automation.rule_name') }}</label>
                            <input type="text" x-model="formData.name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="{{ __('automation.rule_name_placeholder') }}">
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('automation.description') }}</label>
                            <textarea x-model="formData.description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="{{ __('automation.description_placeholder') }}"></textarea>
                        </div>

                        <!-- Condition -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h3 class="text-sm font-semibold text-blue-900 uppercase mb-3">{{ __('automation.when_condition') }}</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('automation.metric') }}</label>
                                    <select x-model="formData.condition.metric" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="cpa">{{ __('automation.metric_cpa') }}</option>
                                        <option value="roas">{{ __('automation.metric_roas') }}</option>
                                        <option value="ctr">{{ __('automation.metric_ctr') }}</option>
                                        <option value="conversion_rate">{{ __('automation.metric_conversion_rate') }}</option>
                                        <option value="spend">{{ __('automation.metric_spend') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('automation.operator') }}</label>
                                    <select x-model="formData.condition.operator" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value=">">{{ __('automation.operator_gt') }}</option>
                                        <option value="<">{{ __('automation.operator_lt') }}</option>
                                        <option value="=">{{ __('automation.operator_eq') }}</option>
                                        <option value=">=">{{ __('automation.operator_gte') }}</option>
                                        <option value="<=">{{ __('automation.operator_lte') }}</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('automation.value') }}</label>
                                    <input type="number" step="0.01" x-model="formData.condition.value" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="50">
                                </div>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="mb-6 p-4 bg-green-50 rounded-lg">
                            <h3 class="text-sm font-semibold text-green-900 uppercase mb-3">{{ __('automation.then_action') }}</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('automation.action_type') }}</label>
                                    <select x-model="formData.action.type" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="pause_underperforming">{{ __('automation.action_pause_underperforming') }}</option>
                                        <option value="increase_budget">{{ __('automation.action_increase_budget') }}</option>
                                        <option value="decrease_budget">{{ __('automation.action_decrease_budget') }}</option>
                                        <option value="adjust_bid">{{ __('automation.action_adjust_bid') }}</option>
                                        <option value="notify">{{ __('automation.action_notify') }}</option>
                                    </select>
                                </div>
                                <div x-show="['increase_budget', 'decrease_budget', 'adjust_bid'].includes(formData.action.type)">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('automation.adjustment_percent') }}</label>
                                    <input type="number" step="1" x-model="formData.action.value"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="20">
                                </div>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="mb-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" x-model="formData.is_active" class="me-2 w-5 h-5 text-blue-600">
                                <span class="text-sm font-medium text-gray-700">{{ __('automation.activate_immediately') }}</span>
                            </label>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showCreateModal = false"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                {{ __('automation.cancel') }}
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <span x-text="editingRule ? '{{ __('automation.update_rule') }}' : '{{ __('automation.create_rule') }}'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Templates Modal -->
        <div x-show="showTemplatesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showTemplatesModal = false">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('automation.rule_templates') }}</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <template x-for="template in templates" :key="template.id">
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-blue-500 hover:shadow-md transition cursor-pointer"
                                 @click="useTemplate(template)">
                                <h3 class="font-semibold text-gray-900 mb-2" x-text="template.name"></h3>
                                <p class="text-sm text-gray-600 mb-3" x-text="template.description"></p>
                                <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                                    <span x-text="formatMetric(template.condition.metric)"></span>
                                    <span x-text="template.condition.operator"></span>
                                    <span x-text="template.condition.value"></span>
                                    →
                                    <span x-text="formatAction(template.action.type)"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button @click="showTemplatesModal = false"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                            {{ __('automation.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const orgId = '{{ $orgId ?? "your-org-id" }}';
        const translations = {
            ruleCreated: '{{ __('automation.rule_created') }}',
            ruleUpdated: '{{ __('automation.rule_updated') }}',
            ruleDeleted: '{{ __('automation.rule_deleted') }}',
            confirmDeleteRule: '{{ __('automation.confirm_delete_rule') }}',
            error: '{{ __('automation.error') }}',
            unknownError: '{{ __('automation.unknown_error') }}',
            failedSaveRule: '{{ __('automation.failed_save_rule') }}',
            failedLoadRules: '{{ __('automation.failed_load_rules') }}',
            failedLoadTemplates: '{{ __('automation.failed_load_templates') }}',
            failedDeleteRule: '{{ __('automation.failed_delete_rule') }}',
            metricCpa: '{{ __('automation.metric_cpa') }}',
            metricRoas: '{{ __('automation.metric_roas') }}',
            metricCtr: '{{ __('automation.metric_ctr') }}',
            metricConversionRate: '{{ __('automation.metric_conversion_rate') }}',
            metricSpend: '{{ __('automation.metric_spend') }}',
            actionPauseUnderperforming: '{{ __('automation.action_pause_underperforming') }}',
            actionIncreaseBudget: '{{ __('automation.action_increase_budget') }}',
            actionDecreaseBudget: '{{ __('automation.action_decrease_budget') }}',
            actionAdjustBid: '{{ __('automation.action_adjust_bid') }}',
            actionNotify: '{{ __('automation.action_notify') }}'
        };

        function automationRules() {
            return {
                loading: false,
                rules: [],
                templates: [],
                showCreateModal: false,
                showTemplatesModal: false,
                editingRule: null,
                formData: {
                    name: '',
                    description: '',
                    condition: {
                        metric: 'cpa',
                        operator: '>',
                        value: 50
                    },
                    action: {
                        type: 'pause_underperforming',
                        value: null
                    },
                    is_active: false
                },

                async loadRules() {
                    this.loading = true;
                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/rules`);
                        const data = await response.json();
                        this.rules = data.rules || [];
                    } catch (error) {
                        console.error('Failed to load rules:', error);
                        alert(translations.failedLoadRules);
                    } finally {
                        this.loading = false;
                    }
                },

                async loadTemplates() {
                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/rules/templates`);
                        const data = await response.json();
                        this.templates = data.templates || [];
                        this.showTemplatesModal = true;
                    } catch (error) {
                        console.error('Failed to load templates:', error);
                        alert(translations.failedLoadTemplates);
                    }
                },

                async saveRule() {
                    try {
                        const url = this.editingRule
                            ? `/api/orgs/${orgId}/automation/rules/${this.editingRule.id}`
                            : `/api/orgs/${orgId}/automation/rules`;

                        const method = this.editingRule ? 'PUT' : 'POST';

                        const response = await fetch(url, {
                            method: method,
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify(this.formData)
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert(this.editingRule ? translations.ruleUpdated : translations.ruleCreated);
                            this.showCreateModal = false;
                            this.resetForm();
                            await this.loadRules();
                        } else {
                            alert(`${translations.error}: ${data.error || translations.unknownError}`);
                        }
                    } catch (error) {
                        console.error('Failed to save rule:', error);
                        alert(translations.failedSaveRule);
                    }
                },

                async toggleRule(rule) {
                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/rules/${rule.id}`, {
                            method: 'PUT',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ is_active: !rule.is_active })
                        });

                        const data = await response.json();

                        if (data.success) {
                            await this.loadRules();
                        }
                    } catch (error) {
                        console.error('Failed to toggle rule:', error);
                    }
                },

                editRule(rule) {
                    this.editingRule = rule;
                    this.formData = {
                        name: rule.name,
                        description: rule.description || '',
                        condition: { ...rule.condition },
                        action: { ...rule.action },
                        is_active: rule.is_active
                    };
                    this.showCreateModal = true;
                },

                async deleteRule(rule) {
                    if (!confirm(`${translations.confirmDeleteRule} "${rule.name}"?`)) return;

                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/rules/${rule.id}`, {
                            method: 'DELETE'
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert(translations.ruleDeleted);
                            await this.loadRules();
                        }
                    } catch (error) {
                        console.error('Failed to delete rule:', error);
                        alert(translations.failedDeleteRule);
                    }
                },

                useTemplate(template) {
                    this.formData = {
                        name: template.name,
                        description: template.description,
                        condition: { ...template.condition },
                        action: { ...template.action },
                        is_active: false
                    };
                    this.showTemplatesModal = false;
                    this.showCreateModal = true;
                },

                resetForm() {
                    this.editingRule = null;
                    this.formData = {
                        name: '',
                        description: '',
                        condition: {
                            metric: 'cpa',
                            operator: '>',
                            value: 50
                        },
                        action: {
                            type: 'pause_underperforming',
                            value: null
                        },
                        is_active: false
                    };
                },

                formatMetric(metric) {
                    const labels = {
                        'cpa': translations.metricCpa,
                        'roas': translations.metricRoas,
                        'ctr': translations.metricCtr,
                        'conversion_rate': translations.metricConversionRate,
                        'spend': translations.metricSpend
                    };
                    return labels[metric] || metric;
                },

                formatAction(actionType) {
                    const labels = {
                        'pause_underperforming': translations.actionPauseUnderperforming,
                        'increase_budget': translations.actionIncreaseBudget,
                        'decrease_budget': translations.actionDecreaseBudget,
                        'adjust_bid': translations.actionAdjustBid,
                        'notify': translations.actionNotify
                    };
                    return labels[actionType] || actionType;
                },

                formatValue(value, metric) {
                    if (['ctr', 'conversion_rate'].includes(metric)) {
                        return (value * 100).toFixed(2) + '%';
                    }
                    if (metric === 'roas') {
                        return value.toFixed(2) + 'x';
                    }
                    if (['cpa', 'spend'].includes(metric)) {
                        return '$' + value.toFixed(2);
                    }
                    return value;
                },

                formatDate(dateString) {
                    return new Date(dateString).toLocaleDateString('{{ app()->getLocale() }}', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                }
            };
        }
    </script>
</body>
</html>
