{{-- Publish Modal Platform Warnings Banner --}}
<div x-show="platformWarnings.length > 0" x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0 -translate-y-2"
     class="border-b border-orange-200">
    <div class="px-6 py-3 bg-gradient-to-l from-orange-50 via-yellow-50 to-orange-50">
        <div class="flex items-start gap-3">
            <div class="flex-shrink-0 mt-0.5">
                <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-exclamation-triangle text-orange-600"></i>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <h4 class="text-sm font-semibold text-orange-900 mb-2">
                    {{ __('publish.platform_warnings_title') }}
                </h4>
                <div class="space-y-1.5">
                    <template x-for="(warning, index) in platformWarnings" :key="index">
                        <div class="flex items-start gap-2">
                            <div class="flex-shrink-0 w-5 h-5 mt-0.5 rounded-full flex items-center justify-center"
                                 :class="{
                                     'bg-red-100': warning.type === 'error',
                                     'bg-yellow-100': warning.type === 'warning',
                                     'bg-blue-100': warning.type === 'customization'
                                 }">
                                <i :class="warning.platform ? getPlatformIcon(warning.platform) : 'fas fa-info'"
                                   class="text-xs"
                                   :class="{
                                       'text-red-600': warning.type === 'error',
                                       'text-yellow-700': warning.type === 'warning',
                                       'text-blue-600': warning.type === 'customization'
                                   }"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-orange-900" x-text="warning.message"></p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            <button @click="resetAllCustomizations()"
                    x-show="platformWarnings.some(w => w.type === 'customization')"
                    class="flex-shrink-0 px-3 py-1.5 text-sm bg-white text-orange-700 rounded-lg hover:bg-orange-100 border border-orange-300 transition flex items-center gap-2 shadow-sm">
                <i class="fas fa-undo"></i>
                {{ __('publish.reset_customizations') }}
            </button>
        </div>
    </div>
</div>
