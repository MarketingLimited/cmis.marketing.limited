@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'padding' => 'p-6',
    'hoverable' => false,
    'gradient' => false,
    'footer' => null
])

@php
$classes = 'bg-white rounded-xl shadow-sm overflow-hidden ' . $padding;
if ($hoverable) {
    $classes .= ' hover:shadow-lg transition-shadow duration-300 cursor-pointer';
}
if ($gradient) {
    $classes = 'bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-lg text-white overflow-hidden ' . $padding;
}
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    @if ($title || $subtitle)
    <div class="mb-4 {{ $gradient ? '' : 'border-b pb-4' }}">
        @if ($icon || $title)
        <div class="flex items-center gap-3">
            @if ($icon)
            <div class="{{ $gradient ? 'bg-white/20 backdrop-blur-sm' : 'bg-indigo-100' }} rounded-xl p-3">
                <i class="{{ $icon }} {{ $gradient ? 'text-white' : 'text-indigo-600' }} text-xl"></i>
            </div>
            @endif
            <div>
                @if ($title)
                <h3 class="text-lg font-bold {{ $gradient ? 'text-white' : 'text-gray-900' }}">{{ $title }}</h3>
                @endif
                @if ($subtitle)
                <p class="text-sm {{ $gradient ? 'text-white/80' : 'text-gray-600' }} mt-1">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
    @endif

    <div>
        {{ $slot }}
    </div>

    @if ($footer || isset($actions))
    <div class="mt-4 pt-4 border-t {{ $gradient ? 'border-white/20' : 'border-gray-200' }} flex justify-end gap-2">
        {{ $footer ?? $actions ?? '' }}
    </div>
    @endif
</div>
