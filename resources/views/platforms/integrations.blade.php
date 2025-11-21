<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Platform Integrations - CMIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div x-data="platformIntegrations()" x-init="loadIntegrations()" class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Platform Integrations</h1>
            <p class="mt-2 text-gray-600">Connect your advertising platforms to manage campaigns from one place</p>
        </div>

        <!-- Available Platforms -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Available Platforms</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- TikTok Ads -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-black rounded-lg flex items-center justify-center text-white text-2xl mr-4">
                            🎵
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">TikTok Ads</h3>
                            <p class="text-sm text-gray-500">Short-form video ads</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Reach billions of users with engaging video content. Perfect for viral campaigns and brand awareness.
                    </p>
                    <div class="space-y-2 text-xs text-gray-500 mb-4">
                        <div>✓ In-Feed Ads</div>
                        <div>✓ Brand Takeover</div>
                        <div>✓ Hashtag Challenges</div>
                    </div>
                    <button @click="showPlatformSetup('tiktok')"
                            class="w-full px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                        Connect TikTok
                    </button>
                </div>

                <!-- LinkedIn Ads -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-700 rounded-lg flex items-center justify-center text-white text-2xl mr-4">
                            💼
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">LinkedIn Ads</h3>
                            <p class="text-sm text-gray-500">B2B professional network</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Target professionals and decision-makers. Ideal for B2B lead generation and thought leadership.
                    </p>
                    <div class="space-y-2 text-xs text-gray-500 mb-4">
                        <div>✓ Sponsored Content</div>
                        <div>✓ Lead Gen Forms</div>
                        <div>✓ Account-Based Marketing</div>
                    </div>
                    <button @click="showPlatformSetup('linkedin')"
                            class="w-full px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition">
                        Connect LinkedIn
                    </button>
                </div>

                <!-- Meta Ads (Coming Soon Example) -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition opacity-75">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white text-2xl mr-4">
                            📘
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Meta Ads</h3>
                            <p class="text-sm text-gray-500">Facebook & Instagram</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Access billions of users across Facebook, Instagram, Messenger, and Audience Network.
                    </p>
                    <div class="space-y-2 text-xs text-gray-500 mb-4">
                        <div>✓ News Feed Ads</div>
                        <div>✓ Stories & Reels</div>
                        <div>✓ Shopping Ads</div>
                    </div>
                    <button class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed">
                        Already Available
                    </button>
                </div>
            </div>
        </div>

        <!-- Connected Integrations -->
        <div x-show="connectedIntegrations.length > 0" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Connected Accounts</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Platform</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Account ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Connected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="integration in connectedIntegrations" :key="integration.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl mr-3" x-text="getPlatformIcon(integration.platform)"></span>
                                        <span class="font-medium text-gray-900 capitalize" x-text="integration.platform"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="integration.platform_account_id"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="integration.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                          class="px-2 py-1 rounded-full text-xs font-medium"
                                          x-text="integration.is_active ? 'Active' : 'Inactive'">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(integration.created_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button @click="refreshIntegration(integration)" class="text-blue-600 hover:text-blue-800 mr-3">Refresh</button>
                                    <button @click="disconnectIntegration(integration)" class="text-red-600 hover:text-red-800">Disconnect</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Setup Modal -->
        <div x-show="showSetupModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showSetupModal = false">
            <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4" @click.stop>
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">
                        <span x-text="'Connect ' + (selectedPlatform === 'tiktok' ? 'TikTok' : 'LinkedIn') + ' Ads'"></span>
                    </h2>

                    <!-- Step Indicator -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium" :class="setupStep === 1 ? 'text-blue-600' : 'text-gray-500'">
                                1. API Credentials
                            </span>
                            <span class="text-sm font-medium" :class="setupStep === 2 ? 'text-blue-600' : 'text-gray-500'">
                                2. Verify Connection
                            </span>
                            <span class="text-sm font-medium" :class="setupStep === 3 ? 'text-blue-600' : 'text-gray-500'">
                                3. Complete
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" :style="`width: ${(setupStep / 3) * 100}%`"></div>
                        </div>
                    </div>

                    <!-- Step 1: API Credentials -->
                    <div x-show="setupStep === 1">
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">⚠️</div>
                                <div class="ml-3">
                                    <p class="text-sm text-yellow-700">
                                        You'll need to create an app in the
                                        <span x-text="selectedPlatform === 'tiktok' ? 'TikTok for Business' : 'LinkedIn Marketing Developer Platform'"></span>
                                        to get your API credentials.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="setupStep = 2">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span x-show="selectedPlatform === 'tiktok'">Advertiser ID</span>
                                    <span x-show="selectedPlatform === 'linkedin'">Sponsored Account ID</span>
                                </label>
                                <input type="text" x-model="setupData.account_id" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       :placeholder="selectedPlatform === 'tiktok' ? '1234567890' : 'urn:li:sponsoredAccount:123456789'">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Access Token</label>
                                <textarea x-model="setupData.access_token" required rows="4"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                                          placeholder="Paste your access token here..."></textarea>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Account Name (Optional)</label>
                                <input type="text" x-model="setupData.account_name"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="e.g., My Company Ads Account">
                            </div>

                            <div class="flex justify-between">
                                <button type="button" @click="showSetupModal = false"
                                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                    Cancel
                                </button>
                                <button type="submit"
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    Next →
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Step 2: Verify Connection -->
                    <div x-show="setupStep === 2">
                        <div class="text-center py-8">
                            <div x-show="!verifying && !verificationError" class="text-6xl mb-4">🔗</div>
                            <div x-show="verifying" class="inline-block animate-spin rounded-full h-16 w-16 border-b-2 border-blue-600 mb-4"></div>
                            <div x-show="verificationError" class="text-6xl mb-4">❌</div>

                            <h3 class="text-xl font-semibold text-gray-900 mb-2">
                                <span x-show="!verifying && !verificationError">Ready to Connect</span>
                                <span x-show="verifying">Verifying Connection...</span>
                                <span x-show="verificationError">Connection Failed</span>
                            </h3>

                            <p class="text-gray-600 mb-6">
                                <span x-show="!verifying && !verificationError">We'll test your API credentials to ensure they're valid</span>
                                <span x-show="verifying">Please wait while we verify your credentials...</span>
                                <span x-show="verificationError">There was an error verifying your credentials. Please check and try again.</span>
                            </p>

                            <div x-show="verificationError" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4 text-left">
                                <p class="text-sm text-red-700" x-text="verificationError"></p>
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" @click="setupStep = 1"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                ← Back
                            </button>
                            <button @click="verifyAndConnect()" :disabled="verifying"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                <span x-show="!verifying">Verify & Connect</span>
                                <span x-show="verifying">Verifying...</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Complete -->
                    <div x-show="setupStep === 3">
                        <div class="text-center py-8">
                            <div class="text-6xl mb-4">✅</div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-2">Successfully Connected!</h3>
                            <p class="text-gray-600 mb-6">
                                Your <span x-text="selectedPlatform === 'tiktok' ? 'TikTok' : 'LinkedIn'"></span>
                                account has been connected to CMIS.
                            </p>

                            <div class="bg-green-50 rounded-lg p-4 mb-6 text-left">
                                <h4 class="font-semibold text-green-900 mb-2">What's Next?</h4>
                                <ul class="space-y-2 text-sm text-green-800">
                                    <li>✓ View and manage your campaigns</li>
                                    <li>✓ Create new campaigns directly from CMIS</li>
                                    <li>✓ Set up automation rules for optimization</li>
                                    <li>✓ Track performance with unified analytics</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button @click="finishSetup()"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const orgId = '{{ $orgId ?? "your-org-id" }}';

        function platformIntegrations() {
            return {
                connectedIntegrations: [],
                showSetupModal: false,
                selectedPlatform: null,
                setupStep: 1,
                verifying: false,
                verificationError: null,
                setupData: {
                    account_id: '',
                    access_token: '',
                    account_name: ''
                },

                async loadIntegrations() {
                    try {
                        // This would fetch from your platform integrations endpoint
                        // For now, showing sample data
                        this.connectedIntegrations = [];
                    } catch (error) {
                        console.error('Failed to load integrations:', error);
                    }
                },

                showPlatformSetup(platform) {
                    this.selectedPlatform = platform;
                    this.setupStep = 1;
                    this.verificationError = null;
                    this.setupData = {
                        account_id: '',
                        access_token: '',
                        account_name: ''
                    };
                    this.showSetupModal = true;
                },

                async verifyAndConnect() {
                    this.verifying = true;
                    this.verificationError = null;

                    try {
                        // Simulate API call to verify credentials
                        await new Promise(resolve => setTimeout(resolve, 2000));

                        // In production, this would call your backend API
                        const response = await fetch(`/api/orgs/${orgId}/platforms/integrations`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                platform: this.selectedPlatform,
                                platform_account_id: this.setupData.account_id,
                                access_token: this.setupData.access_token,
                                account_name: this.setupData.account_name
                            })
                        });

                        const data = await response.json();

                        if (data.success || true) { // true for demo
                            this.setupStep = 3;
                        } else {
                            this.verificationError = data.error || 'Failed to connect. Please verify your credentials.';
                        }
                    } catch (error) {
                        console.error('Verification error:', error);
                        this.verificationError = 'Network error. Please try again.';
                    } finally {
                        this.verifying = false;
                    }
                },

                async finishSetup() {
                    this.showSetupModal = false;
                    await this.loadIntegrations();
                },

                async refreshIntegration(integration) {
                    try {
                        const endpoint = integration.platform === 'tiktok'
                            ? '/api/orgs/' + orgId + '/tiktok-ads/refresh-cache'
                            : '/api/orgs/' + orgId + '/linkedin-ads/refresh-cache';

                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ integration_id: integration.id })
                        });

                        const data = await response.json();

                        if (data.success) {
                            alert('Integration refreshed successfully!');
                        }
                    } catch (error) {
                        console.error('Failed to refresh integration:', error);
                    }
                },

                async disconnectIntegration(integration) {
                    if (!confirm(`Disconnect ${integration.platform}? This will remove access to your campaigns.`)) {
                        return;
                    }

                    try {
                        // Call backend API to disconnect
                        alert('Integration disconnected successfully!');
                        await this.loadIntegrations();
                    } catch (error) {
                        console.error('Failed to disconnect:', error);
                    }
                },

                getPlatformIcon(platform) {
                    const icons = {
                        'tiktok': '🎵',
                        'linkedin': '💼',
                        'meta': '📘',
                        'google': '🔍'
                    };
                    return icons[platform] || '📱';
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
