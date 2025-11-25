<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Automation Rules - CMIS</title>
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
            <h1 class="text-3xl font-bold text-gray-900">Campaign Automation Rules</h1>
            <p class="mt-2 text-gray-600">Create automated rules to optimize your campaigns based on performance metrics</p>
        </div>

        <!-- Action Buttons -->
        <div class="mb-6 flex justify-between items-center">
            <div class="flex gap-3">
                <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    + Create New Rule
                </button>
                <button @click="loadTemplates()" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                    📋 Use Template
                </button>
            </div>
            <button @click="loadRules()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                🔄 Refresh
            </button>
        </div>

        <!-- Loading State -->
        <div x-show="loading" class="text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Loading automation rules...</p>
        </div>

        <!-- Rules List -->
        <div x-show="!loading" class="space-y-4">
            <!-- Empty State -->
            <div x-show="rules.length === 0" class="bg-white rounded-lg shadow p-12 text-center">
                <div class="text-6xl mb-4">🤖</div>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Automation Rules Yet</h3>
                <p class="text-gray-500 mb-6">Create your first automation rule to start optimizing campaigns automatically</p>
                <button @click="showCreateModal = true" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Your First Rule
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
                                      x-text="rule.is_active ? 'Active' : 'Inactive'">
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm" x-text="rule.description"></p>
                        </div>
                        <div class="flex gap-2 ml-4">
                            <button @click="toggleRule(rule)"
                                    :class="rule.is_active ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-green-100 text-green-700 hover:bg-green-200'"
                                    class="px-3 py-1 rounded text-sm font-medium transition">
                                <span x-text="rule.is_active ? 'Pause' : 'Activate'"></span>
                            </button>
                            <button @click="editRule(rule)" class="px-3 py-1 bg-blue-100 text-blue-700 rounded text-sm font-medium hover:bg-blue-200 transition">
                                Edit
                            </button>
                            <button @click="deleteRule(rule)" class="px-3 py-1 bg-red-100 text-red-700 rounded text-sm font-medium hover:bg-red-200 transition">
                                Delete
                            </button>
                        </div>
                    </div>

                    <!-- Rule Details -->
                    <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200">
                        <!-- Condition -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <div class="text-xs font-semibold text-blue-800 uppercase mb-2">Condition</div>
                            <div class="text-sm text-gray-900">
                                <span class="font-medium" x-text="formatMetric(rule.condition.metric)"></span>
                                <span x-text="rule.condition.operator" class="mx-2 font-bold"></span>
                                <span class="font-medium" x-text="formatValue(rule.condition.value, rule.condition.metric)"></span>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <div class="text-xs font-semibold text-green-800 uppercase mb-2">Action</div>
                            <div class="text-sm text-gray-900">
                                <span x-text="formatAction(rule.action.type)"></span>
                                <span x-show="rule.action.value" class="ml-1">
                                    (<span x-text="rule.action.value"></span><span x-show="['increase_budget', 'decrease_budget'].includes(rule.action.type)">%</span>)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="mt-4 flex justify-between text-xs text-gray-500">
                        <span>Created: <span x-text="formatDate(rule.created_at)"></span></span>
                        <span>Updated: <span x-text="formatDate(rule.updated_at)"></span></span>
                    </div>
                </div>
            </template>
        </div>

        <!-- Create/Edit Modal -->
        <div x-show="showCreateModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showCreateModal = false">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6" x-text="editingRule ? 'Edit Rule' : 'Create New Rule'"></h2>

                    <form @submit.prevent="saveRule()">
                        <!-- Rule Name -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rule Name</label>
                            <input type="text" x-model="formData.name" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="e.g., Pause High CPA Campaigns">
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                            <textarea x-model="formData.description" rows="2"
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                      placeholder="Describe what this rule does..."></textarea>
                        </div>

                        <!-- Condition -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                            <h3 class="text-sm font-semibold text-blue-900 uppercase mb-3">When (Condition)</h3>
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Metric</label>
                                    <select x-model="formData.condition.metric" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="cpa">Cost Per Acquisition (CPA)</option>
                                        <option value="roas">Return On Ad Spend (ROAS)</option>
                                        <option value="ctr">Click-Through Rate (CTR)</option>
                                        <option value="conversion_rate">Conversion Rate</option>
                                        <option value="spend">Daily Spend</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Operator</label>
                                    <select x-model="formData.condition.operator" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value=">">Greater Than (>)</option>
                                        <option value="<">Less Than (<)</option>
                                        <option value="=">Equals (=)</option>
                                        <option value=">=">Greater or Equal (>=)</option>
                                        <option value="<=">Less or Equal (<=)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Value</label>
                                    <input type="number" step="0.01" x-model="formData.condition.value" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="e.g., 50">
                                </div>
                            </div>
                        </div>

                        <!-- Action -->
                        <div class="mb-6 p-4 bg-green-50 rounded-lg">
                            <h3 class="text-sm font-semibold text-green-900 uppercase mb-3">Then (Action)</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Action Type</label>
                                    <select x-model="formData.action.type" required
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="pause_underperforming">Pause Campaign</option>
                                        <option value="increase_budget">Increase Budget</option>
                                        <option value="decrease_budget">Decrease Budget</option>
                                        <option value="adjust_bid">Adjust Bid</option>
                                        <option value="notify">Send Notification</option>
                                    </select>
                                </div>
                                <div x-show="['increase_budget', 'decrease_budget', 'adjust_bid'].includes(formData.action.type)">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Adjustment (%)</label>
                                    <input type="number" step="1" x-model="formData.action.value"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                           placeholder="e.g., 20">
                                </div>
                            </div>
                        </div>

                        <!-- Active Toggle -->
                        <div class="mb-6">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" x-model="formData.is_active" class="mr-2 w-5 h-5 text-blue-600">
                                <span class="text-sm font-medium text-gray-700">Activate this rule immediately</span>
                            </label>
                        </div>

                        <!-- Buttons -->
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="showCreateModal = false"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                <span x-text="editingRule ? 'Update Rule' : 'Create Rule'"></span>
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
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Rule Templates</h2>

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
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const orgId = '{{ $orgId ?? "your-org-id" }}'; // Pass from backend

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
                        alert('Failed to load automation rules');
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
                        alert('Failed to load rule templates');
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
                            alert(this.editingRule ? 'Rule updated successfully!' : 'Rule created successfully!');
                            this.showCreateModal = false;
                            this.resetForm();
                            await this.loadRules();
                        } else {
                            alert('Error: ' + (data.error || 'Failed to save rule'));
                        }
                    } catch (error) {
                        console.error('Failed to save rule:', error);
                        alert('Failed to save automation rule');
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
                    if (!confirm(`Are you sure you want to delete "${rule.name}"?`)) return;

                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/rules/${rule.id}`, {
                            method: 'DELETE'
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('Rule deleted successfully!');
                            await this.loadRules();
                        }
                    } catch (error) {
                        console.error('Failed to delete rule:', error);
                        alert('Failed to delete rule');
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
                        'cpa': 'CPA',
                        'roas': 'ROAS',
                        'ctr': 'CTR',
                        'conversion_rate': 'Conversion Rate',
                        'spend': 'Daily Spend'
                    };
                    return labels[metric] || metric;
                },

                formatAction(actionType) {
                    const labels = {
                        'pause_underperforming': 'Pause Campaign',
                        'increase_budget': 'Increase Budget',
                        'decrease_budget': 'Decrease Budget',
                        'adjust_bid': 'Adjust Bid',
                        'notify': 'Send Notification'
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
                    return new Date(dateString).toLocaleDateString('en-US', {
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
