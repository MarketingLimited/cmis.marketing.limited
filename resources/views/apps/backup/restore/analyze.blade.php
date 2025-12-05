@extends('layouts.app')

@section('title', __('backup.analyze_backup'))

@section('content')
<div x-data="analyzeBackup()" class="container mx-auto px-4 py-6">
    <!-- Header with Steps -->
    @include('apps.backup.restore.partials.wizard-steps', ['currentStep' => 1])

    <!-- Backup Info -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        @if($backup->is_encrypted)
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900">
                            <i class="fas fa-lock text-purple-600 dark:text-purple-400 text-xl"></i>
                        </span>
                        @else
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900">
                            <i class="fas fa-database text-blue-600 dark:text-blue-400 text-xl"></i>
                        </span>
                        @endif
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $backup->name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $backup->backup_code }} &bull;
                            {{ __('backup.created_at') }}: {{ $backup->created_at->format('Y-m-d H:i') }}
                        </p>
                    </div>
                </div>
                <div class="text-end">
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('backup.file_size') }}</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ formatBytes($backup->file_size) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Analysis Results -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                <i class="fas fa-search me-2 text-blue-500"></i>
                {{ __('backup.compatibility_analysis') }}
            </h2>
        </div>

        <div class="p-6">
            @php
                $reconciliation = $analysis['reconciliation'] ?? [];
                $isCompatible = ($reconciliation['is_fully_compatible'] ?? false);
                $isPartial = ($reconciliation['is_partially_compatible'] ?? false);
                $summary = $reconciliation['summary'] ?? [];
            @endphp

            <!-- Overall Status -->
            <div class="mb-6">
                @if($isCompatible)
                <div class="flex items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                    </div>
                    <div class="ms-4">
                        <h3 class="text-lg font-medium text-green-800 dark:text-green-200">
                            {{ __('backup.fully_compatible') }}
                        </h3>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            {{ __('backup.fully_compatible_desc') }}
                        </p>
                    </div>
                </div>
                @elseif($isPartial)
                <div class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg border border-yellow-200 dark:border-yellow-800">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl"></i>
                    </div>
                    <div class="ms-4">
                        <h3 class="text-lg font-medium text-yellow-800 dark:text-yellow-200">
                            {{ __('backup.partially_compatible') }}
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            {{ __('backup.partially_compatible_desc') }}
                        </p>
                    </div>
                </div>
                @else
                <div class="flex items-center p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                    <div class="flex-shrink-0">
                        <i class="fas fa-times-circle text-red-500 text-2xl"></i>
                    </div>
                    <div class="ms-4">
                        <h3 class="text-lg font-medium text-red-800 dark:text-red-200">
                            {{ __('backup.incompatible') }}
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-300">
                            {{ __('backup.incompatible_desc') }}
                        </p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-green-700 dark:text-green-300">
                            {{ __('backup.compatible_categories') }}
                        </span>
                        <span class="text-2xl font-bold text-green-800 dark:text-green-200">
                            {{ $summary['compatible_count'] ?? 0 }}
                        </span>
                    </div>
                </div>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-yellow-700 dark:text-yellow-300">
                            {{ __('backup.partial_categories') }}
                        </span>
                        <span class="text-2xl font-bold text-yellow-800 dark:text-yellow-200">
                            {{ $summary['partial_count'] ?? 0 }}
                        </span>
                    </div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-red-700 dark:text-red-300">
                            {{ __('backup.incompatible_categories') }}
                        </span>
                        <span class="text-2xl font-bold text-red-800 dark:text-red-200">
                            {{ $summary['incompatible_count'] ?? 0 }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Category Details -->
            <div class="space-y-4">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                    {{ __('backup.category_details') }}
                </h3>

                <!-- Compatible Categories -->
                @if(!empty($reconciliation['compatible']))
                <div x-data="{ open: false }" class="border border-green-200 dark:border-green-800 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-green-50 dark:bg-green-900/20 text-start">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle text-green-500"></i>
                            <span class="font-medium text-green-800 dark:text-green-200">
                                {{ __('backup.compatible') }} ({{ count(array_merge(...array_values($reconciliation['compatible']))) }})
                            </span>
                        </div>
                        <i :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-green-500"></i>
                    </button>
                    <div x-show="open" x-collapse class="px-4 py-3 bg-white dark:bg-gray-800">
                        <div class="flex flex-wrap gap-2">
                            @foreach($reconciliation['compatible'] as $category => $tables)
                                @foreach($tables as $table)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    {{ __('backup.category_' . Str::snake($table)) }}
                                </span>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif

                <!-- Partially Compatible Categories -->
                @if(!empty($reconciliation['partially_compatible']))
                <div x-data="{ open: true }" class="border border-yellow-200 dark:border-yellow-800 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-yellow-50 dark:bg-yellow-900/20 text-start">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                            <span class="font-medium text-yellow-800 dark:text-yellow-200">
                                {{ __('backup.partially_compatible') }} ({{ count($reconciliation['partially_compatible']) }})
                            </span>
                        </div>
                        <i :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-yellow-500"></i>
                    </button>
                    <div x-show="open" x-collapse class="px-4 py-3 bg-white dark:bg-gray-800">
                        @foreach($reconciliation['partially_compatible'] as $category => $tables)
                            @foreach($tables as $table => $diff)
                            <div class="mb-3 last:mb-0">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                                    {{ __('backup.category_' . Str::snake($table)) }}
                                </h4>
                                @if(!empty($diff['issues']))
                                <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                                    @foreach($diff['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Incompatible Categories -->
                @if(!empty($reconciliation['incompatible']))
                <div x-data="{ open: true }" class="border border-red-200 dark:border-red-800 rounded-lg overflow-hidden">
                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3 bg-red-50 dark:bg-red-900/20 text-start">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-times-circle text-red-500"></i>
                            <span class="font-medium text-red-800 dark:text-red-200">
                                {{ __('backup.incompatible') }} ({{ count($reconciliation['incompatible']) }})
                            </span>
                        </div>
                        <i :class="open ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-red-500"></i>
                    </button>
                    <div x-show="open" x-collapse class="px-4 py-3 bg-white dark:bg-gray-800">
                        @foreach($reconciliation['incompatible'] as $category => $tables)
                            @foreach($tables as $table => $info)
                            <div class="mb-3 last:mb-0">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                    {{ __('backup.category_' . Str::snake($table)) }}
                                </h4>
                                <p class="text-sm text-red-600 dark:text-red-400">
                                    {{ $info['message'] ?? $info['reason'] }}
                                </p>
                            </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Warnings -->
            @if(!empty($reconciliation['warnings']))
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                    {{ __('backup.warnings') }}
                </h3>
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <ul class="list-disc list-inside text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                        @foreach($reconciliation['warnings'] as $table => $warnings)
                            @foreach($warnings as $warning)
                            <li><strong>{{ $table }}:</strong> {{ $warning }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            <!-- Conflict Preview -->
            @if(!empty($analysis['conflict_preview']) && ($analysis['conflict_preview']['total'] ?? 0) > 0)
            <div class="mt-6">
                <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">
                    {{ __('backup.conflict_preview') }}
                </h3>
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                    <div class="flex items-center gap-3 mb-2">
                        <i class="fas fa-exchange-alt text-orange-500"></i>
                        <span class="text-lg font-medium text-orange-800 dark:text-orange-200">
                            {{ number_format($analysis['conflict_preview']['total']) }}
                            {{ __('backup.potential_conflicts') }}
                        </span>
                    </div>
                    <p class="text-sm text-orange-700 dark:text-orange-300">
                        {{ __('backup.conflicts_will_resolve_later') }}
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between">
        <a href="{{ route('backup.restore.index', ['org' => $org]) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <i class="fas fa-arrow-start me-2"></i>
            {{ __('common.back') }}
        </a>

        @if($isCompatible || $isPartial)
        <a href="{{ route('backup.restore.select', ['org' => $org, 'restore' => $restore->id]) }}"
           class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
            {{ __('backup.continue_to_selection') }}
            <i class="fas fa-arrow-end ms-2"></i>
        </a>
        @else
        <button disabled
                class="inline-flex items-center px-6 py-2 bg-gray-400 text-white rounded-lg cursor-not-allowed opacity-50">
            {{ __('backup.cannot_restore') }}
        </button>
        @endif
    </div>
</div>

<script>
function analyzeBackup() {
    return {
        init() {
            // Initialize component
        }
    };
}
</script>
@endsection
