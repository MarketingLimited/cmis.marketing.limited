@props([
    'title',
    'value',
    'icon' => null,
    'color' => 'indigo',
    'trend' => null,
    'trendDirection' => null,
    'subtitle' => null,
    'link' => null
])

@php
$gradients = [
    'indigo' => 'from-indigo-500 to-indigo-600',
    'blue' => 'from-blue-500 to-blue-600',
    'green' => 'from-green-500 to-green-600',
    'yellow' => 'from-yellow-500 to-yellow-600',
    'red' => 'from-red-500 to-red-600',
    'purple' => 'from-purple-500 to-purple-600',
    'pink' => 'from-pink-500 to-pink-600',
    'orange' => 'from-orange-500 to-orange-600',
    'teal' => 'from-teal-500 to-teal-600',
];

$iconColors = [
    'indigo' => 'text-indigo-300',
    'blue' => 'text-blue-300',
    'green' => 'text-green-300',
    'yellow' => 'text-yellow-300',
    'red' => 'text-red-300',
    'purple' => 'text-purple-300',
    'pink' => 'text-pink-300',
    'orange' => 'text-orange-300',
    'teal' => 'text-teal-300',
];

$gradient = $gradients[$color] ?? $gradients['indigo'];
$iconColor = $iconColors[$color] ?? $iconColors['indigo'];
@endphp

<div {{ $attributes->merge(['class' => 'bg-gradient-to-br ' . $gradient . ' rounded-xl shadow-lg p-4 sm:p-6 text-white']) }}>
    <div class="flex items-center justify-between">
        <div class="flex-1 min-w-0">
            <p class="text-white/80 text-xs sm:text-sm mb-1">{{ $title }}</p>
            <p class="text-2xl sm:text-3xl font-bold truncate">{{ $value }}</p>

            @if ($subtitle)
            <p class="text-white/70 text-[10px] sm:text-xs mt-1 sm:mt-2 truncate">{{ $subtitle }}</p>
            @endif

            @if ($trend !== null)
            <div class="flex items-center gap-1 sm:gap-2 mt-2 sm:mt-3">
                @if ($trendDirection === 'up')
                <span class="flex items-center text-xs sm:text-sm font-medium text-white/90">
                    <i class="fas fa-arrow-up ml-1 text-xs"></i>
                    {{ $trend }}
                </span>
                @elseif ($trendDirection === 'down')
                <span class="flex items-center text-xs sm:text-sm font-medium text-white/90">
                    <i class="fas fa-arrow-down ml-1 text-xs"></i>
                    {{ $trend }}
                </span>
                @else
                <span class="flex items-center text-xs sm:text-sm font-medium text-white/90">
                    <i class="fas fa-minus ml-1 text-xs"></i>
                    {{ $trend }}
                </span>
                @endif
                <span class="text-[10px] sm:text-xs text-white/60 hidden sm:inline">مقارنة بالأمس</span>
            </div>
            @endif
        </div>

        @if ($icon)
        <div class="flex-shrink-0 mr-2 sm:mr-0">
            <i class="{{ $icon }} text-3xl sm:text-4xl md:text-5xl {{ $iconColor }} opacity-50"></i>
        </div>
        @endif
    </div>

    @if ($link)
    <a href="{{ $link }}"
       class="block mt-3 sm:mt-4 pt-3 sm:pt-4 border-t border-white/20 text-xs sm:text-sm text-white hover:text-white/80 transition">
        <span class="flex items-center justify-between">
            <span>عرض التفاصيل</span>
            <i class="fas fa-arrow-left text-xs"></i>
        </span>
    </a>
    @endif
</div>
