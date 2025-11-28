@extends('layouts.admin')

@section('title', __('listening.social_listening'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div x-data="socialListeningDashboard()" x-init="init()" class="space-y-6" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('listening.social_listening') }}</span>
        </nav>
        <div class="flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('listening.social_listening') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('listening.monitor_brand_mentions') }}</p>
            </div>
            <div class="flex {{ $isRtl ? 'gap-x-3 flex-row-reverse' : 'gap-x-3' }}">
                <button @click="showSetupModal = true"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fas fa-cog"></i>
                    <span>{{ __('listening.configure_tracking') }}</span>
                </button>
                <button @click="refreshData()"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                        :disabled="loading">
                    <i class="fas fa-sync-alt" :class="{ 'fa-spin': loading }"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- Date Range Selector --}}
    <div class="bg-white rounded-lg shadow p-4">
        <div class="flex items-center gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <label class="text-sm font-medium text-gray-700">{{ __('listening.time_range') }}:</label>
            <select x-model="timeRange" @change="loadData()" class="px-4 py-2 border border-gray-300 rounded-lg">
                <option value="1h">{{ __('listening.last_hour') }}</option>
                <option value="24h">{{ __('listening.last_24_hours') }}</option>
                <option value="7d">{{ __('listening.last_7_days') }}</option>
                <option value="30d">{{ __('listening.last_30_days') }}</option>
                <option value="custom">{{ __('listening.custom_range') }}</option>
            </select>
            <div class="flex-1"></div>
            <div class="flex gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <button @click="activeTab = 'overview'"
                        :class="activeTab === 'overview' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg transition">
                    {{ __('listening.overview') }}
                </button>
                <button @click="activeTab = 'mentions'"
                        :class="activeTab === 'mentions' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg transition">
                    {{ __('listening.mentions') }}
                </button>
                <button @click="activeTab = 'sentiment'"
                        :class="activeTab === 'sentiment' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg transition">
                    {{ __('listening.sentiment') }}
                </button>
                <button @click="activeTab = 'trends'"
                        :class="activeTab === 'trends' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700'"
                        class="px-4 py-2 rounded-lg transition">
                    {{ __('listening.trends') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Overview Tab --}}
    <div x-show="activeTab === 'overview'" x-transition>
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <i class="fas fa-comments text-2xl text-blue-600"></i>
                    </div>
                    <div class="{{ $isRtl ? 'me-4 text-end' : 'ms-4 text-start' }}">
                        <p class="text-sm font-medium text-gray-600">{{ __('listening.total_mentions') }}</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(stats.totalMentions)">0</p>
                        <p class="text-xs mt-1" :class="stats.mentionsTrend >= 0 ? 'text-green-600' : 'text-red-600'">
                            <i :class="stats.mentionsTrend >= 0 ? 'fas fa-arrow-up' : 'fas fa-arrow-down'"></i>
                            <span x-text="Math.abs(stats.mentionsTrend) + '%'"></span>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <i class="fas fa-smile text-2xl text-green-600"></i>
                    </div>
                    <div class="{{ $isRtl ? 'me-4 text-end' : 'ms-4 text-start' }}">
                        <p class="text-sm font-medium text-gray-600">{{ __('listening.positive_sentiment') }}</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.positivePct + '%'">0%</p>
                        <p class="text-xs text-gray-500 mt-1">
                            <span x-text="formatNumber(stats.positive)"></span> {{ __('listening.mentions') }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                        <i class="fas fa-fire text-2xl text-yellow-600"></i>
                    </div>
                    <div class="{{ $isRtl ? 'me-4 text-end' : 'ms-4 text-start' }}">
                        <p class="text-sm font-medium text-gray-600">{{ __('listening.trending_topics') }}</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.trendingTopics">0</p>
                        <p class="text-xs text-gray-500 mt-1">{{ __('listening.active_discussions') }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <i class="fas fa-users text-2xl text-purple-600"></i>
                    </div>
                    <div class="{{ $isRtl ? 'me-4 text-end' : 'ms-4 text-start' }}">
                        <p class="text-sm font-medium text-gray-600">{{ __('listening.total_reach') }}</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="formatNumber(stats.totalReach)">0</p>
                        <p class="text-xs text-gray-500 mt-1">{{ __('listening.potential_impressions') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            {{-- Mentions Over Time Chart --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('listening.mentions_over_time') }}</h3>
                <canvas id="mentionsChart" height="250"></canvas>
            </div>

            {{-- Sentiment Distribution Chart --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('listening.sentiment_distribution') }}</h3>
                <canvas id="sentimentChart" height="250"></canvas>
            </div>
        </div>

        {{-- Platform Breakdown and Top Keywords --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Platform Breakdown --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('listening.platform_breakdown') }}</h3>
                <div class="space-y-3">
                    <template x-for="platform in platformBreakdown" :key="platform.name">
                        <div>
                            <div class="flex justify-between items-center mb-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <i :class="platform.icon" :style="'color: ' + platform.color"></i>
                                    <span class="text-sm font-medium text-gray-700" x-text="platform.name"></span>
                                </div>
                                <span class="text-sm text-gray-600" x-text="formatNumber(platform.mentions)"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all duration-300"
                                     :style="'width: ' + platform.percentage + '%; background-color: ' + platform.color"></div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Top Keywords --}}
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('listening.top_keywords') }}</h3>
                <div class="space-y-2">
                    <template x-for="(keyword, index) in topKeywords" :key="keyword.text">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div class="flex items-center gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <span class="text-sm font-bold text-gray-400" x-text="'#' + (index + 1)"></span>
                                <span class="text-sm font-medium text-gray-900" x-text="keyword.text"></span>
                            </div>
                            <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <span class="text-sm text-gray-600" x-text="formatNumber(keyword.count)"></span>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                      :class="{
                                          'bg-green-100 text-green-800': keyword.sentiment === 'positive',
                                          'bg-gray-100 text-gray-800': keyword.sentiment === 'neutral',
                                          'bg-red-100 text-red-800': keyword.sentiment === 'negative'
                                      }"
                                      x-text="keyword.sentiment"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Mentions Tab --}}
    <div x-show="activeTab === 'mentions'" x-transition>
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <h2 class="text-lg font-semibold text-gray-900">{{ __('listening.recent_mentions') }}</h2>
                    <div class="flex gap-3 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <input type="text"
                               x-model="searchQuery"
                               @input="filterMentions()"
                               placeholder="{{ __('listening.search_mentions_placeholder') }}"
                               class="px-4 py-2 border border-gray-300 rounded-lg">
                        <select x-model="filterSentiment" @change="filterMentions()" class="px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">{{ __('listening.all_sentiments') }}</option>
                            <option value="positive">{{ __('listening.positive') }}</option>
                            <option value="neutral">{{ __('listening.neutral') }}</option>
                            <option value="negative">{{ __('listening.negative') }}</option>
                        </select>
                        <select x-model="filterPlatform" @change="filterMentions()" class="px-4 py-2 border border-gray-300 rounded-lg">
                            <option value="">{{ __('listening.all_platforms') }}</option>
                            <option value="twitter">Twitter</option>
                            <option value="facebook">Facebook</option>
                            <option value="instagram">Instagram</option>
                            <option value="linkedin">LinkedIn</option>
                            <option value="tiktok">TikTok</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="divide-y divide-gray-200">
                <template x-if="loading">
                    <div class="p-12 text-center">
                        <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
                        <p class="mt-2 text-gray-500">{{ __('listening.loading_mentions') }}</p>
                    </div>
                </template>
                <template x-if="!loading && filteredMentions.length === 0">
                    <div class="p-12 text-center">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">{{ __('listening.no_mentions_found') }}</p>
                    </div>
                </template>
                <template x-for="mention in filteredMentions" :key="mention.id">
                    <div class="p-6 hover:bg-gray-50 transition">
                        <div class="flex gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <img :src="mention.authorAvatar || '/images/default-avatar.png'"
                                 :alt="mention.authorName"
                                 class="w-12 h-12 rounded-full">
                            <div class="flex-1 {{ $isRtl ? 'text-right' : '' }}">
                                <div class="flex justify-between items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div>
                                        <h4 class="font-semibold text-gray-900" x-text="mention.authorName"></h4>
                                        <p class="text-sm text-gray-500" x-text="mention.authorHandle"></p>
                                    </div>
                                    <div class="flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <i :class="getPlatformIcon(mention.platform)" class="text-gray-400"></i>
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium"
                                              :class="{
                                                  'bg-green-100 text-green-800': mention.sentiment === 'positive',
                                                  'bg-gray-100 text-gray-800': mention.sentiment === 'neutral',
                                                  'bg-red-100 text-red-800': mention.sentiment === 'negative'
                                              }"
                                              x-text="mention.sentiment"></span>
                                    </div>
                                </div>
                                <p class="mt-2 text-gray-700" x-text="mention.text"></p>
                                <div class="mt-3 flex items-center gap-4 text-sm text-gray-500 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span x-text="formatDate(mention.publishedAt)"></span>
                                    <span class="flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <i class="fas fa-heart"></i>
                                        <span x-text="formatNumber(mention.likes)"></span>
                                    </span>
                                    <span class="flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <i class="fas fa-retweet"></i>
                                        <span x-text="formatNumber(mention.shares)"></span>
                                    </span>
                                    <span class="flex items-center gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                        <i class="fas fa-users"></i>
                                        <span x-text="formatNumber(mention.reach)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Sentiment Tab --}}
    <div x-show="activeTab === 'sentiment'" x-transition>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ __('listening.sentiment_analysis') }}</h2>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="text-center p-6 bg-green-50 rounded-lg">
                    <i class="fas fa-smile text-5xl text-green-600 mb-3"></i>
                    <p class="text-3xl font-bold text-green-600" x-text="stats.positivePct + '%'">0%</p>
                    <p class="text-sm text-gray-600 mt-1">{{ __('listening.positive') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2" x-text="formatNumber(stats.positive)">0</p>
                </div>
                <div class="text-center p-6 bg-gray-50 rounded-lg">
                    <i class="fas fa-meh text-5xl text-gray-600 mb-3"></i>
                    <p class="text-3xl font-bold text-gray-600" x-text="stats.neutralPct + '%'">0%</p>
                    <p class="text-sm text-gray-600 mt-1">{{ __('listening.neutral') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2" x-text="formatNumber(stats.neutral)">0</p>
                </div>
                <div class="text-center p-6 bg-red-50 rounded-lg">
                    <i class="fas fa-frown text-5xl text-red-600 mb-3"></i>
                    <p class="text-3xl font-bold text-red-600" x-text="stats.negativePct + '%'">0%</p>
                    <p class="text-sm text-gray-600 mt-1">{{ __('listening.negative') }}</p>
                    <p class="text-2xl font-semibold text-gray-900 mt-2" x-text="formatNumber(stats.negative)">0</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Trends Tab --}}
    <div x-show="activeTab === 'trends'" x-transition>
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">{{ __('listening.trending_topics_hashtags') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(trend, index) in trends" :key="trend.hashtag">
                    <div class="p-4 border border-gray-200 rounded-lg hover:shadow-md transition">
                        <div class="flex justify-between items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <div>
                                <span class="text-xs font-bold text-gray-400" x-text="'#' + (index + 1)"></span>
                                <h3 class="text-lg font-bold text-blue-600 mt-1" x-text="trend.hashtag"></h3>
                            </div>
                            <span class="text-xs px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full">
                                <i class="fas fa-fire"></i> {{ __('listening.trending') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-600 mt-2" x-text="formatNumber(trend.mentions) + ' {{ __('listening.mentions_lowercase') }}'"></p>
                        <div class="mt-3 text-xs text-gray-500">
                            <span x-text="'+' + trend.growth + '% {{ __('listening.in_last') }} ' + timeRange"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
function socialListeningDashboard() {
    return {
        loading: false,
        showSetupModal: false,
        activeTab: 'overview',
        timeRange: '24h',
        searchQuery: '',
        filterSentiment: '',
        filterPlatform: '',
        mentions: [],
        filteredMentions: [],
        stats: {
            totalMentions: 0,
            mentionsTrend: 0,
            positive: 0,
            neutral: 0,
            negative: 0,
            positivePct: 0,
            neutralPct: 0,
            negativePct: 0,
            trendingTopics: 0,
            totalReach: 0
        },
        platformBreakdown: [
            { name: 'Twitter', icon: 'fab fa-twitter', color: '#1DA1F2', mentions: 0, percentage: 0 },
            { name: 'Facebook', icon: 'fab fa-facebook', color: '#1877F2', mentions: 0, percentage: 0 },
            { name: 'Instagram', icon: 'fab fa-instagram', color: '#E4405F', mentions: 0, percentage: 0 },
            { name: 'LinkedIn', icon: 'fab fa-linkedin', color: '#0A66C2', mentions: 0, percentage: 0 },
            { name: 'TikTok', icon: 'fab fa-tiktok', color: '#000000', mentions: 0, percentage: 0 }
        ],
        topKeywords: [],
        trends: [],
        mentionsChart: null,
        sentimentChart: null,

        init() {
            this.loadData();
        },

        async loadData() {
            this.loading = true;
            try {
                const orgId = '{{ $currentOrg }}';
                const response = await fetch(`/api/orgs/${orgId}/social-listening/dashboard?range=${this.timeRange}`);
                const data = await response.json();

                if (data.success) {
                    this.mentions = data.data.mentions || [];
                    this.filteredMentions = this.mentions;
                    this.stats = data.data.stats || this.stats;
                    this.topKeywords = data.data.keywords || [];
                    this.trends = data.data.trends || [];
                    this.updatePlatformBreakdown();
                    this.initCharts();
                }
            } catch (error) {
                console.error('Error loading data:', error);
            } finally {
                this.loading = false;
            }
        },

        filterMentions() {
            this.filteredMentions = this.mentions.filter(mention => {
                const matchesSearch = mention.text.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                                    mention.authorName.toLowerCase().includes(this.searchQuery.toLowerCase());
                const matchesSentiment = !this.filterSentiment || mention.sentiment === this.filterSentiment;
                const matchesPlatform = !this.filterPlatform || mention.platform === this.filterPlatform;
                return matchesSearch && matchesSentiment && matchesPlatform;
            });
        },

        updatePlatformBreakdown() {
            const totalMentions = this.stats.totalMentions || 1;
            this.platformBreakdown.forEach(platform => {
                const count = this.mentions.filter(m => m.platform === platform.name.toLowerCase()).length;
                platform.mentions = count;
                platform.percentage = (count / totalMentions) * 100;
            });
        },

        initCharts() {
            // Mentions Over Time Chart
            if (this.mentionsChart) this.mentionsChart.destroy();
            const mentionsCtx = document.getElementById('mentionsChart');
            if (mentionsCtx) {
                this.mentionsChart = new Chart(mentionsCtx, {
                    type: 'line',
                    data: {
                        labels: ['{{ __('common.mon') }}', '{{ __('common.tue') }}', '{{ __('common.wed') }}', '{{ __('common.thu') }}', '{{ __('common.fri') }}', '{{ __('common.sat') }}', '{{ __('common.sun') }}'],
                        datasets: [{
                            label: '{{ __('listening.mentions') }}',
                            data: [120, 150, 180, 165, 200, 210, 190],
                            borderColor: '#3B82F6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }

            // Sentiment Distribution Chart
            if (this.sentimentChart) this.sentimentChart.destroy();
            const sentimentCtx = document.getElementById('sentimentChart');
            if (sentimentCtx) {
                this.sentimentChart = new Chart(sentimentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['{{ __('listening.positive') }}', '{{ __('listening.neutral') }}', '{{ __('listening.negative') }}'],
                        datasets: [{
                            data: [this.stats.positive, this.stats.neutral, this.stats.negative],
                            backgroundColor: ['#10B981', '#6B7280', '#EF4444']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false
                    }
                });
            }
        },

        async refreshData() {
            await this.loadData();
        },

        getPlatformIcon(platform) {
            const icons = {
                twitter: 'fab fa-twitter',
                facebook: 'fab fa-facebook',
                instagram: 'fab fa-instagram',
                linkedin: 'fab fa-linkedin',
                tiktok: 'fab fa-tiktok'
            };
            return icons[platform] || 'fas fa-globe';
        },

        formatNumber(num) {
            return new Intl.NumberFormat('en-US').format(num || 0);
        },

        formatDate(date) {
            return new Date(date).toLocaleString('en-US', {
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    };
}
</script>
@endpush
@endsection
