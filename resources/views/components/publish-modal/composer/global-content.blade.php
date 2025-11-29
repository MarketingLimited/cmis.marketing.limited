                    {{-- Content Area --}}
                    <div class="flex-1 overflow-y-auto p-6">
                        {{-- Global Content Tab --}}
                        <div x-show="composerTab === 'global'">
                            {{-- Text Editor --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.post_content') }}</label>
                                <div class="relative">
                                    <textarea x-model="content.global.text" rows="6"
                                              @input="updateCharacterCounts()"
                                              class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none"
                                              placeholder="{{ __('publish.what_to_share') }}"></textarea>
                                    {{-- Toolbar --}}
                                    <div class="absolute bottom-2 start-2 end-2 flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            {{-- RICH TEXT FORMATTING BUTTONS --}}
                                            <div class="flex items-center gap-0.5 me-2 border-e border-gray-300 pe-2">
                                                <button @click="formatText('bold')" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.bold') }}">
                                                    <i class="fas fa-bold"></i>
                                                </button>
                                                <button @click="formatText('italic')" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.italic') }}">
                                                    <i class="fas fa-italic"></i>
                                                </button>
                                                <button @click="formatText('underline')" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.underline') }}">
                                                    <i class="fas fa-underline"></i>
                                                </button>
                                                <button @click="formatText('strikethrough')" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.strikethrough') }}">
                                                    <i class="fas fa-strikethrough"></i>
                                                </button>
                                            </div>
                                            <div class="relative">
                                                <button @click="showEmojiPicker = !showEmojiPicker" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.emoji') }}">
                                                    <i class="far fa-smile"></i>
                                                </button>
                                                {{-- Emoji Picker Popup --}}
                                                <div x-show="showEmojiPicker" @click.away="showEmojiPicker = false"
                                                     x-transition:enter="transition ease-out duration-100"
                                                     x-transition:enter-start="transform opacity-0 scale-95"
                                                     x-transition:enter-end="transform opacity-100 scale-100"
                                                     class="absolute bottom-full start-0 mb-2 w-80 bg-white rounded-lg shadow-2xl border border-gray-200 p-3 z-50">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h4 class="text-sm font-semibold text-gray-700">{{ __('publish.select_emoji') }}</h4>
                                                        <button @click="showEmojiPicker = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
                                                    </div>
                                                    {{-- Emoji Categories --}}
                                                    <div class="grid grid-cols-8 gap-1 max-h-64 overflow-y-auto">
                                                        <template x-for="emoji in commonEmojis" :key="emoji">
                                                            <button @click="insertEmoji(emoji)" class="p-2 hover:bg-gray-100 rounded text-xl transition" x-text="emoji"></button>
                                                        </template>
                                                    </div>
                                                </div>
                                            </div>
                                            <button @click="showHashtagManager = true" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.hashtag_manager_help') }}">
                                                <i class="fas fa-hashtag"></i>
                                            </button>
                                            <button @click="showMentionPicker = true" class="p-1.5 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded" title="{{ __('publish.mention') }}">
                                                <i class="fas fa-at"></i>
                                            </button>
                                            <button @click="showAIAssistant = true" class="p-1.5 text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded" title="{{ __('publish.ai_assistant_help') }}">
                                                <i class="fas fa-magic"></i>
                                            </button>
                                        </div>
                                        {{-- Character Counts --}}
                                        <div class="flex items-center gap-3 text-xs">
                                            <template x-for="platform in getSelectedPlatforms()" :key="platform">
                                                <span :class="getCharacterCountClass(platform)">
                                                    <i :class="getPlatformIcon(platform)" class="ms-1"></i>
                                                    <span x-text="getCharacterCount(platform)"></span>
                                                </span>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- PHASE 4: Enhanced Media Upload with Multiple Sources --}}
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="flex items-center gap-2">
                                        <label class="block text-sm font-medium text-gray-700">{{ __('publish.media') }}</label>
                                        <button type="button" class="text-gray-400 hover:text-blue-600 transition" title="{{ __('publish.media_upload_help') }}">
                                            <i class="fas fa-info-circle text-xs"></i>
                                        </button>
                                    </div>
                                    <div class="flex items-center gap-1">
                                        <button @click="showMediaSourcePicker = true" class="px-2 py-1 text-xs bg-blue-50 text-blue-600 hover:bg-blue-100 rounded transition" title="{{ __('publish.media_sources') }}">
                                            <i class="fas fa-folder-open me-1"></i>{{ __('publish.media_sources') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition cursor-pointer"
                                     @click="$refs.mediaInput.click()"
                                     @dragover.prevent="isDragging = true"
                                     @dragleave.prevent="isDragging = false"
                                     @drop.prevent="handleMediaDrop($event)"
                                     :class="{ 'border-blue-400 bg-blue-50': isDragging }">
                                    <input type="file" x-ref="mediaInput" @change="handleMediaUpload($event)" multiple accept="image/*,video/*" class="hidden">
                                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                    <p class="text-sm text-gray-600">{{ __('publish.drag_files') }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ __('publish.media_formats') }}</p>
                                </div>

                                {{-- Media Preview --}}
                                <div x-show="content.global.media.length > 0 || uploadingMedia.length > 0" class="mt-4 grid grid-cols-4 gap-3">
                                    {{-- Uploaded Media --}}
                                    <template x-for="(media, index) in content.global.media" :key="index">
                                        <div class="relative aspect-square rounded-lg overflow-hidden bg-gray-100 group">
                                            <template x-if="media.type === 'image'">
                                                <img :src="media.preview_url" class="w-full h-full object-cover">
                                            </template>
                                            <template x-if="media.type === 'video'">
                                                <div class="w-full h-full flex items-center justify-center bg-gray-800">
                                                    <i class="fas fa-play-circle text-white text-3xl"></i>
                                                </div>
                                            </template>
                                            {{-- Processing Indicator --}}
                                            <template x-if="mediaProcessingStatus[media.id] === 'processing'">
                                                <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center">
                                                    <div class="animate-spin rounded-full h-8 w-8 border-2 border-white border-t-transparent mb-2"></div>
                                                    <p class="text-white text-xs">{{ __('publish.processing') }}</p>
                                                </div>
                                            </template>
                                            <button @click="removeMedia(index)"
                                                    class="absolute top-2 end-2 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </div>
                                    </template>

                                    {{-- Uploading Media (with progress) --}}
                                    <template x-for="upload in uploadingMedia" :key="upload.id">
                                        <div class="relative aspect-square rounded-lg overflow-hidden bg-gray-100">
                                            <div class="w-full h-full flex flex-col items-center justify-center p-4">
                                                <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-500 border-t-transparent mb-2"></div>
                                                <p class="text-xs text-gray-600 text-center mb-2" x-text="upload.name"></p>
                                                {{-- Progress Bar --}}
                                                <div class="w-full bg-gray-200 rounded-full h-1.5">
                                                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300"
                                                         :style="'width: ' + (upload.progress || 0) + '%'"></div>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1" x-text="(upload.progress || 0) + '%'"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                {{-- PHASE 5A: PROCESSING STATUS TOGGLES (VISTASOCIAL PARITY) --}}
                                <div x-show="content.global.media.length > 0 || uploadingMedia.length > 0" class="mt-4 space-y-2">
                                    {{-- Image Processing Toggle --}}
                                    <template x-if="content.global.media.some(m => m.type === 'image')">
                                        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex items-center gap-2">
                                                <div class="relative inline-block w-10 align-middle select-none">
                                                    <input type="checkbox" x-model="imageProcessingEnabled" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all duration-300 ease-in-out"
                                                           :class="imageProcessingEnabled ? 'right-0 border-blue-500' : 'left-0 border-gray-300'"
                                                           style="top: 1px;">
                                                    <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"
                                                           :class="imageProcessingEnabled ? 'bg-blue-500' : 'bg-gray-300'"></label>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700">{{ __('publish.image_processing') }}</span>
                                            </div>
                                            <a href="#" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                                {{ __('publish.learn_more') }} →
                                            </a>
                                        </div>
                                    </template>

                                    {{-- Video Processing Toggle --}}
                                    <template x-if="content.global.media.some(m => m.type === 'video')">
                                        <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex items-center gap-2">
                                                <div class="relative inline-block w-10 align-middle select-none">
                                                    <input type="checkbox" x-model="videoProcessingEnabled" class="toggle-checkbox absolute block w-5 h-5 rounded-full bg-white border-2 appearance-none cursor-pointer transition-all duration-300 ease-in-out"
                                                           :class="videoProcessingEnabled ? 'right-0 border-blue-500' : 'left-0 border-gray-300'"
                                                           style="top: 1px;">
                                                    <label class="toggle-label block overflow-hidden h-6 rounded-full bg-gray-300 cursor-pointer"
                                                           :class="videoProcessingEnabled ? 'bg-blue-500' : 'bg-gray-300'"></label>
                                                </div>
                                                <span class="text-sm font-medium text-gray-700">{{ __('publish.video_processing') }}</span>
                                            </div>
                                            <a href="#" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                                                {{ __('publish.learn_more') }} →
                                            </a>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Link Input --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.link') }}</label>
                                <div class="flex gap-2">
                                    <input type="url" x-model="content.global.link" placeholder="https://..."
                                           class="flex-1 rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <button @click="shortenLink()" class="px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                                            :disabled="!content.global.link" :class="{ 'opacity-50 cursor-not-allowed': !content.global.link }">
                                        <i class="fas fa-compress-alt ms-1"></i>{{ __('publish.shorten') }}
                                    </button>
                                </div>
                            </div>

                            {{-- Labels/Tags --}}
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.labels') }}</label>
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="label in content.global.labels" :key="label">
                                        <span class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs">
                                            <span x-text="label"></span>
                                            <button @click="removeLabel(label)" class="hover:text-blue-900"><i class="fas fa-times"></i></button>
                                        </span>
                                    </template>
                                    <input type="text" x-model="newLabel" @keydown.enter.prevent="addLabel()"
                                           placeholder="{{ __('publish.add_label') }}"
                                           class="px-2 py-1 text-xs border-0 bg-transparent focus:ring-0 w-28">
                                </div>
                            </div>
                        </div>

