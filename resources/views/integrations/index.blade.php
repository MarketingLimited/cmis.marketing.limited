@extends('layouts.admin')

@section('title', 'التكاملات')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div x-data="integrationsManager()" x-init="init()">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">إدارة التكاملات</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">ربط وإدارة التكاملات مع منصات التسويق الرقمي</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">إجمالي التكاملات</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.total"></p>
                </div>
                <i class="fas fa-plug text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">متصلة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.connected"></p>
                </div>
                <i class="fas fa-check-circle text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">غير متصلة</p>
                    <p class="text-3xl font-bold mt-2" x-text="stats.disconnected"></p>
                </div>
                <i class="fas fa-times-circle text-4xl opacity-50"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm opacity-90">آخر مزامنة</p>
                    <p class="text-lg font-bold mt-2" x-text="stats.lastSync"></p>
                </div>
                <i class="fas fa-sync text-4xl opacity-50"></i>
            </div>
        </div>
    </div>

    <!-- Integration Platforms -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <template x-for="platform in platforms" :key="platform.id">
            <x-ui.card>
                <div class="relative">
                    <!-- Status Badge -->
                    <div class="absolute top-0 left-0">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold"
                              :class="platform.connected ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'">
                            <i :class="platform.connected ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="ml-1"></i>
                            <span x-text="platform.connected ? 'متصل' : 'غير متصل'"></span>
                        </span>
                    </div>

                    <!-- Platform Icon & Name -->
                    <div class="text-center pt-8 pb-4">
                        <div class="w-20 h-20 mx-auto rounded-full flex items-center justify-center mb-4"
                             :style="'background: ' + platform.color + '20'">
                            <i :class="platform.icon" class="text-5xl" :style="'color: ' + platform.color"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white" x-text="platform.name"></h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="platform.description"></p>
                    </div>

                    <!-- Connection Info (if connected) -->
                    <div x-show="platform.connected" class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">الحساب:</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="platform.account"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">آخر مزامنة:</span>
                                <span class="font-medium text-gray-900 dark:text-white" x-text="platform.lastSync"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">الحالة:</span>
                                <span class="px-2 py-1 rounded text-xs font-semibold"
                                      :class="platform.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                                      x-text="platform.status === 'active' ? 'نشط' : 'معلق'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex gap-2">
                        <template x-if="!platform.connected">
                            <x-ui.button @click="connectPlatform(platform.id)" class="flex-1" icon="fas fa-link">
                                ربط الحساب
                            </x-ui.button>
                        </template>

                        <template x-if="platform.connected">
                            <>
                                <x-ui.button @click="syncPlatform(platform.id)" variant="success" class="flex-1" icon="fas fa-sync">
                                    مزامنة
                                </x-ui.button>
                                <x-ui.button @click="testConnection(platform.id)" variant="secondary" icon="fas fa-vial">
                                    اختبار
                                </x-ui.button>
                                <x-ui.button @click="disconnectPlatform(platform.id)" variant="danger" icon="fas fa-unlink">
                                    فصل
                                </x-ui.button>
                            </>
                        </template>
                    </div>

                    <!-- Sync History (if connected) -->
                    <div x-show="platform.connected" class="mt-4">
                        <button @click="platform.showHistory = !platform.showHistory"
                                class="text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400 w-full text-center">
                            <i class="fas fa-history ml-1"></i>
                            <span x-text="platform.showHistory ? 'إخفاء السجل' : 'عرض سجل المزامنة'"></span>
                        </button>

                        <div x-show="platform.showHistory" class="mt-3 space-y-2" x-transition>
                            <template x-for="sync in platform.syncHistory" :key="sync.id">
                                <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs">
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 ml-2"></i>
                                        <span x-text="sync.message"></span>
                                    </div>
                                    <span class="text-gray-500" x-text="sync.time"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </x-ui.card>
        </template>

    </div>

    <!-- Recent Activity -->
    <x-ui.card title="النشاط الأخير" class="mt-8">
        <div class="space-y-3">
            <template x-for="activity in recentActivity" :key="activity.id">
                <div class="flex items-start p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                         :class="{
                             'bg-green-100 text-green-600': activity.type === 'connected',
                             'bg-blue-100 text-blue-600': activity.type === 'synced',
                             'bg-red-100 text-red-600': activity.type === 'disconnected',
                             'bg-yellow-100 text-yellow-600': activity.type === 'error'
                         }">
                        <i :class="activity.icon" class="text-lg"></i>
                    </div>
                    <div class="mr-3 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="activity.message"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="activity.time"></p>
                    </div>
                </div>
            </template>
        </div>
    </x-ui.card>

</div>

<!-- Connection Modal -->
<x-ui.modal name="connect-platform-modal" title="ربط المنصة" max-width="md">
    <div x-data="{ step: 1 }">
        <div class="text-center py-6">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-link text-4xl text-blue-600"></i>
            </div>
            <h3 class="text-lg font-semibold mb-2">جاري توجيهك إلى صفحة التفويض...</h3>
            <p class="text-sm text-gray-600">سيتم فتح نافذة جديدة للمصادقة على الاتصال</p>
        </div>

        <x-slot name="footer">
            <x-ui.button type="button" variant="secondary" @click="closeModal('connect-platform-modal')">
                إلغاء
            </x-ui.button>
        </x-slot>
    </div>
</x-ui.modal>

@endsection

@push('scripts')
<script>
function integrationsManager() {
    return {
        stats: {
            total: 0,
            connected: 0,
            disconnected: 0,
            lastSync: 'منذ ساعة'
        },
        platforms: [],
        recentActivity: [],

        async init() {
            await this.fetchPlatforms();
            await this.fetchActivity();
        },

        async fetchPlatforms() {
            try {
                // TODO: Backend Controller Needed - IntegrationController
                // This page requires a new controller for platform integrations management
                // Required API endpoints:
                // - GET /api/integrations - List all platform integrations with status
                // - POST /api/integrations/{platform}/connect - Connect to platform (OAuth)
                // - DELETE /api/integrations/{platform}/disconnect - Disconnect platform
                // - POST /api/integrations/{platform}/sync - Manual sync trigger
                // - GET /api/integrations/{platform}/history - Get sync history
                // - GET /api/integrations/{platform}/settings - Get platform settings
                // - PUT /api/integrations/{platform}/settings - Update platform settings
                // - GET /api/integrations/activity - Get recent integration activity

                // Simulated data until backend is implemented
                this.platforms = [
                    {
                        id: 'meta',
                        name: 'Meta Ads',
                        description: 'Facebook & Instagram Ads',
                        icon: 'fab fa-meta',
                        color: '#0866FF',
                        connected: true,
                        account: 'marketing@company.com',
                        lastSync: 'منذ ساعة',
                        status: 'active',
                        showHistory: false,
                        syncHistory: [
                            { id: 1, message: 'تمت مزامنة 15 حملة', time: 'منذ ساعة' },
                            { id: 2, message: 'تمت مزامنة 230 إعلان', time: 'منذ 2 ساعة' }
                        ]
                    },
                    {
                        id: 'google',
                        name: 'Google Ads',
                        description: 'Google Search & Display',
                        icon: 'fab fa-google',
                        color: '#4285F4',
                        connected: true,
                        account: 'ads@company.com',
                        lastSync: 'منذ 30 دقيقة',
                        status: 'active',
                        showHistory: false,
                        syncHistory: [
                            { id: 1, message: 'تمت مزامنة 8 حملات', time: 'منذ 30 دقيقة' }
                        ]
                    },
                    {
                        id: 'tiktok',
                        name: 'TikTok Ads',
                        description: 'TikTok for Business',
                        icon: 'fab fa-tiktok',
                        color: '#000000',
                        connected: false,
                        account: null,
                        lastSync: null,
                        status: null,
                        showHistory: false,
                        syncHistory: []
                    },
                    {
                        id: 'linkedin',
                        name: 'LinkedIn Ads',
                        description: 'LinkedIn Campaign Manager',
                        icon: 'fab fa-linkedin',
                        color: '#0A66C2',
                        connected: false,
                        account: null,
                        lastSync: null,
                        status: null,
                        showHistory: false,
                        syncHistory: []
                    },
                    {
                        id: 'x',
                        name: 'X Ads',
                        description: 'X (Twitter) Advertising',
                        icon: 'fab fa-x-twitter',
                        color: '#000000',
                        connected: false,
                        account: null,
                        lastSync: null,
                        status: null,
                        showHistory: false,
                        syncHistory: []
                    },
                    {
                        id: 'woocommerce',
                        name: 'WooCommerce',
                        description: 'WooCommerce Store Integration',
                        icon: 'fab fa-wordpress',
                        color: '#96588A',
                        connected: true,
                        account: 'store.company.com',
                        lastSync: 'منذ 3 ساعات',
                        status: 'active',
                        showHistory: false,
                        syncHistory: [
                            { id: 1, message: 'تمت مزامنة 45 منتج', time: 'منذ 3 ساعات' }
                        ]
                    }
                ];

                this.stats.total = this.platforms.length;
                this.stats.connected = this.platforms.filter(p => p.connected).length;
                this.stats.disconnected = this.platforms.filter(p => !p.connected).length;

                // Replace with actual API call
                // const response = await fetch('/api/integrations');
                // this.platforms = await response.json();
            } catch (error) {
                console.error('Error fetching platforms:', error);
                window.notify('فشل تحميل المنصات', 'error');
            }
        },

        async fetchActivity() {
            this.recentActivity = [
                { id: 1, type: 'synced', icon: 'fas fa-sync', message: 'تمت مزامنة Meta Ads بنجاح', time: 'منذ ساعة' },
                { id: 2, type: 'synced', icon: 'fas fa-sync', message: 'تمت مزامنة Google Ads بنجاح', time: 'منذ ساعتين' },
                { id: 3, type: 'connected', icon: 'fas fa-check', message: 'تم ربط WooCommerce بنجاح', time: 'منذ يوم' },
                { id: 4, type: 'error', icon: 'fas fa-exclamation-triangle', message: 'فشل الاتصال بـ LinkedIn', time: 'منذ يومين' }
            ];
        },

        async connectPlatform(platformId) {
            try {
                window.notify('جاري فتح نافذة المصادقة...', 'info');

                // TODO: Implement OAuth flow for platform connection
                // This should redirect to the platform's OAuth URL
                // window.location.href = `/api/integrations/${platformId}/connect`;
                // The callback will handle the OAuth response and store tokens
                //
                // OAuth callback should:
                // 1. Receive authorization code
                // 2. Exchange for access token
                // 3. Store tokens securely in database
                // 4. Redirect back to integrations page with success message

                // Simulate OAuth flow for demo
                openModal('connect-platform-modal');

                setTimeout(() => {
                    closeModal('connect-platform-modal');
                    window.notify('تم ربط المنصة بنجاح!', 'success');

                    // Update platform status
                    const platform = this.platforms.find(p => p.id === platformId);
                    if (platform) {
                        platform.connected = true;
                        platform.account = 'user@example.com';
                        platform.lastSync = 'الآن';
                        platform.status = 'active';
                        this.stats.connected++;
                        this.stats.disconnected--;
                    }
                }, 2000);
            } catch (error) {
                console.error('Error connecting platform:', error);
                window.notify('فشل ربط المنصة', 'error');
            }
        },

        async syncPlatform(platformId) {
            try {
                window.notify('جاري المزامنة...', 'info');

                // TODO: Implement manual sync API call
                // const response = await fetch(`/api/integrations/${platformId}/sync`, {
                //     method: 'POST',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     }
                // });
                //
                // if (!response.ok) throw new Error('Sync failed');
                //
                // const result = await response.json();
                // // result.campaigns_synced, result.ads_synced, result.last_sync_time

                // Simulate sync
                setTimeout(() => {
                    window.notify('تمت المزامنة بنجاح!', 'success');

                    const platform = this.platforms.find(p => p.id === platformId);
                    if (platform) {
                        platform.lastSync = 'الآن';
                    }
                }, 1500);
            } catch (error) {
                console.error('Error syncing platform:', error);
                window.notify('فشل المزامنة', 'error');
            }
        },

        async testConnection(platformId) {
            try {
                window.notify('جاري اختبار الاتصال...', 'info');

                // TODO: Implement connection test API call
                // const response = await fetch(`/api/integrations/${platformId}/test`, {
                //     method: 'POST',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     }
                // });
                //
                // if (!response.ok) throw new Error('Connection test failed');

                setTimeout(() => {
                    window.notify('الاتصال يعمل بشكل صحيح!', 'success');
                }, 1000);
            } catch (error) {
                console.error('Error testing connection:', error);
                window.notify('فشل اختبار الاتصال', 'error');
            }
        },

        async disconnectPlatform(platformId) {
            if (!confirm('هل أنت متأكد من فصل هذه المنصة؟ سيتم إيقاف جميع عمليات المزامنة.')) return;

            try {
                window.notify('جاري فصل المنصة...', 'info');

                // TODO: Implement disconnect API call
                // const response = await fetch(`/api/integrations/${platformId}/disconnect`, {
                //     method: 'DELETE',
                //     headers: {
                //         'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                //         'Accept': 'application/json'
                //     }
                // });
                //
                // if (!response.ok) throw new Error('Failed to disconnect');
                // This should:
                // 1. Revoke OAuth tokens
                // 2. Delete stored credentials
                // 3. Stop all sync jobs for this platform

                setTimeout(() => {
                    window.notify('تم فصل المنصة بنجاح', 'success');

                    const platform = this.platforms.find(p => p.id === platformId);
                    if (platform) {
                        platform.connected = false;
                        platform.account = null;
                        platform.lastSync = null;
                        platform.status = null;
                        this.stats.connected--;
                        this.stats.disconnected++;
                    }
                }, 1000);
            } catch (error) {
                console.error('Error disconnecting platform:', error);
                window.notify('فشل فصل المنصة', 'error');
            }
        }
    };
}
</script>
@endpush
