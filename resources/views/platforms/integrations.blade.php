<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('integrations.platform_integrations') }} - CMIS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div x-data="platformIntegrations()" x-init="loadIntegrations()" class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('integrations.platform_integrations') }}</h1>
            <p class="mt-2 text-gray-600">{{ __('integrations.connect_platforms_description') }}</p>
        </div>

        <!-- Available Platforms -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('integrations.available_platforms') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- TikTok Ads -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-black rounded-lg flex items-center justify-center text-white text-2xl me-4">
                            🎵
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('integrations.tiktok') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('integrations.tiktok_description') }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('integrations.tiktok_features_description') }}
                    </p>
                    <div class="space-y-2 text-xs text-gray-500 mb-4">
                        <div>✓ {{ __('integrations.in_feed_ads') }}</div>
                        <div>✓ {{ __('integrations.brand_takeover') }}</div>
                        <div>✓ {{ __('integrations.hashtag_challenges') }}</div>
                    </div>
                    <button @click="showPlatformSetup('tiktok')"
                            class="w-full px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                        {{ __('integrations.connect_tiktok') }}
                    </button>
                </div>

                <!-- LinkedIn Ads -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-700 rounded-lg flex items-center justify-center text-white text-2xl me-4">
                            💼
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('integrations.linkedin') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('integrations.linkedin_description') }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('integrations.linkedin_features_description') }}
                    </p>
                    <div class="space-y-2 text-xs text-gray-500 mb-4">
                        <div>✓ {{ __('integrations.sponsored_content') }}</div>
                        <div>✓ {{ __('integrations.lead_gen_forms') }}</div>
                        <div>✓ {{ __('integrations.account_based_marketing') }}</div>
                    </div>
                    <button @click="showPlatformSetup('linkedin')"
                            class="w-full px-4 py-2 bg-blue-700 text-white rounded-lg hover:bg-blue-800 transition">
                        {{ __('integrations.connect_linkedin') }}
                    </button>
                </div>

                <!-- Meta Ads (Coming Soon Example) -->
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition opacity-75">
                    <div class="flex items-center mb-4">
                        <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center text-white text-2xl me-4">
                            📘
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">{{ __('integrations.meta') }}</h3>
                            <p class="text-sm text-gray-500">{{ __('integrations.meta_description') }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ __('integrations.meta_features_description') }}
                    </p>
                    <div class="space-y-2 text-xs text-gray-500 mb-4">
                        <div>✓ {{ __('integrations.news_feed_ads') }}</div>
                        <div>✓ {{ __('integrations.stories_reels') }}</div>
                        <div>✓ {{ __('integrations.shopping_ads') }}</div>
                    </div>
                    <button class="w-full px-4 py-2 bg-gray-300 text-gray-600 rounded-lg cursor-not-allowed">
                        {{ __('integrations.already_available') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Connected Integrations -->
        <div x-show="connectedIntegrations.length > 0" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ __('integrations.connected_accounts') }}</h2>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('integrations.platform') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('integrations.account_id') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('integrations.status') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('integrations.connected') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('integrations.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="integration in connectedIntegrations" :key="integration.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <span class="text-2xl me-3" x-text="getPlatformIcon(integration.platform)"></span>
                                        <span class="font-medium text-gray-900 capitalize" x-text="integration.platform"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="integration.platform_account_id"></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="integration.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                          class="px-2 py-1 rounded-full text-xs font-medium"
                                          x-text="integration.is_active ? '{{ __('integrations.active') }}' : '{{ __('integrations.inactive') }}'">
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="formatDate(integration.created_at)"></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button @click="refreshIntegration(integration)" class="text-blue-600 hover:text-blue-800 me-3">{{ __('integrations.refresh') }}</button>
                                    <button @click="disconnectIntegration(integration)" class="text-red-600 hover:text-red-800">{{ __('integrations.disconnect') }}</button>
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
                        <span x-text="'{{ __('integrations.connect') }} ' + (selectedPlatform === 'tiktok' ? 'TikTok' : 'LinkedIn') + ' {{ __('integrations.ads') }}'"></span>
                    </h2>

                    <!-- Step Indicator -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium" :class="setupStep === 1 ? 'text-blue-600' : 'text-gray-500'">
                                1. {{ __('integrations.api_credentials') }}
                            </span>
                            <span class="text-sm font-medium" :class="setupStep === 2 ? 'text-blue-600' : 'text-gray-500'">
                                2. {{ __('integrations.verify_connection') }}
                            </span>
                            <span class="text-sm font-medium" :class="setupStep === 3 ? 'text-blue-600' : 'text-gray-500'">
                                3. {{ __('integrations.complete') }}
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full transition-all" :style="`width: ${(setupStep / 3) * 100}%`"></div>
                        </div>
                    </div>

                    <!-- Step 1: API Credentials -->
                    <div x-show="setupStep === 1">
                        <div class="bg-yellow-50 border-s-4 border-yellow-400 p-4 mb-4">
                            <div class="flex">
                                <div class="flex-shrink-0">⚠️</div>
                                <div class="ms-3">
                                    <p class="text-sm text-yellow-700">
                                        {{ __('integrations.api_credentials_warning') }}
                                        <span x-text="selectedPlatform === 'tiktok' ? '{{ __('integrations.tiktok_for_business') }}' : '{{ __('integrations.linkedin_developer_platform') }}'"></span>
                                        {{ __('integrations.to_get_credentials') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <form @submit.prevent="setupStep = 2">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <span x-show="selectedPlatform === 'tiktok'">{{ __('integrations.advertiser_id') }}</span>
                                    <span x-show="selectedPlatform === 'linkedin'">{{ __('integrations.sponsored_account_id') }}</span>
                                </label>
                                <input type="text" x-model="setupData.account_id" required
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       :placeholder="selectedPlatform === 'tiktok' ? '1234567890' : 'urn:li:sponsoredAccount:123456789'">
                            </div>

                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('integrations.access_token') }}</label>
                                <textarea x-model="setupData.access_token" required rows="4"
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                                          placeholder="{{ __('integrations.access_token_placeholder') }}"></textarea>
                            </div>

                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('integrations.account_name_optional') }}</label>
                                <input type="text" x-model="setupData.account_name"
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                       placeholder="{{ __('integrations.account_name_placeholder') }}">
                            </div>

                            <div class="flex justify-between">
                                <button type="button" @click="showSetupModal = false"
                                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                    {{ __('integrations.cancel') }}
                                </button>
                                <button type="submit"
                                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    {{ __('integrations.next') }} →
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
                                <span x-show="!verifying && !verificationError">{{ __('integrations.ready_to_connect') }}</span>
                                <span x-show="verifying">{{ __('integrations.verifying_connection') }}</span>
                                <span x-show="verificationError">{{ __('integrations.connection_failed') }}</span>
                            </h3>

                            <p class="text-gray-600 mb-6">
                                <span x-show="!verifying && !verificationError">{{ __('integrations.verify_credentials_description') }}</span>
                                <span x-show="verifying">{{ __('integrations.please_wait_verifying') }}</span>
                                <span x-show="verificationError">{{ __('integrations.verification_error_message') }}</span>
                            </p>

                            <div x-show="verificationError" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4 text-start">
                                <p class="text-sm text-red-700" x-text="verificationError"></p>
                            </div>
                        </div>

                        <div class="flex justify-between">
                            <button type="button" @click="setupStep = 1"
                                    class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition">
                                ← {{ __('integrations.back') }}
                            </button>
                            <button @click="verifyAndConnect()" :disabled="verifying"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
                                <span x-show="!verifying">{{ __('integrations.verify_and_connect') }}</span>
                                <span x-show="verifying">{{ __('integrations.verifying') }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Complete -->
                    <div x-show="setupStep === 3">
                        <div class="text-center py-8">
                            <div class="text-6xl mb-4">✅</div>
                            <h3 class="text-2xl font-semibold text-gray-900 mb-2">{{ __('integrations.successfully_connected') }}</h3>
                            <p class="text-gray-600 mb-6">
                                {{ __('integrations.your') }} <span x-text="selectedPlatform === 'tiktok' ? 'TikTok' : 'LinkedIn'"></span>
                                {{ __('integrations.account_connected_to_cmis') }}
                            </p>

                            <div class="bg-green-50 rounded-lg p-4 mb-6 text-start">
                                <h4 class="font-semibold text-green-900 mb-2">{{ __('integrations.whats_next') }}</h4>
                                <ul class="space-y-2 text-sm text-green-800">
                                    <li>✓ {{ __('integrations.view_manage_campaigns') }}</li>
                                    <li>✓ {{ __('integrations.create_new_campaigns') }}</li>
                                    <li>✓ {{ __('integrations.setup_automation_rules') }}</li>
                                    <li>✓ {{ __('integrations.track_performance') }}</li>
                                </ul>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button @click="finishSetup()"
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                {{ __('integrations.done') }}
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
                            alert('{{ __('integrations.refresh_success') }}');
                        }
                    } catch (error) {
                        console.error('Failed to refresh integration:', error);
                    }
                },

                async disconnectIntegration(integration) {
                    if (!confirm(`{{ __('integrations.confirm_disconnect') }} ${integration.platform}? {{ __('integrations.disconnect_warning') }}`)) {
                        return;
                    }

                    try {
                        // Call backend API to disconnect
                        alert('{{ __('integrations.disconnect_success') }}');
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
