@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
    'disabled' => false,
    'fullWidth' => false
])

@php
$variantClasses = [
    'primary' => 'bg-gradient-to-r from-indigo-600 to-purple-600 text-white hover:shadow-lg',
    'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
    'success' => 'bg-green-600 text-white hover:bg-green-700',
    'danger' => 'bg-red-600 text-white hover:bg-red-700',
    'warning' => 'bg-yellow-500 text-white hover:bg-yellow-600',
    'info' => 'bg-blue-600 text-white hover:bg-blue-700',
    'outline' => 'border-2 border-indigo-600 text-indigo-600 hover:bg-indigo-50',
    'ghost' => 'text-indigo-600 hover:bg-indigo-50',
    'link' => 'text-indigo-600 hover:text-indigo-700 underline',
];

$sizeClasses = [
    'xs' => 'px-3 py-1 text-xs',
    'sm' => 'px-4 py-2 text-sm',
    'md' => 'px-6 py-3 text-base',
    'lg' => 'px-8 py-4 text-lg',
    'xl' => 'px-10 py-5 text-xl',
];

$variantClass = $variantClasses[$variant] ?? $variantClasses['primary'];
$sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];

$classes = $variantClass . ' ' . $sizeClass . ' rounded-lg font-medium transition duration-200 inline-flex items-center justify-center';

if ($fullWidth) {
    $classes .= ' w-full';
}

if ($disabled || $loading) {
    $classes .= ' opacity-50 cursor-not-allowed';
}
@endphp

<button type="{{ $type }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($disabled || $loading) disabled @endif>

    @if ($loading)
        <svg class="animate-spin h-5 w-5 {{ $iconPosition === 'left' ? 'ml-2' : 'mr-2' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    @elseif ($icon && $iconPosition === 'left')
        <i class="{{ $icon }} ml-2"></i>
    @endif

    <span>{{ $slot }}</span>

    @if ($icon && $iconPosition === 'right' && !$loading)
        <i class="{{ $icon }} mr-2"></i>
    @endif
</button>
