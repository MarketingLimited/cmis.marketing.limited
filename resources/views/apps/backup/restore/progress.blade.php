@extends('layouts.app')

@section('title', __('backup.restore_progress'))

@section('content')
<div x-data="restoreProgress()" x-init="startPolling()" class="container mx-auto px-4 py-6">
    <!-- Header with Steps -->
    @include('apps.backup.restore.partials.wizard-steps', ['currentStep' => 5])

    <!-- Progress Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div x-show="status === 'processing'" class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 animate-pulse">
                            <i class="fas fa-sync fa-spin text-blue-600 dark:text-blue-400 text-xl"></i>
                        </span>
                    </div>
                    <div x-show="status === 'completed'" class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-green-100 dark:bg-green-900">
                            <i class="fas fa-check text-green-600 dark:text-green-400 text-xl"></i>
                        </span>
                    </div>
                    <div x-show="status === 'failed'" class="flex-shrink-0">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-red-100 dark:bg-red-900">
                            <i class="fas fa-times text-red-600 dark:text-red-400 text-xl"></i>
                        </span>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span x-show="status === 'processing'">{{ __('backup.restore_in_progress') }}</span>
                            <span x-show="status === 'completed'">{{ __('backup.restore_completed') }}</span>
                            <span x-show="status === 'failed'">{{ __('backup.restore_failed') }}</span>
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('backup.restore_code') }}: {{ $restore->restore_code }}
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    <p class="text-2xl font-bold" :class="{
                        'text-blue-600 dark:text-blue-400': status === 'processing',
                        'text-green-600 dark:text-green-400': status === 'completed',
                        'text-red-600 dark:text-red-400': status === 'failed'
                    }" x-text="progress + '%'"></p>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Progress Bar -->
            <div class="mb-6">
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="{
                             'bg-blue-600': status === 'processing',
                             'bg-green-500': status === 'completed',
                             'bg-red-500': status === 'failed'
                         }"
                         :style="'width: ' + progress + '%'"></div>
                </div>
            </div>

            <!-- Current Step -->
            <div x-show="currentStep" class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div class="flex items-center gap-3">
                    <i class="fas fa-spinner fa-spin text-blue-500"></i>
                    <span class="text-sm text-gray-700 dark:text-gray-300" x-text="currentStep"></span>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="text-2xl font-bold text-green-800 dark:text-green-200" x-text="stats.records_restored || 0"></div>
                    <div class="text-sm text-green-700 dark:text-green-300">{{ __('backup.records_restored') }}</div>
                </div>
                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                    <div class="text-2xl font-bold text-blue-800 dark:text-blue-200" x-text="stats.records_updated || 0"></div>
                    <div class="text-sm text-blue-700 dark:text-blue-300">{{ __('backup.records_updated') }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                    <div class="text-2xl font-bold text-gray-800 dark:text-gray-200" x-text="stats.records_skipped || 0"></div>
                    <div class="text-sm text-gray-700 dark:text-gray-300">{{ __('backup.records_skipped') }}</div>
                </div>
                <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                    <div class="text-2xl font-bold text-purple-800 dark:text-purple-200" x-text="stats.files_restored || 0"></div>
                    <div class="text-sm text-purple-700 dark:text-purple-300">{{ __('backup.files_restored') }}</div>
                </div>
            </div>

            <!-- Category Progress -->
            <div x-show="categories.length > 0" class="mb-6">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                    {{ __('backup.category_progress') }}
                </h3>
                <div class="space-y-2">
                    <template x-for="category in categories" :key="category.name">
                        <div class="flex items-center gap-3">
                            <div class="w-32 text-sm text-gray-600 dark:text-gray-400 truncate" x-text="category.name"></div>
                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                     :style="'width: ' + category.progress + '%'"></div>
                            </div>
                            <div class="w-16 text-end text-sm" :class="{
                                'text-gray-500 dark:text-gray-400': category.progress < 100,
                                'text-green-600 dark:text-green-400': category.progress === 100
                            }" x-text="category.progress + '%'"></div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Error Message -->
            <div x-show="status === 'failed' && errorMessage" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('backup.error_occurred') }}</p>
                        <p class="text-sm text-red-700 dark:text-red-300 mt-1" x-text="errorMessage"></p>
                    </div>
                </div>
            </div>

            <!-- Time Info -->
            <div class="mt-6 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                <span>
                    <i class="fas fa-clock me-1"></i>
                    {{ __('backup.started_at') }}: <span x-text="startedAt || '-'"></span>
                </span>
                <span x-show="elapsedTime">
                    {{ __('backup.elapsed') }}: <span x-text="elapsedTime"></span>
                </span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('backup.restore.index', ['org' => $org]) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-list me-2"></i>
            {{ __('backup.back_to_restores') }}
        </a>

        <div class="flex items-center gap-3">
            <!-- View Result (when completed) -->
            <a x-show="status === 'completed'"
               href="{{ route('backup.restore.complete', ['org' => $org, 'restore' => $restore->id]) }}"
               class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <i class="fas fa-check-circle me-2"></i>
                {{ __('backup.view_result') }}
            </a>

            <!-- Retry (when failed) -->
            <button x-show="status === 'failed'"
                    @click="retryRestore"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-redo me-2"></i>
                {{ __('backup.retry') }}
            </button>
        </div>
    </div>
</div>

<script>
function restoreProgress() {
    return {
        status: '{{ $restore->status }}',
        progress: 0,
        currentStep: '',
        stats: {},
        categories: [],
        errorMessage: '',
        startedAt: '{{ $restore->started_at?->format("Y-m-d H:i:s") }}',
        elapsedTime: '',
        pollInterval: null,
        startTime: Date.now(),

        startPolling() {
            // Initial fetch
            this.fetchProgress();

            // Poll every 2 seconds if processing
            if (this.status === 'processing' || this.status === 'pending') {
                this.pollInterval = setInterval(() => {
                    this.fetchProgress();
                    this.updateElapsedTime();
                }, 2000);
            }
        },

        async fetchProgress() {
            try {
                const response = await fetch('{{ route("backup.restore.progress.status", ["org" => $org, "restore" => $restore->id]) }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success && data.data) {
                    this.status = data.data.status || this.status;
                    this.progress = data.data.progress || 0;
                    this.currentStep = data.data.current_step || '';
                    this.stats = data.data.stats || {};
                    this.categories = data.data.categories || [];
                    this.errorMessage = data.data.error_message || '';

                    // Stop polling if completed or failed
                    if (this.status === 'completed' || this.status === 'failed') {
                        this.stopPolling();
                    }
                }
            } catch (error) {
                console.error('Failed to fetch progress:', error);
            }
        },

        stopPolling() {
            if (this.pollInterval) {
                clearInterval(this.pollInterval);
                this.pollInterval = null;
            }
        },

        updateElapsedTime() {
            const elapsed = Math.floor((Date.now() - this.startTime) / 1000);
            const minutes = Math.floor(elapsed / 60);
            const seconds = elapsed % 60;
            this.elapsedTime = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        },

        async retryRestore() {
            if (!confirm('{{ __("backup.confirm_retry") }}')) return;

            try {
                const response = await fetch('{{ route("backup.restore.process", ["org" => $org, "restore" => $restore->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    this.status = 'processing';
                    this.progress = 0;
                    this.startTime = Date.now();
                    this.startPolling();
                } else {
                    alert('{{ __("backup.retry_failed") }}');
                }
            } catch (error) {
                console.error('Retry failed:', error);
                alert('{{ __("backup.retry_failed") }}');
            }
        }
    };
}
</script>
@endsection
