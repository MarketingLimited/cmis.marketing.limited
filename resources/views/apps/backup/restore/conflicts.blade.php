@extends('layouts.app')

@section('title', __('backup.resolve_conflicts'))

@section('content')
<div x-data="conflictResolver()" class="container mx-auto px-4 py-6">
    <!-- Header with Steps -->
    @include('apps.backup.restore.partials.wizard-steps', ['currentStep' => 3])

    <form action="{{ route('backup.restore.conflicts.store', ['org' => $org, 'restore' => $restore->id]) }}"
          method="POST"
          @submit="handleSubmit">
        @csrf

        <!-- Global Strategy Selection -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <i class="fas fa-exchange-alt me-2 text-orange-500"></i>
                    {{ __('backup.conflict_strategy') }}
                </h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('backup.conflict_strategy_desc') }}
                </p>
            </div>

            <div class="p-6">
                <!-- Conflict Stats -->
                <div class="mb-6 p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900">
                            <i class="fas fa-exclamation-triangle text-orange-600 dark:text-orange-400 text-xl"></i>
                        </span>
                        <div>
                            <p class="text-2xl font-bold text-orange-800 dark:text-orange-200">
                                {{ number_format($conflicts['total'] ?? 0) }}
                            </p>
                            <p class="text-sm text-orange-700 dark:text-orange-300">
                                {{ __('backup.total_conflicts') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Strategy Buttons -->
                <input type="hidden" name="strategy" :value="globalStrategy">

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <!-- Skip Strategy -->
                    <button type="button"
                            @click="setGlobalStrategy('skip')"
                            :class="globalStrategy === 'skip' ? 'ring-2 ring-gray-500 bg-gray-50 dark:bg-gray-700' : ''"
                            class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 mb-3">
                            <i class="fas fa-forward text-gray-600 dark:text-gray-400 text-xl"></i>
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('backup.strategy_skip') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('backup.strategy_skip_desc') }}
                        </span>
                    </button>

                    <!-- Replace Strategy -->
                    <button type="button"
                            @click="setGlobalStrategy('replace')"
                            :class="globalStrategy === 'replace' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : ''"
                            class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 mb-3">
                            <i class="fas fa-sync text-blue-600 dark:text-blue-400 text-xl"></i>
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('backup.strategy_replace') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('backup.strategy_replace_desc') }}
                        </span>
                    </button>

                    <!-- Merge Strategy -->
                    <button type="button"
                            @click="setGlobalStrategy('merge')"
                            :class="globalStrategy === 'merge' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : ''"
                            class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-purple-100 dark:bg-purple-900 mb-3">
                            <i class="fas fa-code-merge text-purple-600 dark:text-purple-400 text-xl"></i>
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('backup.strategy_merge') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('backup.strategy_merge_desc') }}
                        </span>
                    </button>

                    <!-- Ask Strategy -->
                    <button type="button"
                            @click="setGlobalStrategy('ask')"
                            :class="globalStrategy === 'ask' ? 'ring-2 ring-orange-500 bg-orange-50 dark:bg-orange-900/20' : ''"
                            class="flex flex-col items-center p-4 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors text-center">
                        <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-100 dark:bg-orange-900 mb-3">
                            <i class="fas fa-question text-orange-600 dark:text-orange-400 text-xl"></i>
                        </span>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ __('backup.strategy_ask') }}
                        </span>
                        <span class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ __('backup.strategy_ask_desc') }}
                        </span>
                    </button>
                </div>

                <!-- Strategy Explanation -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <template x-if="globalStrategy === 'skip'">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-gray-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.strategy_skip_explanation_title') }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('backup.strategy_skip_explanation') }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template x-if="globalStrategy === 'replace'">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.strategy_replace_explanation_title') }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('backup.strategy_replace_explanation') }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template x-if="globalStrategy === 'merge'">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-purple-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.strategy_merge_explanation_title') }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('backup.strategy_merge_explanation') }}
                                </p>
                            </div>
                        </div>
                    </template>
                    <template x-if="globalStrategy === 'ask'">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-info-circle text-orange-500 mt-0.5"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ __('backup.strategy_ask_explanation_title') }}
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {{ __('backup.strategy_ask_explanation') }}
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Per-Record Conflict Resolution (when strategy is 'ask') -->
        <template x-if="globalStrategy === 'ask'">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                <i class="fas fa-list me-2 text-orange-500"></i>
                                {{ __('backup.individual_conflicts') }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ __('backup.review_each_conflict') }}
                            </p>
                        </div>
                        <!-- Quick Actions -->
                        <div class="flex items-center gap-2">
                            <button type="button" @click="setAllDecisions('skip')"
                                    class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded hover:bg-gray-200 dark:hover:bg-gray-600">
                                {{ __('backup.all_skip') }}
                            </button>
                            <button type="button" @click="setAllDecisions('use_backup')"
                                    class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded hover:bg-blue-200 dark:hover:bg-blue-800">
                                {{ __('backup.all_use_backup') }}
                            </button>
                            <button type="button" @click="setAllDecisions('keep_existing')"
                                    class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded hover:bg-green-200 dark:hover:bg-green-800">
                                {{ __('backup.all_keep_existing') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($conflicts['sample_conflicts'] ?? [] as $category => $samples)
                        @foreach($samples as $index => $conflict)
                        <div class="p-4" x-data="{ expanded: false }">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                            {{ __('backup.category_' . Str::snake($category)) }}
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                            ID: {{ $conflict['id'] }}
                                        </span>
                                    </div>

                                    <!-- Different Fields Summary -->
                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ count($conflict['different_fields'] ?? []) }} {{ __('backup.fields_differ') }}
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <select name="decisions[{{ $conflict['id'] }}][action]"
                                            x-model="decisions['{{ $conflict['id'] }}']"
                                            class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-lg focus:ring-blue-500 focus:border-blue-500">
                                        <option value="skip">{{ __('backup.skip_this') }}</option>
                                        <option value="use_backup">{{ __('backup.use_backup') }}</option>
                                        <option value="keep_existing">{{ __('backup.keep_existing') }}</option>
                                        <option value="merge">{{ __('backup.merge_fields') }}</option>
                                    </select>

                                    <button type="button" @click="expanded = !expanded"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                        <i :class="expanded ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Expanded Field Comparison -->
                            <div x-show="expanded" x-collapse class="mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Existing Data -->
                                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-3 border border-red-200 dark:border-red-800">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-database text-red-500"></i>
                                            <span class="text-sm font-medium text-red-800 dark:text-red-200">
                                                {{ __('backup.existing_value') }}
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($conflict['different_fields'] ?? [] as $field)
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">{{ $field['field'] }}:</span>
                                                <span class="text-gray-900 dark:text-white font-mono truncate max-w-[150px]" title="{{ $field['existing'] }}">
                                                    {{ Str::limit(is_array($field['existing']) ? json_encode($field['existing']) : $field['existing'], 30) }}
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <!-- Backup Data -->
                                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-3 border border-green-200 dark:border-green-800">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-file-archive text-green-500"></i>
                                            <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                                {{ __('backup.backup_value') }}
                                            </span>
                                        </div>
                                        <div class="space-y-2">
                                            @foreach($conflict['different_fields'] ?? [] as $field)
                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-600 dark:text-gray-400">{{ $field['field'] }}:</span>
                                                <span class="text-gray-900 dark:text-white font-mono truncate max-w-[150px]" title="{{ $field['backup'] }}">
                                                    {{ Str::limit(is_array($field['backup']) ? json_encode($field['backup']) : $field['backup'], 30) }}
                                                </span>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                </div>

                @if(($conflicts['total'] ?? 0) > count(array_merge(...array_values($conflicts['sample_conflicts'] ?? []))))
                <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ __('backup.showing_sample_conflicts', ['shown' => count(array_merge(...array_values($conflicts['sample_conflicts'] ?? []))), 'total' => $conflicts['total']]) }}
                    </p>
                </div>
                @endif
            </div>
        </template>

        <!-- Hidden input for decisions JSON -->
        <input type="hidden" name="decisions" :value="JSON.stringify(decisions)">

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <a href="{{ route('backup.restore.select', ['org' => $org, 'restore' => $restore->id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <i class="fas fa-arrow-start me-2"></i>
                {{ __('common.back') }}
            </a>

            <button type="submit"
                    :disabled="!globalStrategy"
                    :class="!globalStrategy ? 'opacity-50 cursor-not-allowed' : ''"
                    class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                {{ __('backup.continue_to_confirm') }}
                <i class="fas fa-arrow-end ms-2"></i>
            </button>
        </div>
    </form>
</div>

<script>
function conflictResolver() {
    return {
        globalStrategy: '{{ $restore->conflict_resolution['strategy'] ?? 'skip' }}',
        decisions: @json($restore->conflict_resolution['decisions'] ?? []),

        setGlobalStrategy(strategy) {
            this.globalStrategy = strategy;
        },

        setAllDecisions(action) {
            const selects = document.querySelectorAll('select[name^="decisions"]');
            selects.forEach(select => {
                const id = select.name.match(/\[(.*?)\]/)[1];
                this.decisions[id] = action;
            });
        },

        handleSubmit(event) {
            if (!this.globalStrategy) {
                event.preventDefault();
                alert('{{ __("backup.select_strategy") }}');
            }
        }
    };
}
</script>
@endsection
