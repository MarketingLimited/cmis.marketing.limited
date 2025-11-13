@props([
    'value' => 0,
    'max' => 100,
    'color' => 'indigo',
    'size' => 'md',
    'label' => null,
    'showPercentage' => true,
    'animated' => false
])

@php
$percentage = $max > 0 ? ($value / $max) * 100 : 0;
$percentage = min(100, max(0, $percentage));

$colorClasses = [
    'indigo' => 'bg-indigo-600',
    'blue' => 'bg-blue-600',
    'green' => 'bg-green-600',
    'yellow' => 'bg-yellow-500',
    'red' => 'bg-red-600',
    'purple' => 'bg-purple-600',
    'pink' => 'bg-pink-600',
    'gray' => 'bg-gray-600',
];

$sizeClasses = [
    'sm' => 'h-1',
    'md' => 'h-2',
    'lg' => 'h-3',
    'xl' => 'h-4',
];

$bgColorClass = $colorClasses[$color] ?? $colorClasses['indigo'];
$heightClass = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if ($label || $showPercentage)
    <div class="flex items-center justify-between text-sm mb-2">
        @if ($label)
        <span class="font-medium text-gray-700">{{ $label }}</span>
        @endif
        @if ($showPercentage)
        <span class="font-bold text-gray-900">{{ number_format($percentage, 1) }}%</span>
        @endif
    </div>
    @endif

    <div class="w-full bg-gray-200 rounded-full {{ $heightClass }} overflow-hidden">
        <div class="{{ $bgColorClass }} {{ $heightClass }} rounded-full transition-all duration-500 ease-out {{ $animated ? 'animate-pulse' : '' }}"
             style="width: {{ $percentage }}%"
             role="progressbar"
             aria-valuenow="{{ $value }}"
             aria-valuemin="0"
             aria-valuemax="{{ $max }}">
        </div>
    </div>

    @if ($value && $max)
    <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
        <span>{{ $value }} من {{ $max }}</span>
    </div>
    @endif
</div>
