@extends('super-admin.layouts.app')

@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('title', __('super_admin.system.logs_title'))

@section('breadcrumb')
    <span class="text-gray-400 mx-2">/</span>
    <a href="{{ route('super-admin.system.health') }}" class="text-gray-500 hover:text-red-600 transition">{{ __('super_admin.system.health_title') }}</a>
    <span class="text-gray-400 mx-2">/</span>
    <span class="text-gray-700 dark:text-gray-300">{{ __('super_admin.system.logs_title') }}</span>
@endsection

@section('content')
<div x-data="logsViewer()" x-init="loadLogs()">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.system.logs_title') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('super_admin.system.logs_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="clearLogs()"
                    class="px-4 py-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 border border-red-300 dark:border-red-800 rounded-lg transition flex items-center gap-2">
                <i class="fas fa-trash"></i>
                {{ __('super_admin.system.clear_logs') }}
            </button>
            <button @click="loadLogs()"
                    :disabled="loading"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition flex items-center gap-2 disabled:opacity-50">
                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sync'"></i>
                {{ __('super_admin.system.refresh') }}
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- Search -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text"
                           x-model="filters.search"
                           @input.debounce.300ms="loadLogs()"
                           placeholder="{{ __('super_admin.system.search_logs') }}"
                           class="w-full {{ $isRtl ? 'pr-10 pl-4' : 'pl-10 pr-4' }} py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <i class="fas fa-search absolute {{ $isRtl ? 'right-3' : 'left-3' }} top-1/2 -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            <!-- Level Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.level"
                        @change="loadLogs()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">{{ __('super_admin.system.all_levels') }}</option>
                    <option value="emergency">Emergency</option>
                    <option value="alert">Alert</option>
                    <option value="critical">Critical</option>
                    <option value="error">Error</option>
                    <option value="warning">Warning</option>
                    <option value="notice">Notice</option>
                    <option value="info">Info</option>
                    <option value="debug">Debug</option>
                </select>
            </div>

            <!-- Date Filter -->
            <div class="w-full lg:w-48">
                <select x-model="filters.date"
                        @change="loadLogs()"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="today">{{ __('super_admin.system.today') }}</option>
                    <option value="yesterday">{{ __('super_admin.system.yesterday') }}</option>
                    <option value="week">{{ __('super_admin.system.last_7_days') }}</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Logs List -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
        <!-- Loading -->
        <div x-show="loading" class="p-8 text-center">
            <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
            <p class="mt-2 text-gray-600 dark:text-gray-400">{{ __('common.loading') }}</p>
        </div>

        <!-- Logs -->
        <div x-show="!loading" class="divide-y divide-gray-200 dark:divide-gray-700">
            <template x-for="log in logs" :key="log.id">
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition cursor-pointer"
                     @click="expandedLog = expandedLog === log.id ? null : log.id">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0"
                             :class="{
                                 'bg-red-100 dark:bg-red-900/30': ['emergency', 'alert', 'critical', 'error'].includes(log.level),
                                 'bg-yellow-100 dark:bg-yellow-900/30': log.level === 'warning',
                                 'bg-blue-100 dark:bg-blue-900/30': ['notice', 'info'].includes(log.level),
                                 'bg-gray-100 dark:bg-gray-700': log.level === 'debug'
                             }">
                            <i class="fas text-sm"
                               :class="{
                                   'fa-times-circle text-red-600': ['emergency', 'alert', 'critical', 'error'].includes(log.level),
                                   'fa-exclamation-triangle text-yellow-600': log.level === 'warning',
                                   'fa-info-circle text-blue-600': ['notice', 'info'].includes(log.level),
                                   'fa-bug text-gray-600': log.level === 'debug'
                               }"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium uppercase"
                                          :class="{
                                              'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400': ['emergency', 'alert', 'critical', 'error'].includes(log.level),
                                              'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400': log.level === 'warning',
                                              'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': ['notice', 'info'].includes(log.level),
                                              'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': log.level === 'debug'
                                          }"
                                          x-text="log.level"></span>
                                    <span class="text-xs text-gray-500" x-text="log.date"></span>
                                </div>
                                <i class="fas text-gray-400 transition-transform"
                                   :class="{ 'fa-chevron-down': expandedLog !== log.id, 'fa-chevron-up': expandedLog === log.id }"></i>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-white mt-1 truncate" x-text="log.message"></p>
                        </div>
                    </div>

                    <!-- Expanded Content -->
                    <div x-show="expandedLog === log.id"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 -translate-y-2"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="mt-4 {{ $isRtl ? 'mr-11' : 'ml-11' }}">
                        <div class="bg-gray-900 rounded-lg p-4 overflow-x-auto">
                            <pre class="text-sm text-gray-300 whitespace-pre-wrap font-mono" x-text="log.stack || log.context || '{{ __('super_admin.system.no_stack_trace') }}'"></pre>
                        </div>
                        <div class="mt-3 flex items-center gap-4 text-sm">
                            <span class="text-gray-500">
                                <i class="fas fa-file {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                <span x-text="log.file || '-'"></span>
                            </span>
                            <span class="text-gray-500">
                                <i class="fas fa-hashtag {{ $isRtl ? 'ml-1' : 'mr-1' }}"></i>
                                <span x-text="'Line ' + (log.line || '-')"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Empty State -->
        <div x-show="!loading && logs.length === 0" class="p-8 text-center">
            <i class="fas fa-check-circle text-5xl text-green-500 mb-4"></i>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">{{ __('super_admin.system.no_logs') }}</h3>
            <p class="text-gray-600 dark:text-gray-400">{{ __('super_admin.system.no_logs_description') }}</p>
        </div>

        <!-- Pagination -->
        <div x-show="!loading && pagination.total > pagination.per_page" class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ __('super_admin.pagination.showing') }}
                    <span x-text="((pagination.current_page - 1) * pagination.per_page) + 1"></span>
                    {{ __('super_admin.pagination.to') }}
                    <span x-text="Math.min(pagination.current_page * pagination.per_page, pagination.total)"></span>
                    {{ __('super_admin.pagination.of') }}
                    <span x-text="pagination.total"></span>
                </p>
                <div class="flex items-center gap-2">
                    <button @click="goToPage(pagination.current_page - 1)"
                            :disabled="pagination.current_page === 1"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <i class="fas {{ $isRtl ? 'fa-chevron-right' : 'fa-chevron-left' }}"></i>
                    </button>
                    <button @click="goToPage(pagination.current_page + 1)"
                            :disabled="pagination.current_page === pagination.last_page"
                            class="px-3 py-1.5 text-sm border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                        <i class="fas {{ $isRtl ? 'fa-chevron-left' : 'fa-chevron-right' }}"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function logsViewer() {
    return {
        loading: false,
        logs: [],
        expandedLog: null,
        filters: {
            search: '',
            level: '',
            date: 'today'
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            per_page: 50,
            total: 0
        },

        async loadLogs() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    page: this.pagination.current_page,
                    ...this.filters
                });

                const response = await fetch(`{{ route('super-admin.system.logs') }}?${params}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                this.logs = data.logs || [];

                if (data.pagination) {
                    this.pagination = data.pagination;
                }
            } catch (error) {
                console.error('Error loading logs:', error);
            } finally {
                this.loading = false;
            }
        },

        async clearLogs() {
            if (!confirm('{{ __('super_admin.system.clear_logs_confirm') }}')) return;

            try {
                const response = await fetch('{{ route('super-admin.system.logs') }}', {
                    method: 'DELETE',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (response.ok) {
                    this.logs = [];
                    this.pagination.total = 0;
                }
            } catch (error) {
                console.error('Error clearing logs:', error);
            }
        },

        goToPage(page) {
            if (page < 1 || page > this.pagination.last_page) return;
            this.pagination.current_page = page;
            this.loadLogs();
        }
    };
}
</script>
@endpush
