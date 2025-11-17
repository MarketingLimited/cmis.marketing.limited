@extends('layouts.admin')

@section('title', 'الرؤى والتحليلات')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="insightsPage()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">الرؤى والتحليلات المتقدمة</h1>
        <p class="mt-2 text-gray-600">اكتشف اتجاهات وأنماط جديدة في بياناتك التسويقية</p>
    </div>

    <!-- AI-Powered Insights Banner -->
    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 mb-6 text-white">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="bg-white bg-opacity-20 rounded-full p-3 ml-4">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold">رؤى مدعومة بالذكاء الاصطناعي</h2>
                    <p class="mt-1 text-indigo-100">تحليلات تلقائية لاكتشاف الفرص والتوصيات</p>
                </div>
            </div>
            <button @click="generateInsights()" class="bg-white text-indigo-600 px-6 py-3 rounded-lg font-medium hover:bg-indigo-50 transition-colors">
                <span x-show="!generating">توليد رؤى جديدة</span>
                <span x-show="generating" class="flex items-center">
                    <svg class="animate-spin h-5 w-5 ml-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    جاري التوليد...
                </span>
            </button>
        </div>
    </div>

    <!-- Insights Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">
        <!-- Campaign Performance Insights -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">أداء الحملات</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    <svg class="ml-1 h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    نشط
                </span>
            </div>

            <div class="space-y-4">
                <template x-for="insight in campaignInsights" :key="insight.id">
                    <div class="border-r-4 pr-4 py-3" :class="'border-' + insight.priority + '-500'">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6" :class="'text-' + insight.priority + '-500'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-gray-900" x-text="insight.title"></p>
                                <p class="mt-1 text-sm text-gray-600" x-text="insight.description"></p>
                                <div class="mt-2 flex items-center text-xs text-gray-500">
                                    <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span x-text="insight.timeAgo"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Audience Insights -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">رؤى الجمهور</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    محدّث
                </span>
            </div>

            <div class="space-y-4">
                <template x-for="insight in audienceInsights" :key="insight.id">
                    <div class="border-r-4 border-blue-500 pr-4 py-3">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-gray-900" x-text="insight.title"></p>
                                <p class="mt-1 text-sm text-gray-600" x-text="insight.description"></p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Content Insights -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">رؤى المحتوى</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                    توصيات
                </span>
            </div>

            <div class="space-y-4">
                <template x-for="insight in contentInsights" :key="insight.id">
                    <div class="border-r-4 border-purple-500 pr-4 py-3">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-gray-900" x-text="insight.title"></p>
                                <p class="mt-1 text-sm text-gray-600" x-text="insight.description"></p>
                                <button x-show="insight.actionable" class="mt-2 text-xs text-purple-600 hover:text-purple-700 font-medium">
                                    تطبيق التوصية ←
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Trend Analysis -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">تحليل الاتجاهات</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    متغيّر
                </span>
            </div>

            <div class="space-y-4">
                <template x-for="insight in trendInsights" :key="insight.id">
                    <div class="border-r-4 border-yellow-500 pr-4 py-3">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-6 w-6 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                                </svg>
                            </div>
                            <div class="mr-3">
                                <p class="text-sm font-medium text-gray-900" x-text="insight.title"></p>
                                <p class="mt-1 text-sm text-gray-600" x-text="insight.description"></p>
                                <div class="mt-2 flex items-center">
                                    <span class="text-xs font-medium px-2 py-1 rounded" :class="insight.trend === 'up' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                                        <span x-text="insight.trend === 'up' ? '↑' : '↓'"></span>
                                        <span x-text="insight.change + '%'"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Recommendations Section -->
    <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">توصيات قابلة للتنفيذ</h3>

        <div class="space-y-3">
            <template x-for="recommendation in recommendations" :key="recommendation.id">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="flex items-center flex-1">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full flex items-center justify-center" :class="'bg-' + recommendation.type + '-100'">
                                <svg class="h-6 w-6" :class="'text-' + recommendation.type + '-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <div class="mr-4 flex-1">
                            <p class="text-sm font-medium text-gray-900" x-text="recommendation.title"></p>
                            <p class="text-xs text-gray-500 mt-1" x-text="recommendation.impact"></p>
                        </div>
                    </div>
                    <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        تطبيق
                    </button>
                </div>
            </template>
        </div>
    </div>
</div>

@push('scripts')
<script>
function insightsPage() {
    return {
        generating: false,
        campaignInsights: [],
        audienceInsights: [],
        contentInsights: [],
        trendInsights: [],
        recommendations: [],

        init() {
            this.loadInsights();
        },

        async loadInsights() {
            try {
                const response = await fetch('/api/analytics/insights');
                const data = await response.json();

                this.campaignInsights = data.campaign_insights || [];
                this.audienceInsights = data.audience_insights || [];
                this.contentInsights = data.content_insights || [];
                this.trendInsights = data.trend_insights || [];
                this.recommendations = data.recommendations || [];
            } catch (error) {
                console.error('Error loading insights:', error);
            }
        },

        async generateInsights() {
            this.generating = true;
            try {
                const response = await fetch('/api/analytics/insights/generate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    await this.loadInsights();
                }
            } catch (error) {
                console.error('Error generating insights:', error);
            } finally {
                this.generating = false;
            }
        }
    }
}
</script>
@endpush
@endsection
