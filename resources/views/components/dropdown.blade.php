@props(['label' => '', 'icon' => '', 'align' => 'left'])

<div x-data="{ open: false }" class="relative">
    <!-- Trigger Button -->
    <button @click="open = !open" type="button"
            class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-sm">
        @if($icon)
            <i class="{{ $icon }} text-gray-600"></i>
        @endif
        <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
        <i class="fas fa-chevron-down text-xs text-gray-500 transition-transform" :class="open && 'rotate-180'"></i>
    </button>

    <!-- Dropdown Menu -->
    <div x-show="open"
         @click.away="open = false"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ $align === 'left' ? 'left-0' : 'right-0' }} mt-2 w-56 bg-white rounded-xl shadow-2xl border border-gray-100 z-50"
         style="display: none;">
        <div class="py-2">
            {{ $slot }}
        </div>
    </div>
</div>
