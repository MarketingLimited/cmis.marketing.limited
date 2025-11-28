@extends('layouts.admin')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('ai.center_title'))

@section('content')
<div x-data="aiDashboard()" x-init="init()">

    <!-- Page Header -->
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('ai.center_title') }}</span>
        </nav>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-brain text-white"></i>
                    </div>
                    {{ __('ai.center_title') }}
                </h1>
                <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('ai.center_description') }}</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <button @click="showGenerateModal = true"
                        class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-lg hover:from-violet-700 hover:to-purple-700 shadow-lg shadow-purple-500/25 transition-all">
                    <i class="fas fa-magic ms-2"></i>
                    {{ __('ai.generate_content') }}
                </button>
                <button @click="showSearchModal = true"
                        class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all">
                    <i class="fas fa-search ms-2"></i>
                    {{ __('ai.semantic_search') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-8">
        <!-- Generated Content -->
        <div class="relative overflow-hidden bg-gradient-to-br from-violet-500 to-purple-600 text-white rounded-2xl shadow-xl p-6">
            <div class="absolute top-0 left-0 w-full h-full bg-white/5"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-violet-100">{{ __('ai.generated_content') }}</p>
                        <p class="text-3xl font-bold mt-1" x-text="stats.generatedContent">0</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-robot text-2xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm text-violet-100">
                    <i class="fas fa-arrow-up ms-1"></i>
                    <span x-text="stats.contentGrowth + '% {{ __('common.this_month') }}'"></span>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="relative overflow-hidden bg-gradient-to-br from-blue-500 to-cyan-600 text-white rounded-2xl shadow-xl p-6">
            <div class="absolute top-0 left-0 w-full h-full bg-white/5"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-blue-100">{{ __('ai.active_recommendations') }}</p>
                        <p class="text-3xl font-bold mt-1" x-text="stats.activeRecommendations">0</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-lightbulb text-2xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm text-blue-100">
                    <i class="fas fa-check-circle ms-1"></i>
                    <span x-text="stats.appliedCount + ' {{ __('ai.applied') }}'"></span>
                </div>
            </div>
        </div>

        <!-- AI Campaigns -->
        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-2xl shadow-xl p-6">
            <div class="absolute top-0 left-0 w-full h-full bg-white/5"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-emerald-100">{{ __('ai.ai_campaigns') }}</p>
                        <p class="text-3xl font-bold mt-1" x-text="stats.aiCampaigns">0</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-bullhorn text-2xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm text-emerald-100">
                    <i class="fas fa-chart-line ms-1"></i>
                    <span x-text="stats.successRate + '% {{ __('ai.success_rate') }}'"></span>
                </div>
            </div>
        </div>

        <!-- Vector Storage -->
        <div class="relative overflow-hidden bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-2xl shadow-xl p-6">
            <div class="absolute top-0 left-0 w-full h-full bg-white/5"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-sm text-amber-100">{{ __('ai.vectors_stored') }}</p>
                        <p class="text-3xl font-bold mt-1" x-text="formatNumber(stats.vectorsStored)">0</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                        <i class="fas fa-database text-2xl"></i>
                    </div>
                </div>
                <div class="flex items-center text-sm text-amber-100">
                    <i class="fas fa-file-alt ms-1"></i>
                    <span x-text="stats.documentsProcessed + ' {{ __('ai.documents_processed') }}'"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Services Status -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <i class="fas fa-server text-purple-500"></i>
                {{ __('ai.services_status') }}
            </h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <template x-for="service in aiServices" :key="service.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                     :class="service.bgColor">
                                    <i :class="service.icon + ' text-lg ' + service.textColor"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white" x-text="service.name"></h4>
                                    <p class="text-xs text-gray-500" x-text="service.provider"></p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                  :class="service.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600'"
                                  x-text="service.status === 'active' ? '{{ __('ai.connected') }}' : '{{ __('ai.disconnected') }}'"></span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>{{ __('ai.requests_today') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="formatNumber(service.requests)"></span>
                            </div>
                            <div class="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>{{ __('ai.response_time') }}</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="service.responseTime"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 mt-2">
                                <div class="h-1.5 rounded-full transition-all"
                                     :class="service.status === 'active' ? 'bg-green-500' : 'bg-gray-400'"
                                     :style="'width: ' + service.health + '%'"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Recent Generated Content -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-file-alt text-blue-500"></i>
                    {{ __('ai.recent_generated') }}
                </h3>
                <a href="#" class="text-sm text-blue-600 hover:text-blue-700">{{ __('common.view_all') }}</a>
            </div>
            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                <template x-for="content in recentContent" :key="content.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer"
                         @click="viewContent(content)">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-lg"
                                          :class="getContentTypeClass(content.type)"
                                          x-text="content.type"></span>
                                    <span class="text-xs text-gray-500" x-text="content.timeAgo"></span>
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1 truncate" x-text="content.title"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2" x-text="content.preview"></p>
                            </div>
                            <div class="flex-shrink-0">
                                <button class="p-2 text-gray-400 hover:text-blue-600 transition">
                                    <i class="fas fa-external-link-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                            <span class="text-xs text-gray-500">
                                <i class="fas fa-language ms-1"></i>
                                <span x-text="content.language"></span>
                            </span>
                            <div class="flex items-center gap-2">
                                <button @click.stop="copyContent(content)" class="p-1.5 text-gray-400 hover:text-green-600 transition" :title="t.copy">
                                    <i class="fas fa-copy text-sm"></i>
                                </button>
                                <button @click.stop="editContent(content)" class="p-1.5 text-gray-400 hover:text-blue-600 transition" :title="t.edit">
                                    <i class="fas fa-edit text-sm"></i>
                                </button>
                                <button @click.stop="useInCampaign(content)" class="p-1.5 text-gray-400 hover:text-purple-600 transition" :title="t.use">
                                    <i class="fas fa-plus-circle text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="recentContent.length === 0" class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-file-alt text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('ai.no_generated_content') }}</p>
                    <button @click="showGenerateModal = true" class="mt-4 text-purple-600 hover:text-purple-700 font-medium">
                        <i class="fas fa-plus ms-1"></i> {{ __('ai.start_generating') }}
                    </button>
                </div>
            </div>
        </div>

        <!-- AI Recommendations -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-lightbulb text-yellow-500"></i>
                    {{ __('ai.smart_recommendations') }}
                </h3>
                <span class="px-2 py-1 text-xs font-semibold bg-yellow-100 text-yellow-700 rounded-full" x-text="recommendations.length + ' {{ __('ai.recommendations_count') }}'"></span>
            </div>
            <div class="p-4 space-y-3 max-h-96 overflow-y-auto">
                <template x-for="rec in recommendations" :key="rec.id">
                    <div class="border rounded-xl p-4 transition"
                         :class="rec.priority === 'high' ? 'border-red-200 bg-red-50/50 dark:border-red-900 dark:bg-red-900/10' : rec.priority === 'medium' ? 'border-yellow-200 bg-yellow-50/50 dark:border-yellow-900 dark:bg-yellow-900/10' : 'border-gray-200 dark:border-gray-700'">
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-lg"
                                          :class="rec.priority === 'high' ? 'bg-red-100 text-red-700' : rec.priority === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700'"
                                          x-text="rec.priority === 'high' ? t.highPriority : rec.priority === 'medium' ? t.mediumPriority : t.lowPriority"></span>
                                    <span class="text-xs text-green-600 font-medium">
                                        <i class="fas fa-chart-line ms-1"></i>
                                        <span x-text="'+' + rec.impact + '% ' + t.expectedImpact"></span>
                                    </span>
                                </div>
                                <h4 class="font-semibold text-gray-900 dark:text-white mb-1" x-text="rec.title"></h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400" x-text="rec.description"></p>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center text-xs text-gray-500">
                                <i class="fas fa-brain ms-1"></i>
                                <span x-text="t.confidence + ': ' + rec.confidence + '%'"></span>
                                <div class="w-16 h-1.5 bg-gray-200 dark:bg-gray-700 rounded-full me-2">
                                    <div class="h-1.5 bg-purple-500 rounded-full" :style="'width: ' + rec.confidence + '%'"></div>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button @click="applyRecommendation(rec)"
                                        class="px-3 py-1.5 text-xs font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                    <i class="fas fa-check ms-1"></i> {{ __('ai.apply') }}
                                </button>
                                <button @click="dismissRecommendation(rec)"
                                        class="px-3 py-1.5 text-xs font-semibold bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                    {{ __('ai.dismiss') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="recommendations.length === 0" class="text-center py-12">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-lightbulb text-2xl text-gray-400"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400">{{ __('ai.no_recommendations') }}</p>
                    <p class="text-sm text-gray-400 mt-1">{{ __('ai.recommendations_info') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- AI Models & Knowledge Base -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- AI Models -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-cogs text-indigo-500"></i>
                    {{ __('ai.trained_models') }}
                </h3>
            </div>
            <div class="p-4 space-y-3">
                <template x-for="model in aiModels" :key="model.id">
                    <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-white" x-text="model.name"></h4>
                                <p class="text-xs text-gray-500" x-text="model.family"></p>
                            </div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full"
                                  :class="model.status === 'trained' ? 'bg-green-100 text-green-700' : model.status === 'training' ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-600'"
                                  x-text="model.status === 'trained' ? t.ready : model.status === 'training' ? t.training : t.inactive"></span>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600 dark:text-gray-400">{{ __('ai.accuracy') }}:</span>
                                <span class="font-semibold text-gray-900 dark:text-white" x-text="model.accuracy + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all"
                                     :class="model.accuracy >= 90 ? 'bg-green-500' : model.accuracy >= 75 ? 'bg-yellow-500' : 'bg-red-500'"
                                     :style="'width: ' + model.accuracy + '%'"></div>
                            </div>
                            <div class="flex justify-between text-xs text-gray-500 mt-2">
                                <span x-text="t.lastTrained + ': ' + model.lastTrained"></span>
                                <span x-text="formatNumber(model.predictions) + ' ' + t.predictions"></span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Empty State -->
                <div x-show="aiModels.length === 0" class="text-center py-8">
                    <i class="fas fa-cogs text-3xl text-gray-300 mb-3"></i>
                    <p class="text-gray-500">{{ __('ai.no_trained_models') }}</p>
                </div>
            </div>
        </div>

        <!-- Knowledge Base -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-book text-teal-500"></i>
                    {{ __('ai.knowledge_base') }}
                </h3>
            </div>
            <div class="p-4">
                <!-- Search -->
                <div class="relative mb-4">
                    <input type="text"
                           x-model="knowledgeSearch"
                           @input="searchKnowledge()"
                           placeholder="{{ __('ai.search_knowledge') }}"
                           class="w-full px-4 py-2.5 pe-10 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    <i class="fas fa-search absolute end-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>

                <div class="space-y-3 max-h-72 overflow-y-auto">
                    <template x-for="doc in filteredDocs" :key="doc.id">
                        <div class="border border-gray-200 dark:border-gray-700 rounded-xl p-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition cursor-pointer"
                             @click="openDocument(doc)">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0"
                                     :class="doc.iconBg">
                                    <i :class="doc.icon + ' ' + doc.iconColor"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h5 class="font-semibold text-gray-900 dark:text-white text-sm truncate" x-text="doc.title"></h5>
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="doc.excerpt"></p>
                                    <div class="flex items-center gap-3 mt-2 text-xs text-gray-500">
                                        <span>
                                            <i class="fas fa-tag ms-1"></i>
                                            <span x-text="doc.category"></span>
                                        </span>
                                        <span>
                                            <i class="fas fa-clock ms-1"></i>
                                            <span x-text="doc.updatedAt"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div x-show="filteredDocs.length === 0" class="text-center py-8">
                        <i class="fas fa-book-open text-3xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">{{ __('ai.no_documents') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Generate Content Modal -->
    <div x-show="showGenerateModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/75 transition-opacity" @click="showGenerateModal = false"></div>

            <div x-show="showGenerateModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl transform transition-all sm:max-w-lg w-full mx-4">

                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-magic text-purple-500"></i>
                        {{ __('ai.generate_new_content') }}
                    </h3>
                    <button @click="showGenerateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.content_type') }}</label>
                        <select x-model="generateForm.contentType"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                            <option value="ad_copy">{{ __('ai.ad_copy') }}</option>
                            <option value="social_post">{{ __('ai.social_post') }}</option>
                            <option value="email">{{ __('ai.email') }}</option>
                            <option value="landing_page">{{ __('ai.landing_page') }}</option>
                            <option value="video_script">{{ __('ai.video_script') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.topic_product') }}</label>
                        <input type="text"
                               x-model="generateForm.topic"
                               placeholder="{{ __('ai.topic_placeholder') }}"
                               class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.marketing_objective') }}</label>
                        <select x-model="generateForm.objective"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                            <option value="awareness">{{ __('ai.awareness') }}</option>
                            <option value="consideration">{{ __('ai.consideration') }}</option>
                            <option value="conversion">{{ __('ai.conversion') }}</option>
                            <option value="engagement">{{ __('ai.engagement') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.language') }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" @click="generateForm.language = 'ar'"
                                    :class="generateForm.language === 'ar' ? 'bg-purple-100 dark:bg-purple-900/30 border-purple-500 text-purple-700 dark:text-purple-400' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'"
                                    class="px-4 py-2.5 border-2 rounded-xl font-semibold transition">
                                {{ __('ai.arabic') }}
                            </button>
                            <button type="button" @click="generateForm.language = 'en'"
                                    :class="generateForm.language === 'en' ? 'bg-purple-100 dark:bg-purple-900/30 border-purple-500 text-purple-700 dark:text-purple-400' : 'bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300'"
                                    class="px-4 py-2.5 border-2 rounded-xl font-semibold transition">
                                {{ __('ai.english') }}
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.tone') }}</label>
                        <select x-model="generateForm.tone"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-purple-500">
                            <option value="professional">{{ __('ai.professional') }}</option>
                            <option value="friendly">{{ __('ai.friendly') }}</option>
                            <option value="persuasive">{{ __('ai.persuasive') }}</option>
                            <option value="casual">{{ __('ai.casual') }}</option>
                            <option value="urgent">{{ __('ai.urgent') }}</option>
                        </select>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button @click="showGenerateModal = false"
                            class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="generateContent()"
                            :disabled="isGenerating"
                            class="px-4 py-2.5 bg-gradient-to-r from-violet-600 to-purple-600 text-white rounded-xl hover:from-violet-700 hover:to-purple-700 transition disabled:opacity-50">
                        <i class="fas fa-magic ms-2" :class="isGenerating && 'fa-spin'"></i>
                        <span x-text="isGenerating ? '{{ __('ai.generating') }}' : '{{ __('ai.generate') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Semantic Search Modal -->
    <div x-show="showSearchModal"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto"
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <div class="fixed inset-0 bg-gray-900/75 transition-opacity" @click="showSearchModal = false"></div>

            <div x-show="showSearchModal"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white dark:bg-gray-800 rounded-2xl shadow-xl transform transition-all sm:max-w-lg w-full mx-4">

                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-search text-blue-500"></i>
                        {{ __('ai.advanced_semantic_search') }}
                    </h3>
                    <button @click="showSearchModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.search_query') }}</label>
                        <textarea x-model="searchForm.query"
                                  rows="4"
                                  placeholder="{{ __('ai.search_placeholder') }}"
                                  class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __('ai.search_in') }}</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" x-model="searchForm.sources" value="campaigns" class="rounded text-blue-600 ms-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('ai.previous_campaigns') }}</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" x-model="searchForm.sources" value="documents" class="rounded text-blue-600 ms-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('ai.documents') }}</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" x-model="searchForm.sources" value="analytics" class="rounded text-blue-600 ms-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('ai.analytics') }}</span>
                            </label>
                            <label class="flex items-center p-3 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                <input type="checkbox" x-model="searchForm.sources" value="knowledge" class="rounded text-blue-600 ms-2">
                                <span class="text-sm text-gray-700 dark:text-gray-300">{{ __('ai.knowledge_base') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                    <button @click="showSearchModal = false"
                            class="px-4 py-2.5 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-xl hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                        {{ __('common.cancel') }}
                    </button>
                    <button @click="performSearch()"
                            :disabled="isSearching"
                            class="px-4 py-2.5 bg-gradient-to-r from-blue-600 to-cyan-600 text-white rounded-xl hover:from-blue-700 hover:to-cyan-700 transition disabled:opacity-50">
                        <i class="fas fa-search ms-2" :class="isSearching && 'fa-spin'"></i>
                        <span x-text="isSearching ? '{{ __('ai.searching') }}' : '{{ __('common.search') }}'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function aiDashboard() {
    return {
        // Translations
        t: {
            // Content types
            adCopy: '{{ __('ai.ad_copy') }}',
            socialPost: '{{ __('ai.social_post') }}',
            email: '{{ __('ai.email') }}',
            videoScript: '{{ __('ai.video_script') }}',
            landingPage: '{{ __('ai.landing_page') }}',
            // Priority levels
            highPriority: '{{ __('ai.high_priority') }}',
            mediumPriority: '{{ __('ai.medium_priority') }}',
            lowPriority: '{{ __('ai.low_priority') }}',
            expectedImpact: '{{ __('ai.expected_impact') }}',
            confidence: '{{ __('ai.confidence') }}',
            // Model status
            ready: '{{ __('ai.ready') }}',
            training: '{{ __('ai.training') }}',
            inactive: '{{ __('ai.inactive') }}',
            // Other
            lastTrained: '{{ __('ai.last_trained') }}',
            predictions: '{{ __('ai.predictions') }}',
            copy: '{{ __('ai.copy') }}',
            edit: '{{ __('common.edit') }}',
            use: '{{ __('ai.use') }}',
        },

        // Modal states
        showGenerateModal: false,
        showSearchModal: false,
        isGenerating: false,
        isSearching: false,

        // Stats from backend
        stats: {
            generatedContent: {{ $stats['campaigns'] ?? 0 }},
            contentGrowth: 24,
            activeRecommendations: {{ $stats['recommendations'] ?? 0 }},
            appliedCount: 12,
            aiCampaigns: {{ $stats['campaigns'] ?? 0 }},
            successRate: 87,
            vectorsStored: 45620,
            documentsProcessed: 284
        },

        // AI Services
        aiServices: [
            {
                id: 1,
                name: 'Google Gemini',
                provider: 'Google AI',
                icon: 'fas fa-brain',
                bgColor: 'bg-purple-100 dark:bg-purple-900/30',
                textColor: 'text-purple-600 dark:text-purple-400',
                status: 'active',
                requests: 1523,
                responseTime: '1.2s',
                health: 98
            },
            {
                id: 2,
                name: 'OpenAI GPT-4',
                provider: 'OpenAI',
                icon: 'fas fa-robot',
                bgColor: 'bg-blue-100 dark:bg-blue-900/30',
                textColor: 'text-blue-600 dark:text-blue-400',
                status: 'active',
                requests: 2341,
                responseTime: '0.8s',
                health: 100
            },
            {
                id: 3,
                name: 'pgvector',
                provider: 'PostgreSQL',
                icon: 'fas fa-database',
                bgColor: 'bg-green-100 dark:bg-green-900/30',
                textColor: 'text-green-600 dark:text-green-400',
                status: 'active',
                requests: 5672,
                responseTime: '0.3s',
                health: 100
            }
        ],

        // Recent Content
        recentContent: [
            { id: 1, type: 'نص إعلاني', title: 'حملة الجمعة البيضاء', preview: 'اكتشف عروضنا الحصرية! خصم يصل إلى 70% على جميع المنتجات المحددة...', language: 'العربية', timeAgo: 'منذ ساعة' },
            { id: 2, type: 'منشور سوشيال', title: 'إطلاق المنتج الجديد', preview: 'نحن متحمسون للإعلان عن منتجنا الثوري الجديد الذي سيغير طريقة...', language: 'العربية', timeAgo: 'منذ 3 ساعات' },
            { id: 3, type: 'بريد إلكتروني', title: 'رسالة ترحيبية للعملاء', preview: 'مرحباً بك في عائلتنا! نحن سعداء بانضمامك إلينا...', language: 'العربية', timeAgo: 'منذ 5 ساعات' },
            { id: 4, type: 'سكريبت فيديو', title: 'فيديو توضيحي للمنتج', preview: '[المشهد الافتتاحي] شخص يواجه مشكلة شائعة في الحياة اليومية...', language: 'العربية', timeAgo: 'منذ يوم' }
        ],

        // Recommendations
        recommendations: [
            { id: 1, priority: 'high', title: 'زيادة الميزانية لحملة Meta', description: 'الحملة تحقق ROAS 5.2x، يُنصح بزيادة الميزانية بنسبة 30% للاستفادة من الأداء العالي', impact: 45, confidence: 92 },
            { id: 2, priority: 'medium', title: 'تحسين استهداف الجمهور', description: 'الفئة العمرية 25-34 تظهر أعلى معدل تحويل، قم بزيادة التركيز على هذه الشريحة', impact: 28, confidence: 85 },
            { id: 3, priority: 'high', title: 'تغيير وقت النشر', description: 'البيانات تظهر أن النشر بين 8-10 مساءً يحقق أفضل النتائج في منطقتك', impact: 35, confidence: 88 },
            { id: 4, priority: 'low', title: 'اختبار نصوص إعلانية جديدة', description: 'معدل النقر CTR يمكن تحسينه باستخدام نداء أقوى للعمل (CTA)', impact: 15, confidence: 72 }
        ],

        // AI Models
        aiModels: [
            { id: 1, name: 'CTR Predictor', family: 'Random Forest', status: 'trained', accuracy: 94.2, lastTrained: 'منذ يومين', predictions: 15234 },
            { id: 2, name: 'Budget Optimizer', family: 'Neural Network', status: 'trained', accuracy: 89.7, lastTrained: 'منذ أسبوع', predictions: 8956 },
            { id: 3, name: 'Audience Segmenter', family: 'K-Means Clustering', status: 'training', accuracy: 76.3, lastTrained: 'قيد التدريب', predictions: 3421 }
        ],

        // Knowledge Base
        knowledgeDocs: [
            { id: 1, title: 'دليل أفضل ممارسات إعلانات Meta', excerpt: 'استراتيجيات متقدمة لتحسين أداء حملات Meta Ads وزيادة العائد على الإعلان', category: 'إعلانات', icon: 'fas fa-file-pdf', iconBg: 'bg-red-100 dark:bg-red-900/30', iconColor: 'text-red-600', updatedAt: 'منذ 3 أيام' },
            { id: 2, title: 'تقرير اتجاهات السوق Q4 2025', excerpt: 'تحليل شامل لاتجاهات التسويق الرقمي في الربع الأخير من العام', category: 'تقارير', icon: 'fas fa-chart-line', iconBg: 'bg-blue-100 dark:bg-blue-900/30', iconColor: 'text-blue-600', updatedAt: 'منذ أسبوع' },
            { id: 3, title: 'دراسة حالة: حملة ناجحة', excerpt: 'كيف حققت شركة XYZ زيادة 300% في المبيعات باستخدام استراتيجيات مبتكرة', category: 'دراسات', icon: 'fas fa-lightbulb', iconBg: 'bg-yellow-100 dark:bg-yellow-900/30', iconColor: 'text-yellow-600', updatedAt: 'منذ أسبوعين' }
        ],
        knowledgeSearch: '',

        // Forms
        generateForm: {
            contentType: 'ad_copy',
            topic: '',
            objective: 'awareness',
            language: 'ar',
            tone: 'professional'
        },
        searchForm: {
            query: '',
            sources: ['campaigns', 'documents', 'knowledge']
        },

        // Computed
        get filteredDocs() {
            if (!this.knowledgeSearch) return this.knowledgeDocs;
            const search = this.knowledgeSearch.toLowerCase();
            return this.knowledgeDocs.filter(doc =>
                doc.title.toLowerCase().includes(search) ||
                doc.excerpt.toLowerCase().includes(search) ||
                doc.category.toLowerCase().includes(search)
            );
        },

        // Methods
        init() {
            console.log('AI Dashboard initialized');
        },

        formatNumber(num) {
            if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
            if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
            return num.toString();
        },

        getContentTypeClass(type) {
            const classes = {
                [this.t.adCopy]: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                [this.t.socialPost]: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                [this.t.email]: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                [this.t.landingPage]: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
                [this.t.videoScript]: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
            };
            return classes[type] || 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
        },

        async generateContent() {
            if (!this.generateForm.topic) {
                window.notify && window.notify('{{ __('ai.enter_topic') }}', 'warning');
                return;
            }

            this.isGenerating = true;
            try {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 2000));

                window.notify && window.notify('{{ __('ai.content_generated_success') }}', 'success');
                this.showGenerateModal = false;
                this.generateForm.topic = '';
            } catch (error) {
                window.notify && window.notify('{{ __('ai.content_generation_failed') }}', 'error');
            } finally {
                this.isGenerating = false;
            }
        },

        async performSearch() {
            if (!this.searchForm.query) {
                window.notify && window.notify('{{ __('ai.enter_search_query') }}', 'warning');
                return;
            }

            this.isSearching = true;
            try {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1500));

                window.notify && window.notify('{{ __('ai.search_results_found') }}', 'success');
                this.showSearchModal = false;
            } catch (error) {
                window.notify && window.notify('{{ __('ai.search_failed') }}', 'error');
            } finally {
                this.isSearching = false;
            }
        },

        searchKnowledge() {
            // Real-time filtering handled by computed property
        },

        viewContent(content) {
            window.notify && window.notify('{{ __('ai.viewing') }}: ' + content.title, 'info');
        },

        copyContent(content) {
            navigator.clipboard && navigator.clipboard.writeText(content.preview);
            window.notify && window.notify('{{ __('ai.content_copied') }}', 'success');
        },

        editContent(content) {
            window.notify && window.notify('{{ __('ai.editing') }}: ' + content.title, 'info');
        },

        useInCampaign(content) {
            window.notify && window.notify('{{ __('ai.content_added_to_campaign') }}', 'success');
        },

        applyRecommendation(rec) {
            window.notify && window.notify('{{ __('ai.applying_recommendation') }}', 'info');
            this.recommendations = this.recommendations.filter(r => r.id !== rec.id);
        },

        dismissRecommendation(rec) {
            this.recommendations = this.recommendations.filter(r => r.id !== rec.id);
            window.notify && window.notify('{{ __('ai.recommendation_dismissed') }}', 'success');
        },

        openDocument(doc) {
            window.notify && window.notify('{{ __('ai.opening') }}: ' + doc.title, 'info');
        }
    };
}
</script>
@endpush
