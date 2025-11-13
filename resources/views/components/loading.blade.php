@props(['size' => 'md', 'color' => 'indigo'])

@php
$sizeClasses = [
    'sm' => 'h-4 w-4',
    'md' => 'h-8 w-8',
    'lg' => 'h-12 w-12',
    'xl' => 'h-16 w-16',
];

$colorClasses = [
    'indigo' => 'border-indigo-600',
    'blue' => 'border-blue-600',
    'green' => 'border-green-600',
    'red' => 'border-red-600',
    'yellow' => 'border-yellow-600',
];

$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
$colorClass = $colorClasses[$color] ?? $colorClasses['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center']) }}>
    <div class="animate-spin rounded-full border-b-2 {{ $sizeClass }} {{ $colorClass }}"></div>
</div>
