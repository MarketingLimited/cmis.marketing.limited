@extends('layouts.app')

@section('title', __('backup.select_data'))

@section('content')
<div x-data="selectCategories()" class="container mx-auto px-4 py-6">
    <!-- Header with Steps -->
    @include('apps.backup.restore.partials.wizard-steps', ['currentStep' => 2])

    <!-- Restore Type Selection -->
    <form action="{{ route('backup.restore.select.store', ['org' => $org, 'restore' => $restore->id]) }}"
          method="POST"
          @submit="handleSubmit">
        @csrf

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-cog me-2 text-blue-500"></i>
                    {{ __('backup.restore_type') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('backup.choose_restore_type') }}
                </p>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Selective Restore -->
                    <label :class="type === 'selective' ? 'ring-2 ring-blue-500' : ''"
                           class="relative flex flex-col p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="radio" name="type" value="selective" x-model="type" class="sr-only">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900">
                                <i class="fas fa-list-check text-blue-600 dark:text-blue-400"></i>
                            </span>
                            <span class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ __('backup.type_selective') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('backup.type_selective_desc') }}
                        </p>
                        <span class="absolute top-4 end-4" x-show="type === 'selective'">
                            <i class="fas fa-check-circle text-blue-500"></i>
                        </span>
                    </label>

                    <!-- Merge Restore -->
                    <label :class="type === 'merge' ? 'ring-2 ring-purple-500' : ''"
                           class="relative flex flex-col p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input type="radio" name="type" value="merge" x-model="type" class="sr-only">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900">
                                <i class="fas fa-code-merge text-purple-600 dark:text-purple-400"></i>
                            </span>
                            <span class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ __('backup.type_merge') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('backup.type_merge_desc') }}
                        </p>
                        <span class="absolute top-4 end-4" x-show="type === 'merge'">
                            <i class="fas fa-check-circle text-purple-500"></i>
                        </span>
                    </label>

                    <!-- Full Restore -->
                    <label :class="type === 'full' ? 'ring-2 ring-red-500' : ''"
                           class="relative flex flex-col p-4 border border-red-200 dark:border-red-800 rounded-lg cursor-pointer hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <input type="radio" name="type" value="full" x-model="type" class="sr-only">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-red-100 dark:bg-red-900">
                                <i class="fas fa-database text-red-600 dark:text-red-400"></i>
                            </span>
                            <span class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ __('backup.type_full') }}
                            </span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('backup.type_full_desc') }}
                        </p>
                        <span class="absolute top-4 end-4" x-show="type === 'full'">
                            <i class="fas fa-check-circle text-red-500"></i>
                        </span>
                    </label>
                </div>

                <!-- Full Restore Warning -->
                <div x-show="type === 'full'" x-transition class="mt-4">
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-500"></i>
                            </div>
                            <div class="ms-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">
                                    {{ __('backup.full_restore_warning_title') }}
                                </h3>
                                <p class="mt-1 text-sm text-red-700 dark:text-red-300">
                                    {{ __('backup.full_restore_warning') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Category Selection (for selective restore) -->
        <div x-show="type === 'selective'" x-transition class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <i class="fas fa-list-check me-2 text-green-500"></i>
                            {{ __('backup.select_categories') }}
                        </h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('backup.select_categories_desc') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="selectAll" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            {{ __('backup.select_all') }}
                        </button>
                        <span class="text-gray-300 dark:text-gray-600">|</span>
                        <button type="button" @click="deselectAll" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                            {{ __('backup.deselect_all') }}
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-6">
                @php
                    $categories = $reconciliation['compatible'] ?? [];
                    $partialCategories = $reconciliation['partially_compatible'] ?? [];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($categories as $categoryGroup => $tables)
                        @foreach($tables as $table)
                        @php
                            $categoryName = Str::snake($table);
                            $summary = $backup->summary['categories'][$table] ?? [];
                        @endphp
                        <label class="relative flex items-start p-4 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <div class="flex items-center h-5">
                                <input type="checkbox"
                                       name="categories[]"
                                       value="{{ $table }}"
                                       x-model="selectedCategories"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </div>
                            <div class="ms-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.category_' . $categoryName) }}
                                </span>
                                @if(!empty($summary))
                                <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>
                                        <i class="fas fa-list me-1"></i>
                                        {{ number_format($summary['count'] ?? 0) }} {{ __('backup.records') }}
                                    </span>
                                    @if(isset($summary['size_kb']))
                                    <span>
                                        <i class="fas fa-file me-1"></i>
                                        {{ number_format($summary['size_kb']) }} KB
                                    </span>
                                    @endif
                                </div>
                                @endif
                            </div>
                            <span class="absolute top-4 end-4">
                                <i class="fas fa-check-circle text-green-500" title="{{ __('backup.compatible') }}"></i>
                            </span>
                        </label>
                        @endforeach
                    @endforeach

                    @foreach($partialCategories as $categoryGroup => $tables)
                        @foreach($tables as $table => $diff)
                        @php
                            $categoryName = Str::snake($table);
                            $summary = $backup->summary['categories'][$table] ?? [];
                        @endphp
                        <label class="relative flex items-start p-4 border border-yellow-200 dark:border-yellow-700 rounded-lg cursor-pointer hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors">
                            <div class="flex items-center h-5">
                                <input type="checkbox"
                                       name="categories[]"
                                       value="{{ $table }}"
                                       x-model="selectedCategories"
                                       class="h-4 w-4 text-yellow-600 border-gray-300 rounded focus:ring-yellow-500">
                            </div>
                            <div class="ms-3">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.category_' . $categoryName) }}
                                </span>
                                @if(!empty($summary))
                                <div class="mt-1 flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                    <span>
                                        <i class="fas fa-list me-1"></i>
                                        {{ number_format($summary['count'] ?? 0) }} {{ __('backup.records') }}
                                    </span>
                                </div>
                                @endif
                                @if(!empty($diff['warnings']))
                                <p class="mt-1 text-xs text-yellow-600 dark:text-yellow-400">
                                    {{ $diff['warnings'][0] ?? '' }}
                                </p>
                                @endif
                            </div>
                            <span class="absolute top-4 end-4">
                                <i class="fas fa-exclamation-triangle text-yellow-500" title="{{ __('backup.partially_compatible') }}"></i>
                            </span>
                        </label>
                        @endforeach
                    @endforeach
                </div>

                <!-- Selected Summary -->
                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('backup.selected_categories') }}:
                        </span>
                        <span class="text-lg font-semibold text-gray-900 dark:text-white" x-text="selectedCategories.length"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conflict Summary -->
        @if(!empty($conflictPreview) && ($conflictPreview['total'] ?? 0) > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-exchange-alt me-2 text-orange-500"></i>
                    {{ __('backup.conflicts_detected') }}
                </h2>
            </div>
            <div class="p-6">
                <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg p-4">
                    <p class="text-sm text-orange-700 dark:text-orange-300">
                        {{ __('backup.conflicts_detected_count', ['count' => number_format($conflictPreview['total'])]) }}
                        {{ __('backup.conflicts_resolve_next_step') }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="{{ route('backup.restore.analyze', ['org' => $org, 'backup' => $backup->id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-start me-2"></i>
                {{ __('common.back') }}
            </a>

            <button type="submit"
                    :disabled="type === 'selective' && selectedCategories.length === 0"
                    :class="(type === 'selective' && selectedCategories.length === 0) ? 'opacity-50 cursor-not-allowed' : ''"
                    class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                {{ __('backup.continue') }}
                <i class="fas fa-arrow-end ms-2"></i>
            </button>
        </div>
    </form>
</div>

<script>
function selectCategories() {
    return {
        type: '{{ $restore->type ?? "selective" }}',
        selectedCategories: @json($restore->selected_categories ?? []),

        selectAll() {
            const checkboxes = document.querySelectorAll('input[name="categories[]"]');
            this.selectedCategories = Array.from(checkboxes).map(cb => cb.value);
        },

        deselectAll() {
            this.selectedCategories = [];
        },

        handleSubmit(event) {
            if (this.type === 'selective' && this.selectedCategories.length === 0) {
                event.preventDefault();
                alert('{{ __("backup.select_at_least_one") }}');
            }
        }
    };
}
</script>
@endsection
