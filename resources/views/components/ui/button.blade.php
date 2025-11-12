@props(['type' => 'button', 'variant' => 'primary', 'size' => 'md', 'icon' => null])

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold rounded-lg transition duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

    $variantClasses = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500',
        'secondary' => 'bg-gray-600 hover:bg-gray-700 text-white focus:ring-gray-500',
        'success' => 'bg-green-600 hover:bg-green-700 text-white focus:ring-green-500',
        'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500',
        'warning' => 'bg-yellow-600 hover:bg-yellow-700 text-white focus:ring-yellow-500',
        'info' => 'bg-cyan-600 hover:bg-cyan-700 text-white focus:ring-cyan-500',
        'outline' => 'border-2 border-gray-300 hover:border-gray-400 text-gray-700 bg-transparent',
    ];

    $sizeClasses = [
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-base',
        'lg' => 'px-6 py-3 text-lg',
    ];

    $classes = $baseClasses . ' ' . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i class="{{ $icon }} ml-2"></i>
    @endif
    {{ $slot }}
</button>
