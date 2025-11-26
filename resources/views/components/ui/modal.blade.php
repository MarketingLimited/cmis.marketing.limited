@props(['name', 'title', 'maxWidth' => 'md'])

@php
    $maxWidthClass = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-lg',
        'xl' => 'max-w-xl',
        '2xl' => 'max-w-2xl',
    ][$maxWidth];
@endphp

<div x-data="{ show: false }"
     @open-modal.window="if ($event.detail === '{{ $name }}') show = true"
     @close-modal.window="if ($event.detail === '{{ $name }}') show = false"
     @keydown.escape.window="show = false"
     x-show="show"
     x-trap.noscroll.inert="show"
     role="dialog"
     aria-modal="true"
     :aria-labelledby="show ? 'modal-title-{{ $name }}' : null"
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;">

    <!-- Backdrop -->
    <div x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black bg-opacity-50"
         @click="show = false"
         aria-hidden="true"></div>

    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen px-4 py-6 sm:px-6">
        <div x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 transform translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 transform translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full {{ $maxWidthClass }} my-8">

            <!-- Header -->
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 id="modal-title-{{ $name }}" class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h3>
                <button @click="show = false"
                        class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        aria-label="إغلاق النافذة">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-4 sm:px-6 py-4 max-h-[70vh] overflow-y-auto">
                {{ $slot }}
            </div>

            <!-- Footer (if provided) -->
            @isset($footer)
                <div class="flex items-center justify-end gap-2 px-4 sm:px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                    {{ $footer }}
                </div>
            @endisset

        </div>
    </div>
</div>

<script>
    function openModal(name) {
        window.dispatchEvent(new CustomEvent('open-modal', { detail: name }));
    }

    function closeModal(name) {
        window.dispatchEvent(new CustomEvent('close-modal', { detail: name }));
    }
</script>
