@extends('layouts.app')

@section('title', __('backup.restore_title'))

@section('content')
<div x-data="restoreIndex()" class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('backup.restore_title') }}
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('backup.restore_description') }}
            </p>
        </div>
        <div class="mt-4 md:mt-0 flex items-center gap-3">
            <a href="{{ route('backup.restore.upload', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                <i class="fas fa-upload me-2"></i>
                {{ __('backup.upload_external') }}
            </a>
            <a href="{{ route('backup.index', ['org' => $org]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-start me-2"></i>
                {{ __('backup.back_to_backups') }}
            </a>
        </div>
    </div>

    <!-- Restore History -->
    @if($restores->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-history me-2 text-blue-500"></i>
                {{ __('backup.recent_restores') }}
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('backup.restore_code') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('backup.backup_name') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('backup.type') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('backup.status') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('backup.date') }}
                        </th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            {{ __('backup.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($restores as $restore)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="font-mono text-sm text-gray-900 dark:text-white">
                                {{ $restore->restore_code }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-900 dark:text-white">
                                {{ $restore->backup?->name ?? __('backup.external_backup') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @include('apps.backup.partials.restore-type-badge', ['type' => $restore->type])
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @include('apps.backup.partials.restore-status-badge', ['status' => $restore->status])
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $restore->created_at->format('Y-m-d H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-end">
                            @if(in_array($restore->status, ['pending', 'analyzing', 'awaiting_confirmation']))
                            <a href="{{ route('backup.restore.select', ['org' => $org, 'restore' => $restore->id]) }}"
                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ __('backup.continue') }}
                            </a>
                            @elseif($restore->status === 'processing')
                            <a href="{{ route('backup.restore.progress', ['org' => $org, 'restore' => $restore->id]) }}"
                               class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                {{ __('backup.view_progress') }}
                            </a>
                            @elseif($restore->status === 'completed')
                            <a href="{{ route('backup.restore.complete', ['org' => $org, 'restore' => $restore->id]) }}"
                               class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-300">
                                {{ __('backup.view_result') }}
                            </a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Available Backups -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-database me-2 text-green-500"></i>
                {{ __('backup.available_backups') }}
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ __('backup.select_backup_to_restore') }}
            </p>
        </div>

        @if($backups->isEmpty())
        <div class="px-6 py-12 text-center">
            <i class="fas fa-box-open text-4xl text-gray-400 mb-4"></i>
            <p class="text-gray-500 dark:text-gray-400">
                {{ __('backup.no_backups_available') }}
            </p>
            <a href="{{ route('backup.create', ['org' => $org]) }}"
               class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="fas fa-plus me-2"></i>
                {{ __('backup.create_backup') }}
            </a>
        </div>
        @else
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($backups as $backup)
            <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                @if($backup->is_encrypted)
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900">
                                    <i class="fas fa-lock text-purple-600 dark:text-purple-400"></i>
                                </span>
                                @else
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-100 dark:bg-green-900">
                                    <i class="fas fa-database text-green-600 dark:text-green-400"></i>
                                </span>
                                @endif
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $backup->name }}
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $backup->backup_code }} &bull;
                                    {{ $backup->created_at->format('Y-m-d H:i') }}
                                </p>
                            </div>
                        </div>
                        @if($backup->description)
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 ms-13">
                            {{ Str::limit($backup->description, 100) }}
                        </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-4">
                        <!-- Backup Stats -->
                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                            <span title="{{ __('backup.file_size') }}">
                                <i class="fas fa-file me-1"></i>
                                {{ formatBytes($backup->file_size) }}
                            </span>
                            @if($backup->summary)
                            <span title="{{ __('backup.total_records') }}">
                                <i class="fas fa-list me-1"></i>
                                {{ number_format($backup->summary['total_records'] ?? 0) }}
                            </span>
                            @endif
                        </div>

                        <!-- Restore Button -->
                        <a href="{{ route('backup.restore.analyze', ['org' => $org, 'backup' => $backup->id]) }}"
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-undo me-2"></i>
                            {{ __('backup.restore') }}
                        </a>
                    </div>
                </div>

                <!-- Categories Preview -->
                @if($backup->summary && !empty($backup->summary['categories']))
                <div class="mt-3 ms-13">
                    <div class="flex flex-wrap gap-2">
                        @foreach(array_slice(array_keys($backup->summary['categories']), 0, 5) as $category)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            {{ __('backup.category_' . Str::snake($category)) }}
                        </span>
                        @endforeach
                        @if(count($backup->summary['categories']) > 5)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            +{{ count($backup->summary['categories']) - 5 }} {{ __('backup.more') }}
                        </span>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($backups->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $backups->links() }}
        </div>
        @endif
        @endif
    </div>
</div>

<script>
function restoreIndex() {
    return {
        init() {
            // Initialize component
        }
    };
}
</script>
@endsection
