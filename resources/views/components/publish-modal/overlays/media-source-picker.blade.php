    <div x-show="showMediaSourcePicker"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50"
         style="display: none;"
         @click.self="showMediaSourcePicker = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-xl mx-4"
             @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.media_sources') }}</h3>
                <button @click="showMediaSourcePicker = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Media Sources Grid --}}
            <div class="p-6 grid grid-cols-2 gap-4">
                {{-- Upload from URL --}}
                <button @click="showMediaSourcePicker = false; $refs.urlInput.focus()"
                        class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 rounded-lg hover:border-blue-400 hover:bg-blue-50 transition">
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-link text-blue-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('publish.upload_from_url') }}</span>
                </button>

                {{-- Media Library --}}
                <button @click="showMediaSourcePicker = false; showMediaLibrary = true; loadMediaLibrary()"
                        class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 rounded-lg hover:border-purple-400 hover:bg-purple-50 transition">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-images text-purple-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('publish.media_library') }}</span>
                </button>

                {{-- Google Drive --}}
                <button @click="connectGoogleDrive()"
                        class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 rounded-lg hover:border-green-400 hover:bg-green-50 transition">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fab fa-google-drive text-green-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('publish.google_drive') }}</span>
                </button>

                {{-- Dropbox --}}
                <button @click="connectDropbox()"
                        class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 rounded-lg hover:border-indigo-400 hover:bg-indigo-50 transition">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                        <i class="fab fa-dropbox text-indigo-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('publish.dropbox') }}</span>
                </button>

                {{-- OneDrive --}}
                <button @click="connectOneDrive()"
                        class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 rounded-lg hover:border-sky-400 hover:bg-sky-50 transition">
                    <div class="w-12 h-12 bg-sky-100 rounded-full flex items-center justify-center">
                        <i class="fab fa-microsoft text-sky-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('publish.onedrive') }}</span>
                </button>

                {{-- Computer Upload (Direct) --}}
                <button @click="showMediaSourcePicker = false; $refs.mediaInput.click()"
                        class="flex flex-col items-center gap-3 p-6 border-2 border-gray-200 rounded-lg hover:border-gray-400 hover:bg-gray-50 transition">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-laptop text-gray-600 text-xl"></i>
                    </div>
                    <span class="text-sm font-medium text-gray-900">{{ __('publish.computer') }}</span>
                </button>
            </div>

            {{-- URL Input Section --}}
            <div class="px-6 pb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.enter_media_url') }}</label>
                <div class="flex gap-2">
                    <input type="url"
                           x-ref="urlInput"
                           x-model="mediaUrlInput"
                           placeholder="https://example.com/image.jpg"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button @click="uploadFromUrl()"
                            :disabled="!mediaUrlInput"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        {{ __('publish.upload') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- PHASE 4: Media Library Modal --}}
