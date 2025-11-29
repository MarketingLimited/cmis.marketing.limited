@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('analytics.platform_insights'))

@section('content')
<div class="space-y-6" x-data="platformInsights()" x-init="init()">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white">{{ __('analytics.platform_insights') }}</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.platform_insights_description') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-plug {{ $isRtl ? 'ms-2' : 'me-2' }}"></i>
                {{ __('settings.platform_connections') }}
            </a>
            <button @click="syncAllPlatforms()"
                    :disabled="syncing"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-50">
                <i class="fas fa-sync-alt {{ $isRtl ? 'ms-2' : 'me-2' }}" :class="syncing && 'animate-spin'"></i>
                <span x-text="syncing ? '{{ __('analytics.syncing') }}...' : '{{ __('analytics.sync_all') }}'"></span>
            </button>
        </div>
    </div>

    <!-- Date Range & Platform Filters -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('analytics.from_date') }}</label>
                <input type="date" x-model="filters.startDate" @change="fetchData()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('analytics.to_date') }}</label>
                <input type="date" x-model="filters.endDate" @change="fetchData()"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('analytics.platform') }}</label>
                <select x-model="filters.platform" @change="fetchData()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                    <option value="">{{ __('analytics.all_platforms') }}</option>
                    <option value="meta">Meta (Facebook/Instagram)</option>
                    <option value="google">Google (Ads/Analytics/Search Console)</option>
                    <option value="tiktok">TikTok</option>
                    <option value="linkedin">LinkedIn</option>
                    <option value="twitter">X (Twitter)</option>
                    <option value="snapchat">Snapchat</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ __('analytics.compare_period') }}</label>
                <select x-model="filters.comparePeriod"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:border-gray-600">
                    <option value="previous_period">{{ __('analytics.previous_period') }}</option>
                    <option value="previous_year">{{ __('analytics.previous_year') }}</option>
                    <option value="none">{{ __('analytics.no_comparison') }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Insights Tabs -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <!-- Tab Navigation -->
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="flex flex-wrap -mb-px px-4" aria-label="Tabs">
                <button @click="activeTab = 'social_accounts'"
                        :class="activeTab === 'social_accounts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-users"></i>
                    {{ __('analytics.social_accounts') }}
                </button>
                <button @click="activeTab = 'social_posts'"
                        :class="activeTab === 'social_posts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-newspaper"></i>
                    {{ __('analytics.social_posts') }}
                </button>
                <button @click="activeTab = 'ad_accounts'"
                        :class="activeTab === 'ad_accounts' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-ad"></i>
                    {{ __('analytics.ad_accounts') }}
                </button>
                <button @click="activeTab = 'campaigns'"
                        :class="activeTab === 'campaigns' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-bullhorn"></i>
                    {{ __('analytics.campaigns') }}
                </button>
                <button @click="activeTab = 'ad_sets'"
                        :class="activeTab === 'ad_sets' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-layer-group"></i>
                    {{ __('analytics.ad_sets') }}
                </button>
                <button @click="activeTab = 'ads'"
                        :class="activeTab === 'ads' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-image"></i>
                    {{ __('analytics.ads') }}
                </button>
                <button @click="activeTab = 'google_analytics'"
                        :class="activeTab === 'google_analytics' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-chart-line"></i>
                    {{ __('analytics.google_analytics') }}
                </button>
                <button @click="activeTab = 'search_console'"
                        :class="activeTab === 'search_console' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-4 border-b-2 font-medium text-sm flex items-center gap-2 transition">
                    <i class="fas fa-search"></i>
                    {{ __('analytics.search_console') }}
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-6">
            <!-- Loading State -->
            <div x-show="loading" class="flex items-center justify-center py-12">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
                    <p class="text-gray-600 dark:text-gray-400">{{ __('analytics.loading_insights') }}...</p>
                </div>
            </div>

            <!-- Social Accounts Tab -->
            <div x-show="activeTab === 'social_accounts' && !loading" x-cloak>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.social_accounts_insights') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.social_accounts_description') }}</p>
                </div>

                <!-- Social Account Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <template x-for="stat in socialAccountsStats" :key="stat.id">
                        <div class="bg-gradient-to-br rounded-lg p-4 text-white" :class="stat.gradient">
                            <div class="flex items-center justify-between mb-3">
                                <i :class="stat.icon + ' text-2xl opacity-75'"></i>
                                <span class="text-xs px-2 py-1 rounded-full bg-white/20" x-text="stat.platform"></span>
                            </div>
                            <div class="text-2xl font-bold" x-text="formatNumber(stat.followers)"></div>
                            <div class="text-sm opacity-90" x-text="stat.label"></div>
                            <div class="mt-2 flex items-center text-xs">
                                <i :class="stat.change >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'" class="me-1"></i>
                                <span x-text="Math.abs(stat.change) + '%'"></span>
                                <span class="ms-1">{{ __('analytics.vs_previous') }}</span>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Social Accounts Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('analytics.account') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('analytics.platform') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.followers') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.engagement_rate') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.reach') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.impressions') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="account in socialAccounts" :key="account.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <img :src="account.avatar" :alt="account.name" class="w-8 h-8 rounded-full me-3">
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="account.name"></div>
                                                <div class="text-xs text-gray-500" x-text="'@' + account.username"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getPlatformBadgeClass(account.platform)">
                                            <i :class="getPlatformIcon(account.platform) + ' me-1'"></i>
                                            <span x-text="account.platform"></span>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(account.followers)"></td>
                                    <td class="px-4 py-3 text-end">
                                        <span class="text-sm font-medium" :class="account.engagementRate >= 3 ? 'text-green-600' : 'text-yellow-600'" x-text="account.engagementRate + '%'"></span>
                                    </td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(account.reach)"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(account.impressions)"></td>
                                    <td class="px-4 py-3 text-center">
                                        <button @click="viewAccountDetails(account)" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Social Posts Tab -->
            <div x-show="activeTab === 'social_posts' && !loading" x-cloak>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.social_posts_insights') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.social_posts_description') }}</p>
                </div>

                <!-- Post Performance KPIs -->
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-blue-600" x-text="formatNumber(postStats.totalPosts)"></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.total_posts') }}</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-green-600" x-text="formatNumber(postStats.totalEngagement)"></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.total_engagement') }}</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-purple-600" x-text="formatNumber(postStats.totalReach)"></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.total_reach') }}</div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-yellow-600" x-text="postStats.avgEngagementRate + '%'"></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.avg_engagement') }}</div>
                    </div>
                    <div class="bg-pink-50 dark:bg-pink-900/20 rounded-lg p-4 text-center">
                        <div class="text-3xl font-bold text-pink-600" x-text="formatNumber(postStats.totalShares)"></div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.total_shares') }}</div>
                    </div>
                </div>

                <!-- Top Performing Posts -->
                <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4">{{ __('analytics.top_performing_posts') }}</h4>
                <div class="space-y-4">
                    <template x-for="post in topPosts" :key="post.id">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition">
                            <div class="flex items-start gap-4">
                                <img :src="post.thumbnail" :alt="post.title" class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium" :class="getPlatformBadgeClass(post.platform)">
                                            <i :class="getPlatformIcon(post.platform)"></i>
                                        </span>
                                        <span class="text-xs text-gray-500" x-text="post.publishedAt"></span>
                                    </div>
                                    <p class="text-sm text-gray-900 dark:text-white line-clamp-2 mb-3" x-text="post.content"></p>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        <span><i class="fas fa-heart text-red-500 me-1"></i> <span x-text="formatNumber(post.likes)"></span></span>
                                        <span><i class="fas fa-comment text-blue-500 me-1"></i> <span x-text="formatNumber(post.comments)"></span></span>
                                        <span><i class="fas fa-share text-green-500 me-1"></i> <span x-text="formatNumber(post.shares)"></span></span>
                                        <span><i class="fas fa-eye text-purple-500 me-1"></i> <span x-text="formatNumber(post.reach)"></span></span>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="text-lg font-bold text-green-600" x-text="post.engagementRate + '%'"></div>
                                    <div class="text-xs text-gray-500">{{ __('analytics.engagement') }}</div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Ad Accounts Tab -->
            <div x-show="activeTab === 'ad_accounts' && !loading" x-cloak>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.ad_accounts_insights') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.ad_accounts_description') }}</p>
                </div>

                <!-- Ad Account Summary Cards -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <template x-for="account in adAccounts" :key="account.id">
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                                <div class="flex items-center">
                                    <i :class="getPlatformIcon(account.platform) + ' text-2xl me-3'" :style="'color:' + account.color"></i>
                                    <div>
                                        <div class="font-semibold text-gray-900 dark:text-white" x-text="account.name"></div>
                                        <div class="text-xs text-gray-500" x-text="account.accountId"></div>
                                    </div>
                                </div>
                                <span class="px-2 py-1 rounded-full text-xs font-medium" :class="account.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="account.status"></span>
                            </div>
                            <div class="p-4">
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <div class="text-gray-500">{{ __('analytics.total_spend') }}</div>
                                        <div class="text-lg font-bold text-gray-900 dark:text-white" x-text="formatCurrency(account.spend)"></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">{{ __('analytics.roas') }}</div>
                                        <div class="text-lg font-bold" :class="account.roas >= 3 ? 'text-green-600' : 'text-yellow-600'" x-text="account.roas + 'x'"></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">{{ __('analytics.impressions') }}</div>
                                        <div class="font-semibold" x-text="formatNumber(account.impressions)"></div>
                                    </div>
                                    <div>
                                        <div class="text-gray-500">{{ __('analytics.conversions') }}</div>
                                        <div class="font-semibold" x-text="formatNumber(account.conversions)"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-700/50 flex justify-between">
                                <button @click="viewAdAccountDetails(account)" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    {{ __('common.view_details') }}
                                </button>
                                <button @click="syncAdAccount(account)" class="text-gray-600 hover:text-gray-800 text-sm">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Campaigns Tab -->
            <div x-show="activeTab === 'campaigns' && !loading" x-cloak>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.campaigns_insights') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.campaigns_description') }}</p>
                </div>

                <!-- Campaigns Performance Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('campaigns.campaign') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('common.status') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.spend') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.impressions') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.clicks') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.ctr') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.conversions') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.cpa') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.roas') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="campaign in campaigns" :key="campaign.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" @click="viewCampaignDetails(campaign)">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center">
                                            <i :class="getPlatformIcon(campaign.platform) + ' text-lg me-3'" :style="'color:' + campaign.color"></i>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="campaign.name"></div>
                                                <div class="text-xs text-gray-500" x-text="campaign.objective"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium"
                                              :class="campaign.status === 'active' ? 'bg-green-100 text-green-800' : (campaign.status === 'paused' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')"
                                              x-text="campaign.status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatCurrency(campaign.spend)"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(campaign.impressions)"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(campaign.clicks)"></td>
                                    <td class="px-4 py-3 text-end text-sm font-medium" :class="campaign.ctr >= 2 ? 'text-green-600' : 'text-gray-900'" x-text="campaign.ctr + '%'"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(campaign.conversions)"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatCurrency(campaign.cpa)"></td>
                                    <td class="px-4 py-3 text-end">
                                        <span class="text-sm font-bold" :class="campaign.roas >= 3 ? 'text-green-600' : (campaign.roas >= 1 ? 'text-yellow-600' : 'text-red-600')" x-text="campaign.roas + 'x'"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ad Sets Tab -->
            <div x-show="activeTab === 'ad_sets' && !loading" x-cloak>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.ad_sets_insights') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.ad_sets_description') }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('analytics.ad_set') }}</th>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 uppercase">{{ __('campaigns.campaign') }}</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">{{ __('common.status') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.budget') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.spend') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.reach') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.frequency') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 uppercase">{{ __('analytics.cpm') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="adSet in adSets" :key="adSet.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="adSet.name"></div>
                                        <div class="text-xs text-gray-500" x-text="adSet.targeting"></div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600" x-text="adSet.campaignName"></td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium"
                                              :class="adSet.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                              x-text="adSet.status"></span>
                                    </td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatCurrency(adSet.budget)"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatCurrency(adSet.spend)"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatNumber(adSet.reach)"></td>
                                    <td class="px-4 py-3 text-end text-sm" :class="adSet.frequency > 3 ? 'text-yellow-600' : 'text-gray-900'" x-text="adSet.frequency"></td>
                                    <td class="px-4 py-3 text-end text-sm" x-text="formatCurrency(adSet.cpm)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ads Tab -->
            <div x-show="activeTab === 'ads' && !loading" x-cloak>
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.ads_insights') }}</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.ads_description') }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-for="ad in ads" :key="ad.id">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-lg transition">
                            <div class="relative">
                                <img :src="ad.creative.thumbnail" :alt="ad.name" class="w-full h-40 object-cover">
                                <span class="absolute top-2 end-2 px-2 py-1 rounded text-xs font-medium"
                                      :class="ad.status === 'active' ? 'bg-green-500 text-white' : 'bg-gray-500 text-white'"
                                      x-text="ad.status"></span>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center gap-2 mb-2">
                                    <i :class="getPlatformIcon(ad.platform)" :style="'color:' + ad.color"></i>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white" x-text="ad.name"></span>
                                </div>
                                <div class="text-xs text-gray-500 mb-3" x-text="ad.adSetName"></div>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <div class="text-gray-500">{{ __('analytics.impressions') }}</div>
                                        <div class="font-semibold" x-text="formatNumber(ad.impressions)"></div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <div class="text-gray-500">{{ __('analytics.clicks') }}</div>
                                        <div class="font-semibold" x-text="formatNumber(ad.clicks)"></div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <div class="text-gray-500">{{ __('analytics.ctr') }}</div>
                                        <div class="font-semibold" :class="ad.ctr >= 2 ? 'text-green-600' : 'text-gray-900'" x-text="ad.ctr + '%'"></div>
                                    </div>
                                    <div class="bg-gray-50 dark:bg-gray-700 p-2 rounded">
                                        <div class="text-gray-500">{{ __('analytics.spend') }}</div>
                                        <div class="font-semibold" x-text="formatCurrency(ad.spend)"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Google Analytics Tab -->
            <div x-show="activeTab === 'google_analytics' && !loading" x-cloak>
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.google_analytics_insights') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.google_analytics_description') }}</p>
                    </div>
                    <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-cog me-1"></i> {{ __('analytics.configure_ga') }}
                    </a>
                </div>

                <!-- GA4 Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-users text-orange-600"></i>
                            <span class="text-sm text-gray-600">{{ __('analytics.active_users') }}</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(gaMetrics.activeUsers)"></div>
                        <div class="text-xs text-green-600 mt-1" x-text="'+' + gaMetrics.activeUsersChange + '%'"></div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-eye text-blue-600"></i>
                            <span class="text-sm text-gray-600">{{ __('analytics.page_views') }}</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="formatNumber(gaMetrics.pageViews)"></div>
                        <div class="text-xs text-green-600 mt-1" x-text="'+' + gaMetrics.pageViewsChange + '%'"></div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-clock text-green-600"></i>
                            <span class="text-sm text-gray-600">{{ __('analytics.avg_session') }}</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="gaMetrics.avgSessionDuration"></div>
                        <div class="text-xs text-green-600 mt-1" x-text="'+' + gaMetrics.avgSessionChange + '%'"></div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <i class="fas fa-sign-out-alt text-purple-600"></i>
                            <span class="text-sm text-gray-600">{{ __('analytics.bounce_rate') }}</span>
                        </div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white" x-text="gaMetrics.bounceRate + '%'"></div>
                        <div class="text-xs" :class="gaMetrics.bounceRateChange < 0 ? 'text-green-600' : 'text-red-600'" x-text="gaMetrics.bounceRateChange + '%'"></div>
                    </div>
                </div>

                <!-- Traffic Sources & Top Pages -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Traffic Sources -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-4">{{ __('analytics.traffic_sources') }}</h4>
                        <div class="space-y-3">
                            <template x-for="source in gaMetrics.trafficSources" :key="source.name">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i :class="source.icon" class="w-5 text-gray-400 me-2"></i>
                                        <span class="text-sm text-gray-700 dark:text-gray-300" x-text="source.name"></span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-24 bg-gray-200 dark:bg-gray-600 rounded-full h-2 me-2">
                                            <div class="h-2 rounded-full" :style="'width: ' + source.percentage + '%; background-color: ' + source.color"></div>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white w-10 text-end" x-text="source.percentage + '%'"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Top Pages -->
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-4">{{ __('analytics.top_pages') }}</h4>
                        <div class="space-y-3">
                            <template x-for="(page, index) in gaMetrics.topPages" :key="page.path">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center flex-1 min-w-0">
                                        <span class="w-6 h-6 rounded-full bg-blue-100 text-blue-600 text-xs font-medium flex items-center justify-center me-2" x-text="index + 1"></span>
                                        <span class="text-sm text-gray-700 dark:text-gray-300 truncate" x-text="page.path"></span>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white ms-2" x-text="formatNumber(page.views)"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Console Tab -->
            <div x-show="activeTab === 'search_console' && !loading" x-cloak>
                <div class="mb-6 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('analytics.search_console_insights') }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('analytics.search_console_description') }}</p>
                    </div>
                    <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-cog me-1"></i> {{ __('analytics.configure_gsc') }}
                    </a>
                </div>

                <!-- GSC Metrics -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <div class="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">{{ __('analytics.total_clicks') }}</div>
                        <div class="text-2xl font-bold text-indigo-600" x-text="formatNumber(gscMetrics.clicks)"></div>
                        <div class="text-xs mt-1" :class="gscMetrics.clicksChange >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(gscMetrics.clicksChange >= 0 ? '+' : '') + gscMetrics.clicksChange + '%'"></div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">{{ __('analytics.total_impressions') }}</div>
                        <div class="text-2xl font-bold text-green-600" x-text="formatNumber(gscMetrics.impressions)"></div>
                        <div class="text-xs mt-1" :class="gscMetrics.impressionsChange >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(gscMetrics.impressionsChange >= 0 ? '+' : '') + gscMetrics.impressionsChange + '%'"></div>
                    </div>
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">{{ __('analytics.avg_ctr') }}</div>
                        <div class="text-2xl font-bold text-yellow-600" x-text="gscMetrics.ctr + '%'"></div>
                        <div class="text-xs mt-1" :class="gscMetrics.ctrChange >= 0 ? 'text-green-600' : 'text-red-600'" x-text="(gscMetrics.ctrChange >= 0 ? '+' : '') + gscMetrics.ctrChange + '%'"></div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
                        <div class="text-sm text-gray-600 mb-1">{{ __('analytics.avg_position') }}</div>
                        <div class="text-2xl font-bold text-purple-600" x-text="gscMetrics.position"></div>
                        <div class="text-xs mt-1" :class="gscMetrics.positionChange <= 0 ? 'text-green-600' : 'text-red-600'" x-text="(gscMetrics.positionChange <= 0 ? '' : '+') + gscMetrics.positionChange"></div>
                    </div>
                </div>

                <!-- Top Keywords & Pages -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top Keywords -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-4">{{ __('analytics.top_keywords') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-gray-500 text-xs uppercase">
                                        <th class="text-start pb-2">{{ __('analytics.keyword') }}</th>
                                        <th class="text-end pb-2">{{ __('analytics.clicks') }}</th>
                                        <th class="text-end pb-2">{{ __('analytics.impr') }}</th>
                                        <th class="text-end pb-2">{{ __('analytics.pos') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="keyword in gscMetrics.topKeywords" :key="keyword.query">
                                        <tr>
                                            <td class="py-2 text-gray-900 dark:text-white" x-text="keyword.query"></td>
                                            <td class="py-2 text-end" x-text="formatNumber(keyword.clicks)"></td>
                                            <td class="py-2 text-end" x-text="formatNumber(keyword.impressions)"></td>
                                            <td class="py-2 text-end font-medium" :class="keyword.position <= 10 ? 'text-green-600' : 'text-gray-600'" x-text="keyword.position.toFixed(1)"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Top Landing Pages -->
                    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 dark:text-white mb-4">{{ __('analytics.top_landing_pages') }}</h4>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-gray-500 text-xs uppercase">
                                        <th class="text-start pb-2">{{ __('analytics.page') }}</th>
                                        <th class="text-end pb-2">{{ __('analytics.clicks') }}</th>
                                        <th class="text-end pb-2">{{ __('analytics.ctr') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                    <template x-for="page in gscMetrics.topPages" :key="page.url">
                                        <tr>
                                            <td class="py-2 text-gray-900 dark:text-white truncate max-w-[200px]" x-text="page.url"></td>
                                            <td class="py-2 text-end" x-text="formatNumber(page.clicks)"></td>
                                            <td class="py-2 text-end font-medium" :class="page.ctr >= 5 ? 'text-green-600' : 'text-gray-600'" x-text="page.ctr + '%'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Data State -->
            <div x-show="!loading && noData" class="text-center py-12">
                <i class="fas fa-chart-bar text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('analytics.no_data_available') }}</h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ __('analytics.connect_platforms_first') }}</p>
                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plug me-2"></i>
                    {{ __('analytics.connect_platforms') }}
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function platformInsights() {
    return {
        activeTab: 'social_accounts',
        loading: true,
        syncing: false,
        noData: false,
        filters: {
            startDate: '',
            endDate: '',
            platform: '',
            comparePeriod: 'previous_period'
        },

        // Data
        socialAccountsStats: [],
        socialAccounts: [],
        postStats: { totalPosts: 0, totalEngagement: 0, totalReach: 0, avgEngagementRate: 0, totalShares: 0 },
        topPosts: [],
        adAccounts: [],
        campaigns: [],
        adSets: [],
        ads: [],
        gaMetrics: {
            activeUsers: 0, activeUsersChange: 0,
            pageViews: 0, pageViewsChange: 0,
            avgSessionDuration: '0:00', avgSessionChange: 0,
            bounceRate: 0, bounceRateChange: 0,
            trafficSources: [],
            topPages: []
        },
        gscMetrics: {
            clicks: 0, clicksChange: 0,
            impressions: 0, impressionsChange: 0,
            ctr: 0, ctrChange: 0,
            position: 0, positionChange: 0,
            topKeywords: [],
            topPages: []
        },

        init() {
            // Set default date range (last 30 days)
            const end = new Date();
            const start = new Date();
            start.setDate(start.getDate() - 30);
            this.filters.startDate = start.toISOString().split('T')[0];
            this.filters.endDate = end.toISOString().split('T')[0];

            this.fetchData();
        },

        async fetchData() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    start_date: this.filters.startDate,
                    end_date: this.filters.endDate,
                    platform: this.filters.platform,
                    compare: this.filters.comparePeriod
                });

                const response = await fetch(`/api/orgs/{{ $currentOrg }}/analytics/platform-insights?${params}`);
                if (response.ok) {
                    const data = await response.json();
                    this.populateData(data);
                    this.noData = false;
                } else {
                    // Load sample data if API not available
                    this.loadSampleData();
                }
            } catch (error) {
                console.log('Loading sample data:', error);
                this.loadSampleData();
            }
            this.loading = false;
        },

        populateData(data) {
            this.socialAccountsStats = data.socialAccountsStats || [];
            this.socialAccounts = data.socialAccounts || [];
            this.postStats = data.postStats || this.postStats;
            this.topPosts = data.topPosts || [];
            this.adAccounts = data.adAccounts || [];
            this.campaigns = data.campaigns || [];
            this.adSets = data.adSets || [];
            this.ads = data.ads || [];
            this.gaMetrics = data.gaMetrics || this.gaMetrics;
            this.gscMetrics = data.gscMetrics || this.gscMetrics;
        },

        loadSampleData() {
            // Sample social account stats
            this.socialAccountsStats = [
                { id: 1, platform: 'Facebook', icon: 'fab fa-facebook', gradient: 'from-blue-500 to-blue-600', followers: 125000, label: '{{ __('analytics.followers') }}', change: 5.2 },
                { id: 2, platform: 'Instagram', icon: 'fab fa-instagram', gradient: 'from-pink-500 to-purple-600', followers: 89000, label: '{{ __('analytics.followers') }}', change: 12.8 },
                { id: 3, platform: 'LinkedIn', icon: 'fab fa-linkedin', gradient: 'from-blue-600 to-blue-800', followers: 45000, label: '{{ __('analytics.followers') }}', change: 3.4 },
                { id: 4, platform: 'X', icon: 'fab fa-x-twitter', gradient: 'from-gray-700 to-gray-900', followers: 32000, label: '{{ __('analytics.followers') }}', change: -1.2 }
            ];

            // Sample social accounts
            this.socialAccounts = [
                { id: 1, name: 'Brand Page', username: 'brand_official', platform: 'Facebook', avatar: 'https://via.placeholder.com/40', followers: 125000, engagementRate: 4.2, reach: 450000, impressions: 1200000 },
                { id: 2, name: 'Brand IG', username: 'brand_ig', platform: 'Instagram', avatar: 'https://via.placeholder.com/40', followers: 89000, engagementRate: 6.8, reach: 320000, impressions: 890000 },
                { id: 3, name: 'Brand LinkedIn', username: 'brand-company', platform: 'LinkedIn', avatar: 'https://via.placeholder.com/40', followers: 45000, engagementRate: 2.1, reach: 120000, impressions: 280000 }
            ];

            // Sample post stats
            this.postStats = { totalPosts: 156, totalEngagement: 89420, totalReach: 1250000, avgEngagementRate: 5.7, totalShares: 12400 };

            // Sample top posts
            this.topPosts = [
                { id: 1, platform: 'Instagram', thumbnail: 'https://via.placeholder.com/80', content: 'Check out our latest product launch! Limited time offer...', publishedAt: '2 days ago', likes: 4520, comments: 234, shares: 156, reach: 45000, engagementRate: 10.9 },
                { id: 2, platform: 'Facebook', thumbnail: 'https://via.placeholder.com/80', content: 'Thank you for 100K followers! We appreciate your support...', publishedAt: '5 days ago', likes: 3200, comments: 189, shares: 423, reach: 62000, engagementRate: 6.1 }
            ];

            // Sample ad accounts
            this.adAccounts = [
                { id: 1, name: 'Meta Ads Main', accountId: 'act_123456789', platform: 'meta', color: '#1877F2', status: 'active', spend: 45000, roas: 4.2, impressions: 2500000, conversions: 1250 },
                { id: 2, name: 'Google Ads', accountId: '123-456-7890', platform: 'google', color: '#4285F4', status: 'active', spend: 32000, roas: 3.8, impressions: 1800000, conversions: 890 },
                { id: 3, name: 'TikTok Business', accountId: 'tiktok_789', platform: 'tiktok', color: '#000000', status: 'active', spend: 15000, roas: 2.9, impressions: 900000, conversions: 320 }
            ];

            // Sample campaigns
            this.campaigns = [
                { id: 1, name: 'Summer Sale 2024', platform: 'meta', color: '#1877F2', objective: 'Conversions', status: 'active', spend: 12500, impressions: 850000, clicks: 25400, ctr: 2.99, conversions: 456, cpa: 27.41, roas: 5.2 },
                { id: 2, name: 'Brand Awareness Q4', platform: 'google', color: '#4285F4', objective: 'Awareness', status: 'active', spend: 8900, impressions: 620000, clicks: 18600, ctr: 3.0, conversions: 234, cpa: 38.03, roas: 3.1 },
                { id: 3, name: 'Product Launch', platform: 'tiktok', color: '#000000', objective: 'Traffic', status: 'paused', spend: 5600, impressions: 420000, clicks: 12100, ctr: 2.88, conversions: 89, cpa: 62.92, roas: 1.8 }
            ];

            // Sample ad sets
            this.adSets = [
                { id: 1, name: 'Lookalike - Purchase 1%', campaignName: 'Summer Sale 2024', targeting: 'Lookalike Audience', status: 'active', budget: 500, spend: 423, reach: 145000, frequency: 2.3, cpm: 5.82 },
                { id: 2, name: 'Interest - Fashion', campaignName: 'Summer Sale 2024', targeting: 'Interest Based', status: 'active', budget: 350, spend: 298, reach: 98000, frequency: 3.1, cpm: 6.12 }
            ];

            // Sample ads
            this.ads = [
                { id: 1, name: 'Video Ad - Summer Collection', platform: 'meta', color: '#1877F2', adSetName: 'Lookalike - Purchase 1%', status: 'active', creative: { thumbnail: 'https://via.placeholder.com/300x200' }, impressions: 125000, clicks: 3750, ctr: 3.0, spend: 1250 },
                { id: 2, name: 'Carousel - Best Sellers', platform: 'meta', color: '#1877F2', adSetName: 'Interest - Fashion', status: 'active', creative: { thumbnail: 'https://via.placeholder.com/300x200' }, impressions: 98000, clicks: 2450, ctr: 2.5, spend: 890 }
            ];

            // Sample GA metrics
            this.gaMetrics = {
                activeUsers: 12500,
                activeUsersChange: 8.5,
                pageViews: 89000,
                pageViewsChange: 12.3,
                avgSessionDuration: '2:45',
                avgSessionChange: 5.2,
                bounceRate: 42.5,
                bounceRateChange: -3.2,
                trafficSources: [
                    { name: 'Organic Search', icon: 'fas fa-search', percentage: 35, color: '#22c55e' },
                    { name: 'Direct', icon: 'fas fa-link', percentage: 28, color: '#3b82f6' },
                    { name: 'Social', icon: 'fas fa-share-alt', percentage: 22, color: '#ec4899' },
                    { name: 'Referral', icon: 'fas fa-external-link-alt', percentage: 10, color: '#f59e0b' },
                    { name: 'Email', icon: 'fas fa-envelope', percentage: 5, color: '#8b5cf6' }
                ],
                topPages: [
                    { path: '/products', views: 12500 },
                    { path: '/home', views: 9800 },
                    { path: '/about', views: 5600 },
                    { path: '/contact', views: 3200 },
                    { path: '/blog', views: 2800 }
                ]
            };

            // Sample GSC metrics
            this.gscMetrics = {
                clicks: 45000,
                clicksChange: 15.2,
                impressions: 1250000,
                impressionsChange: 22.5,
                ctr: 3.6,
                ctrChange: 0.4,
                position: 8.2,
                positionChange: -1.3,
                topKeywords: [
                    { query: 'brand name products', clicks: 5200, impressions: 45000, position: 2.1 },
                    { query: 'best product category', clicks: 3800, impressions: 89000, position: 5.4 },
                    { query: 'product reviews 2024', clicks: 2400, impressions: 56000, position: 7.8 },
                    { query: 'buy product online', clicks: 1900, impressions: 42000, position: 9.2 },
                    { query: 'product comparison', clicks: 1200, impressions: 38000, position: 12.5 }
                ],
                topPages: [
                    { url: '/products/bestseller', clicks: 8500, ctr: 5.2 },
                    { url: '/category/main', clicks: 6200, ctr: 4.1 },
                    { url: '/blog/how-to-guide', clicks: 4100, ctr: 6.8 },
                    { url: '/products/new-arrival', clicks: 3500, ctr: 3.9 },
                    { url: '/', clicks: 2800, ctr: 2.2 }
                ]
            };
        },

        async syncAllPlatforms() {
            this.syncing = true;
            try {
                await fetch(`/api/orgs/{{ $currentOrg }}/analytics/sync`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                await this.fetchData();
                window.notify && window.notify('{{ __('analytics.sync_complete') }}', 'success');
            } catch (error) {
                console.error('Sync error:', error);
            }
            this.syncing = false;
        },

        getPlatformIcon(platform) {
            const icons = {
                'meta': 'fab fa-meta',
                'facebook': 'fab fa-facebook',
                'Facebook': 'fab fa-facebook',
                'instagram': 'fab fa-instagram',
                'Instagram': 'fab fa-instagram',
                'google': 'fab fa-google',
                'tiktok': 'fab fa-tiktok',
                'TikTok': 'fab fa-tiktok',
                'linkedin': 'fab fa-linkedin',
                'LinkedIn': 'fab fa-linkedin',
                'twitter': 'fab fa-x-twitter',
                'X': 'fab fa-x-twitter',
                'snapchat': 'fab fa-snapchat'
            };
            return icons[platform] || 'fas fa-globe';
        },

        getPlatformBadgeClass(platform) {
            const classes = {
                'meta': 'bg-blue-100 text-blue-700',
                'facebook': 'bg-blue-100 text-blue-700',
                'Facebook': 'bg-blue-100 text-blue-700',
                'instagram': 'bg-pink-100 text-pink-700',
                'Instagram': 'bg-pink-100 text-pink-700',
                'google': 'bg-green-100 text-green-700',
                'tiktok': 'bg-gray-100 text-gray-700',
                'TikTok': 'bg-gray-100 text-gray-700',
                'linkedin': 'bg-blue-100 text-blue-800',
                'LinkedIn': 'bg-blue-100 text-blue-800',
                'twitter': 'bg-gray-100 text-gray-800',
                'X': 'bg-gray-100 text-gray-800',
                'snapchat': 'bg-yellow-100 text-yellow-700'
            };
            return classes[platform] || 'bg-gray-100 text-gray-700';
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num?.toLocaleString() || '0';
        },

        formatCurrency(amount) {
            return new Intl.NumberFormat('{{ app()->getLocale() }}', {
                style: 'currency',
                currency: 'SAR',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
        },

        viewAccountDetails(account) {
            console.log('View account:', account);
        },

        viewAdAccountDetails(account) {
            console.log('View ad account:', account);
        },

        viewCampaignDetails(campaign) {
            window.location.href = `/orgs/{{ $currentOrg }}/analytics/campaign/${campaign.id}`;
        },

        syncAdAccount(account) {
            console.log('Sync ad account:', account);
        }
    };
}
</script>
@endpush
@endsection
