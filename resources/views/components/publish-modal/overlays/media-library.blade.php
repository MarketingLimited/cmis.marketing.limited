    <div x-show="showMediaLibrary"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-[200]"
         
         @click.self="showMediaLibrary = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-hidden flex flex-col"
             @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.media_library') }}</h3>
                <button @click="showMediaLibrary = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Media Grid --}}
            <div class="flex-1 overflow-y-auto p-6">
                <div class="grid grid-cols-4 gap-4">
                    <template x-for="media in mediaLibraryFiles" :key="media.id">
                        <div class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 group cursor-pointer hover:ring-2 hover:ring-blue-500 transition"
                             @click="selectLibraryMedia(media)">
                            <img :src="media.thumbnail_url || media.url" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                <i class="fas fa-check-circle text-white text-2xl"></i>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Empty State --}}
                <template x-if="mediaLibraryFiles.length === 0">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="fas fa-images text-gray-300 text-5xl mb-3"></i>
                        <p class="text-gray-500">{{ __('publish.no_media_library') }}</p>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- PHASE 4: Platform Warnings Banner --}}
    <div x-show="platformWarnings.length > 0" class="fixed top-20 inset-x-0 z-40 flex justify-center px-4" >
        <div class="max-w-4xl w-full">
            <template x-for="(warning, index) in platformWarnings" :key="index">
                <div x-show="!warning.dismissed"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-4"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="bg-gradient-to-r from-orange-50 to-yellow-50 border-l-4 border-orange-500 rounded-lg shadow-lg p-4 mb-3">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-orange-600 text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-orange-900" x-text="warning.title || '{{ __('publish.warning') }}'"></p>
                            <p class="text-sm text-orange-700 mt-1" x-text="warning.message"></p>
                        </div>
                        <button @click="warning.dismissed = true" class="flex-shrink-0 text-orange-400 hover:text-orange-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
