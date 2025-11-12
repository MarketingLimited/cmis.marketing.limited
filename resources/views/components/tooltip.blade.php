@props(['text', 'position' => 'top'])

@php
    $positions = [
        'top' => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
        'left' => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right' => 'left-full top-1/2 -translate-y-1/2 ml-2',
    ];
@endphp

<div x-data="{ show: false }" class="relative inline-block">
    <div @mouseenter="show = true" @mouseleave="show = false">
        {{ $slot }}
    </div>

    <div x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ $positions[$position] }} z-50 px-3 py-2 text-sm font-medium text-white bg-gray-900 rounded-lg shadow-lg whitespace-nowrap"
         style="display: none;">
        {{ $text }}
        <!-- Arrow -->
        <div class="absolute {{ $position === 'top' ? 'top-full -mt-1 left-1/2 -translate-x-1/2' : '' }} {{ $position === 'bottom' ? 'bottom-full -mb-1 left-1/2 -translate-x-1/2 rotate-180' : '' }} {{ $position === 'left' ? 'left-full -ml-1 top-1/2 -translate-y-1/2 -rotate-90' : '' }} {{ $position === 'right' ? 'right-full -mr-1 top-1/2 -translate-y-1/2 rotate-90' : '' }} w-2 h-2 bg-gray-900 transform rotate-45"></div>
    </div>
</div>
