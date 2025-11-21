<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Optimization - CMIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div x-data="campaignOptimization()" x-init="loadData()" class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Campaign Optimization</h1>
            <p class="mt-2 text-gray-600">Run automated optimization on your campaigns based on active rules</p>
        </div>

        <!-- Action Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Optimize All Campaigns -->
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Optimize All Campaigns</h3>
                        <p class="text-blue-100 text-sm mt-1">Apply active rules to all campaigns</p>
                    </div>
                    <div class="text-4xl">🚀</div>
                </div>
                <div class="mb-4">
                    <div class="text-2xl font-bold" x-text="activeCampaignsCount"></div>
                    <div class="text-sm text-blue-100">Active campaigns</div>
                </div>
                <button @click="optimizeAllCampaigns()" :disabled="optimizing"
                        class="w-full px-4 py-2 bg-white text-blue-600 rounded-lg font-medium hover:bg-blue-50 transition disabled:opacity-50">
                    <span x-show="!optimizing">Run Organization Optimization</span>
                    <span x-show="optimizing">Optimizing...</span>
                </button>
            </div>

            <!-- Active Rules Count -->
            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold">Active Automation Rules</h3>
                        <p class="text-purple-100 text-sm mt-1">Currently enabled rules</p>
                    </div>
                    <div class="text-4xl">⚙️</div>
                </div>
                <div class="mb-4">
                    <div class="text-2xl font-bold" x-text="activeRulesCount"></div>
                    <div class="text-sm text-purple-100">Rules monitoring your campaigns</div>
                </div>
                <a href="/automation/rules"
                   class="block w-full px-4 py-2 bg-white text-purple-600 rounded-lg font-medium hover:bg-purple-50 transition text-center">
                    Manage Rules
                </a>
            </div>
        </div>

        <!-- Latest Optimization Results -->
        <div x-show="lastOptimization" class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Latest Optimization Results</h2>

            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-blue-600" x-text="lastOptimization.optimized || 0"></div>
                    <div class="text-sm text-gray-600">Campaigns Optimized</div>
                </div>
                <div class="bg-yellow-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-yellow-600" x-text="lastOptimization.paused || 0"></div>
                    <div class="text-sm text-gray-600">Paused</div>
                </div>
                <div class="bg-green-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-green-600" x-text="lastOptimization.budget_adjusted || 0"></div>
                    <div class="text-sm text-gray-600">Budget Adjusted</div>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-purple-600" x-text="lastOptimization.notifications_sent || 0"></div>
                    <div class="text-sm text-gray-600">Notifications</div>
                </div>
                <div class="bg-red-50 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-red-600" x-text="lastOptimization.errors || 0"></div>
                    <div class="text-sm text-gray-600">Errors</div>
                </div>
            </div>

            <!-- Optimization Details -->
            <div x-show="lastOptimization.details && lastOptimization.details.length > 0">
                <h3 class="font-semibold text-gray-900 mb-3">Actions Taken</h3>
                <div class="space-y-2">
                    <template x-for="detail in (lastOptimization.details || []).slice(0, 5)" :key="detail.campaign_id">
                        <div class="bg-gray-50 rounded p-3">
                            <div class="font-medium text-gray-900" x-text="detail.campaign_name"></div>
                            <div class="text-sm text-gray-600 mt-1">
                                <template x-for="action in detail.actions" :key="action.action">
                                    <span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-xs mr-2"
                                          x-text="formatAction(action.action)">
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Execution History -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-900">Execution History</h2>
                <button @click="loadHistory()" class="text-blue-600 hover:text-blue-800 text-sm">
                    🔄 Refresh
                </button>
            </div>

            <!-- Loading State -->
            <div x-show="loadingHistory" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- History Table -->
            <div x-show="!loadingHistory" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Campaign</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Executed</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="item in history.slice(0, 10)" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="item.campaign_name"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium"
                                          :class="getActionColor(item.action)"
                                          x-text="formatAction(item.action)">
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500" x-text="item.details"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(item.executed_at)"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div x-show="history.length === 0" class="text-center py-8 text-gray-500">
                    No execution history yet. Run optimization to see results here.
                </div>
            </div>
        </div>

        <!-- Campaign-Specific Optimization (Optional) -->
        <div class="mt-8 bg-gray-100 rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Optimize Specific Campaign</h3>
            <div class="flex gap-3">
                <input type="text" x-model="specificCampaignId" placeholder="Enter campaign ID"
                       class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                <button @click="optimizeSpecificCampaign()" :disabled="!specificCampaignId || optimizing"
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Optimize Campaign
                </button>
            </div>
        </div>
    </div>

    <script>
        const orgId = '{{ $orgId ?? "your-org-id" }}';

        function campaignOptimization() {
            return {
                activeCampaignsCount: 0,
                activeRulesCount: 0,
                optimizing: false,
                loadingHistory: false,
                lastOptimization: null,
                history: [],
                specificCampaignId: '',

                async loadData() {
                    await Promise.all([
                        this.loadStats(),
                        this.loadHistory()
                    ]);
                },

                async loadStats() {
                    try {
                        // Load campaign count
                        const campaignsResponse = await fetch(`/api/orgs/${orgId}/campaigns`);
                        const campaignsData = await campaignsResponse.json();
                        this.activeCampaignsCount = campaignsData.campaigns?.filter(c => c.status === 'active').length || 0;

                        // Load rules count
                        const rulesResponse = await fetch(`/api/orgs/${orgId}/automation/rules`);
                        const rulesData = await rulesResponse.json();
                        this.activeRulesCount = rulesData.rules?.filter(r => r.is_active).length || 0;
                    } catch (error) {
                        console.error('Failed to load stats:', error);
                    }
                },

                async loadHistory() {
                    this.loadingHistory = true;
                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/history`);
                        const data = await response.json();
                        this.history = data.history || [];
                    } catch (error) {
                        console.error('Failed to load history:', error);
                        this.history = [];
                    } finally {
                        this.loadingHistory = false;
                    }
                },

                async optimizeAllCampaigns() {
                    if (!confirm('Run optimization on all campaigns? This will apply all active automation rules.')) {
                        return;
                    }

                    this.optimizing = true;
                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/optimize`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        });

                        const data = await response.json();

                        if (data.success) {
                            this.lastOptimization = data.results;
                            alert(`Optimization complete!\n\nOptimized: ${data.results.optimized}\nPaused: ${data.results.paused}\nBudget Adjusted: ${data.results.budget_adjusted}\nNotifications: ${data.results.notifications_sent}\nErrors: ${data.results.errors}`);
                            await this.loadHistory();
                        } else {
                            alert('Optimization failed: ' + (data.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Optimization error:', error);
                        alert('Failed to run optimization');
                    } finally {
                        this.optimizing = false;
                    }
                },

                async optimizeSpecificCampaign() {
                    if (!this.specificCampaignId) return;

                    this.optimizing = true;
                    try {
                        const response = await fetch(`/api/orgs/${orgId}/automation/optimize/${this.specificCampaignId}`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' }
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('Campaign optimized successfully!');
                            await this.loadHistory();
                            this.specificCampaignId = '';
                        } else {
                            alert('Optimization failed: ' + (data.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Optimization error:', error);
                        alert('Failed to optimize campaign');
                    } finally {
                        this.optimizing = false;
                    }
                },

                formatAction(action) {
                    const labels = {
                        'paused': 'Paused',
                        'budget_adjusted': 'Budget Adjusted',
                        'bid_adjusted': 'Bid Adjusted',
                        'notification_sent': 'Notification Sent'
                    };
                    return labels[action] || action;
                },

                getActionColor(action) {
                    const colors = {
                        'paused': 'bg-red-100 text-red-800',
                        'budget_adjusted': 'bg-green-100 text-green-800',
                        'bid_adjusted': 'bg-blue-100 text-blue-800',
                        'notification_sent': 'bg-purple-100 text-purple-800'
                    };
                    return colors[action] || 'bg-gray-100 text-gray-800';
                },

                formatDate(dateString) {
                    return new Date(dateString).toLocaleString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                }
            };
        }
    </script>
</body>
</html>
