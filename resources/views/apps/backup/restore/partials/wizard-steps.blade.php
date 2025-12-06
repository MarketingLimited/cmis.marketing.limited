@php
$steps = [
    1 => ['icon' => 'fas fa-search', 'label' => __('backup.step_analyze')],
    2 => ['icon' => 'fas fa-list-check', 'label' => __('backup.step_select')],
    3 => ['icon' => 'fas fa-exchange-alt', 'label' => __('backup.step_conflicts')],
    4 => ['icon' => 'fas fa-check-double', 'label' => __('backup.step_confirm')],
    5 => ['icon' => 'fas fa-spinner', 'label' => __('backup.step_progress')],
];
@endphp

<div class="mb-8">
    <!-- Breadcrumb -->
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
                <span class="text-gray-900 dark:text-white">{{ __('backup.restore_wizard') }}</span>
            </li>
        </ol>
    </nav>

    <!-- Title -->
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
        {{ __('backup.restore_wizard') }}
    </h1>

    <!-- Steps -->
    <div class="flex items-center justify-between">
        @foreach($steps as $stepNum => $step)
        <div class="flex items-center {{ $stepNum < count($steps) ? 'flex-1' : '' }}">
            <!-- Step Circle -->
            <div class="flex flex-col items-center">
                <div @class([
                    'flex items-center justify-center w-10 h-10 rounded-full border-2 transition-colors',
                    'bg-blue-600 border-blue-600 text-white' => $currentStep === $stepNum,
                    'bg-green-500 border-green-500 text-white' => $currentStep > $stepNum,
                    'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-400' => $currentStep < $stepNum,
                ])>
                    @if($currentStep > $stepNum)
                    <i class="fas fa-check"></i>
                    @else
                    <i class="{{ $step['icon'] }}"></i>
                    @endif
                </div>
                <span @class([
                    'mt-2 text-xs font-medium text-center',
                    'text-blue-600 dark:text-blue-400' => $currentStep === $stepNum,
                    'text-green-600 dark:text-green-400' => $currentStep > $stepNum,
                    'text-gray-500 dark:text-gray-400' => $currentStep < $stepNum,
                ])>
                    {{ $step['label'] }}
                </span>
            </div>

            <!-- Connector Line -->
            @if($stepNum < count($steps))
            <div @class([
                'flex-1 h-0.5 mx-2',
                'bg-green-500' => $currentStep > $stepNum,
                'bg-gray-300 dark:bg-gray-600' => $currentStep <= $stepNum,
            ])></div>
            @endif
        </div>
        @endforeach
    </div>
</div>
