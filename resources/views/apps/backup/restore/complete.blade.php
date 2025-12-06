@extends('layouts.admin')

@section('title', __('backup.restore_complete'))

@section('content')
<div x-data="restoreComplete()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3 rtl:space-x-reverse">
                <li>
                    <a href="{{ route('orgs.backup.index', ['org' => $org]) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ __('backup.backups') }}
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                    <a href="{{ route('orgs.backup.restore.index', ['org' => $org]) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
                        {{ __('backup.restore') }}
                    </a>
                </li>
                <li>
                    <span class="mx-2 text-gray-400">/</span>
                    <span class="text-gray-900 dark:text-white">{{ __('backup.result') }}</span>
                </li>
            </ol>
        </nav>
    </div>

    <!-- Result Card -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <!-- Status Header -->
        @if($restore->status === 'completed')
        <div class="px-6 py-8 bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800 text-center">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900 mb-4">
                <i class="fas fa-check text-green-600 dark:text-green-400 text-3xl"></i>
            </span>
            <h1 class="text-2xl font-bold text-green-800 dark:text-green-200">
                {{ __('backup.restore_successful') }}
            </h1>
            <p class="mt-2 text-green-700 dark:text-green-300">
                {{ __('backup.restore_successful_desc') }}
            </p>
        </div>
        @elseif($restore->status === 'failed')
        <div class="px-6 py-8 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800 text-center">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-100 dark:bg-red-900 mb-4">
                <i class="fas fa-times text-red-600 dark:text-red-400 text-3xl"></i>
            </span>
            <h1 class="text-2xl font-bold text-red-800 dark:text-red-200">
                {{ __('backup.restore_failed') }}
            </h1>
            <p class="mt-2 text-red-700 dark:text-red-300">
                {{ __('backup.restore_failed_desc') }}
            </p>
        </div>
        @elseif($restore->status === 'rolled_back')
        <div class="px-6 py-8 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-200 dark:border-orange-800 text-center">
            <span class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100 dark:bg-orange-900 mb-4">
                <i class="fas fa-undo text-orange-600 dark:text-orange-400 text-3xl"></i>
            </span>
            <h1 class="text-2xl font-bold text-orange-800 dark:text-orange-200">
                {{ __('backup.restore_rolled_back') }}
            </h1>
            <p class="mt-2 text-orange-700 dark:text-orange-300">
                {{ __('backup.restore_rolled_back_desc') }}
            </p>
        </div>
        @endif

        <div class="p-6">
            @php
                $report = $restore->execution_report ?? [];
            @endphp

            <!-- Summary Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($report['records_restored'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.records_restored') }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($report['records_updated'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.records_updated') }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($report['records_skipped'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.records_skipped') }}</div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ number_format($report['files_restored'] ?? 0) }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.files_restored') }}</div>
                </div>
            </div>

            <!-- Restore Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('backup.restore_details') }}
                    </h3>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.restore_code') }}:</span>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $restore->restore_code }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.type') }}:</span>
                            @include('apps.backup.partials.restore-type-badge', ['type' => $restore->type])
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.started_at') }}:</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $restore->started_at?->format('Y-m-d H:i:s') ?? '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.completed_at') }}:</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $restore->completed_at?->format('Y-m-d H:i:s') ?? '-' }}</span>
                        </div>
                        @if($restore->started_at && $restore->completed_at)
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.duration') }}:</span>
                            <span class="text-sm text-gray-900 dark:text-white">
                                {{ $restore->started_at->diffForHumans($restore->completed_at, true) }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                        {{ __('backup.source_backup') }}
                    </h3>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.backup_name') }}:</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $backup->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.backup_code') }}:</span>
                            <span class="text-sm font-mono text-gray-900 dark:text-white">{{ $backup->backup_code }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('backup.created_at') }}:</span>
                            <span class="text-sm text-gray-900 dark:text-white">{{ $backup->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            @if(!empty($report['by_category']))
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                    {{ __('backup.category_breakdown') }}
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('backup.category') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('backup.inserted') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('backup.updated') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('backup.skipped') }}</th>
                                <th class="px-4 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">{{ __('backup.errors') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($report['by_category'] as $category => $stats)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                    {{ __('backup.category_' . Str::snake($category)) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-end text-green-600 dark:text-green-400">
                                    {{ number_format($stats['inserted'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-end text-blue-600 dark:text-blue-400">
                                    {{ number_format($stats['updated'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-end text-gray-600 dark:text-gray-400">
                                    {{ number_format($stats['skipped'] ?? 0) }}
                                </td>
                                <td class="px-4 py-3 text-sm text-end">
                                    @if(!empty($stats['errors']))
                                    <span class="text-red-600 dark:text-red-400">{{ count($stats['errors']) }}</span>
                                    @else
                                    <span class="text-gray-400">0</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <!-- Errors -->
            @if(!empty($report['errors']))
            <div class="mb-6">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                    {{ __('backup.errors_encountered') }}
                </h3>
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <ul class="space-y-2">
                        @foreach(array_slice($report['errors'], 0, 10) as $error)
                        <li class="flex items-start gap-2 text-sm text-red-700 dark:text-red-300">
                            <i class="fas fa-times-circle mt-0.5 flex-shrink-0"></i>
                            <span>{{ is_array($error) ? ($error['message'] ?? json_encode($error)) : $error }}</span>
                        </li>
                        @endforeach
                    </ul>
                    @if(count($report['errors']) > 10)
                    <p class="mt-3 text-sm text-red-600 dark:text-red-400">
                        {{ __('backup.and_more_errors', ['count' => count($report['errors']) - 10]) }}
                    </p>
                    @endif
                </div>
            </div>
            @endif

            <!-- Error Message (for failed restores) -->
            @if($restore->error_message)
            <div class="mb-6">
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('backup.error_message') }}</p>
                            <p class="text-sm text-red-700 dark:text-red-300 mt-1">{{ $restore->error_message }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Rollback Option -->
            @if($restore->status === 'completed' && $restore->rollback_expires_at && $restore->rollback_expires_at->isFuture())
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-history text-yellow-500 mt-0.5"></i>
                        <div>
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                {{ __('backup.rollback_available') }}
                            </p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                {{ __('backup.rollback_expires_in', ['time' => $restore->rollback_expires_at->diffForHumans()]) }}
                            </p>
                        </div>
                    </div>
                    <button type="button"
                            @click="showRollbackModal = true"
                            class="flex-shrink-0 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                        <i class="fas fa-undo me-2"></i>
                        {{ __('backup.rollback') }}
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('orgs.backup.restore.index', ['org' => $org]) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-list me-2"></i>
            {{ __('backup.back_to_restores') }}
        </a>

        <a href="{{ route('orgs.backup.index', ['org' => $org]) }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            <i class="fas fa-home me-2"></i>
            {{ __('backup.go_to_dashboard') }}
        </a>
    </div>

    <!-- Rollback Modal -->
    <div x-show="showRollbackModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showRollbackModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showRollbackModal = false"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="showRollbackModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="p-6">
                    <div class="flex items-center gap-4 mb-4">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-yellow-100 dark:bg-yellow-900">
                            <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 text-xl"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ __('backup.confirm_rollback') }}
                            </h3>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        {{ __('backup.rollback_warning') }}
                    </p>

                    <div class="flex items-center justify-end gap-3">
                        <button type="button"
                                @click="showRollbackModal = false"
                                class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                            {{ __('common.cancel') }}
                        </button>
                        <form action="{{ route('orgs.backup.restore.rollback', ['org' => $org, 'restore' => $restore->id]) }}"
                              method="POST"
                              class="inline">
                            @csrf
                            <button type="submit"
                                    class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                                <i class="fas fa-undo me-2"></i>
                                {{ __('backup.confirm_rollback_button') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function restoreComplete() {
    return {
        showRollbackModal: false
    };
}
</script>
@endsection
