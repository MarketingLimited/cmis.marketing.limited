{{-- Text Editor --}}
<div class="mb-4">
    <div class="flex items-center justify-between mb-2">
        <label class="block text-sm font-medium text-gray-700">{{ __('publish.post_content') }}</label>
        {{-- Character Counts (Desktop - moved to header) --}}
        <div class="hidden md:flex items-center gap-3 text-xs">
            <template x-for="platform in getSelectedPlatforms()" :key="platform">
                <span :class="getCharacterCountClass(platform)">
                    <i :class="getPlatformIcon(platform)" class="ms-1"></i>
                    <span x-text="getCharacterCount(platform)"></span>
                </span>
            </template>
        </div>
    </div>

    {{-- Toolbar - Above Textarea (Mobile: 2 rows, Desktop: 1 row) --}}
    <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200 relative">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            {{-- Row 1: Formatting Tools --}}
            <div class="flex items-center gap-1 overflow-x-auto overflow-y-visible">
                {{-- RICH TEXT FORMATTING BUTTONS --}}
                <div class="flex items-center gap-0.5 me-2 border-e border-gray-300 pe-2">
                    <button @click="formatText('bold')" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.bold') }}">
                        <i class="fas fa-bold"></i>
                    </button>
                    <button @click="formatText('italic')" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.italic') }}">
                        <i class="fas fa-italic"></i>
                    </button>
                    <button @click="formatText('underline')" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.underline') }}">
                        <i class="fas fa-underline"></i>
                    </button>
                    <button @click="formatText('strikethrough')" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.strikethrough') }}">
                        <i class="fas fa-strikethrough"></i>
                    </button>
                </div>
                {{-- Content Tools --}}
                <div class="static">
                    <button @click="showEmojiPicker = !showEmojiPicker" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.emoji') }}" x-ref="emojiButton">
                        <i class="far fa-smile"></i>
                    </button>
                </div>
                <button @click="showHashtagManager = true" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.hashtag_manager_help') }}">
                    <i class="fas fa-hashtag"></i>
                </button>
                <button @click="showMentionPicker = true" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded transition" title="{{ __('publish.mention') }}">
                    <i class="fas fa-at"></i>
                </button>
                <button @click="showAIAssistant = true" class="p-2.5 min-w-[44px] min-h-[44px] flex items-center justify-center text-blue-500 hover:text-blue-700 hover:bg-blue-50 rounded transition" title="{{ __('publish.ai_assistant_help') }}">
                    <i class="fas fa-magic"></i>
                </button>
            </div>

            {{-- Character Counts (Mobile - Bottom row) --}}
            <div class="flex md:hidden items-center gap-3 text-xs border-t border-gray-200 pt-3">
                <span class="text-gray-500 font-medium">{{ __('publish.character_limit') }}:</span>
                <template x-for="platform in getSelectedPlatforms()" :key="platform">
                    <span :class="getCharacterCountClass(platform)">
                        <i :class="getPlatformIcon(platform)" class="ms-1"></i>
                        <span x-text="getCharacterCount(platform)"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Emoji Picker Popup - Positioned outside overflow container --}}
        <div x-show="showEmojiPicker" @click.away="showEmojiPicker = false"
             x-transition:enter="transition ease-out duration-100"
             x-transition:enter-start="transform opacity-0 scale-95"
             x-transition:enter-end="transform opacity-100 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="transform opacity-100 scale-100"
             x-transition:leave-end="transform opacity-0 scale-95"
             class="absolute top-16 start-20 w-80 bg-white rounded-lg shadow-2xl border border-gray-200 p-3 z-[100]"
             style="max-height: 400px;">
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('publish.select_emoji') }}</h4>
                <button @click="showEmojiPicker = false" class="text-gray-400 hover:text-gray-600"><i class="fas fa-times"></i></button>
            </div>
            {{-- Emoji Categories --}}
            <div class="grid grid-cols-6 gap-1 max-h-64 overflow-y-auto">
                <template x-for="emoji in commonEmojis" :key="emoji">
                    <button @click="insertEmoji(emoji)" class="p-2 min-w-[44px] min-h-[44px] flex items-center justify-center hover:bg-gray-100 rounded text-xl transition" x-text="emoji"></button>
                </template>
            </div>
        </div>
    </div>

    {{-- PHASE 2: Advanced Collaboration - Real-time Editing Status --}}
    <template x-if="true">
        <div x-show="activeCollaborators.length > 0"
             x-cloak
             x-transition
             class="mb-3 p-2.5 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="flex -space-x-2">
                    <template x-for="collaborator in activeCollaborators.slice(0, 3)" :key="collaborator.id">
                        <div class="relative">
                            <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center text-white text-xs font-semibold ring-2 ring-white"
                                 :title="collaborator.name">
                                <span x-text="collaborator.initials"></span>
                            </div>
                            <div class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full border-2 border-white"
                                 :class="collaborator.status === 'editing' ? 'bg-green-500' : 'bg-blue-500'"></div>
                        </div>
                    </template>
                    <div x-show="activeCollaborators.length > 3"
                         class="w-7 h-7 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 text-xs font-semibold ring-2 ring-white">
                        <span x-text="'+' + (activeCollaborators.length - 3)"></span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <p class="text-xs font-medium text-indigo-900" x-text="getCollaboratorSummary()"></p>
                    <p class="text-xs text-indigo-600" x-text="getLastActivity()"></p>
                </div>
            </div>
            <button @click="showCollaborators = !showCollaborators"
                    class="text-xs text-indigo-700 hover:text-indigo-800 transition">
                <i :class="showCollaborators ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
            </button>
        </div>

        {{-- Expanded Collaborator List --}}
        <div x-show="showCollaborators" x-transition class="mt-2 pt-2 border-t border-indigo-200 space-y-1.5">
            <template x-for="collaborator in activeCollaborators" :key="collaborator.id">
                <div class="flex items-center justify-between p-1.5 bg-white rounded">
                    <div class="flex items-center gap-2">
                        <div class="w-6 h-6 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 flex items-center justify-center text-white text-xs font-semibold">
                            <span x-text="collaborator.initials"></span>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-gray-900" x-text="collaborator.name"></p>
                            <p class="text-xs text-gray-500" x-text="collaborator.role"></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 text-xs rounded-full"
                              :class="collaborator.status === 'editing' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'"
                              x-text="collaborator.status === 'editing' ? '{{ __('publish.editing') }}' : '{{ __('publish.viewing') }}'"></span>
                        <span class="text-xs text-gray-400" x-text="formatTime(collaborator.last_activity)"></span>
                    </div>
                </div>
            </template>
        </div>
        </div>
    </template>

    {{-- Textarea (No embedded toolbar) --}}
    <textarea x-model="content.global.text" rows="6"
              @input="updateCharacterCounts()"
              class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none"
              placeholder="{{ __('publish.what_to_share') }}"></textarea>

    {{-- PHASE 2: Enhanced AI - Content Variations --}}
    <template x-if="true">
        <div x-show="content.global.text.length > 20 && !aiGeneratingVariations"
             x-cloak
             x-transition
             class="mt-3 p-2.5 bg-gradient-to-r from-violet-50 to-fuchsia-50 border border-violet-200 rounded-lg">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2">
                <i class="fas fa-magic text-violet-600"></i>
                <h4 class="text-xs font-semibold text-violet-900">{{ __('publish.ai_assistant') }}</h4>
            </div>
            <button @click="showAiVariations = !showAiVariations"
                    class="text-xs text-violet-700 hover:text-violet-800 transition">
                <i :class="showAiVariations ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
            </button>
        </div>

        <div x-show="showAiVariations" x-transition class="space-y-2">
            <div class="flex gap-2">
                <button @click="generateContentVariations()"
                        :disabled="aiGeneratingVariations"
                        class="flex-1 px-3 py-2 text-xs font-medium bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition">
                    <i class="fas fa-sparkles me-1"></i>{{ __('publish.generate_variations') }}
                </button>
                <button @click="improveContent()"
                        :disabled="aiGeneratingVariations"
                        class="flex-1 px-3 py-2 text-xs font-medium bg-fuchsia-600 hover:bg-fuchsia-700 text-white rounded-lg transition">
                    <i class="fas fa-wand-magic-sparkles me-1"></i>{{ __('publish.improve_content') }}
                </button>
            </div>

            {{-- Generated Variations --}}
            <div x-show="contentVariations.length > 0" class="mt-3 space-y-2">
                <p class="text-xs text-gray-600 font-medium">{{ __('publish.ai_variations') }}</p>
                <template x-for="(variation, index) in contentVariations" :key="index">
                    <div class="p-2.5 bg-white border border-violet-100 rounded-lg hover:border-violet-300 transition group">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-800 leading-relaxed mb-2" x-text="variation.text"></p>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 text-xs rounded-full bg-violet-100 text-violet-700" x-text="variation.style"></span>
                                    <span class="text-xs text-gray-500" x-text="'Quality: ' + variation.quality + '/100'"></span>
                                </div>
                            </div>
                            <button @click="useVariation(variation)"
                                    class="flex-shrink-0 p-1.5 text-violet-600 hover:bg-violet-50 rounded transition opacity-0 group-hover:opacity-100"
                                    title="{{ __('publish.use_this_variation') }}">
                                <i class="fas fa-check text-sm"></i>
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- A/B Testing Setup --}}
            <div x-show="contentVariations.length > 1" x-transition class="mt-3 pt-3 border-t border-violet-200">
                <div class="flex items-center justify-between mb-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" x-model="enableABTesting" class="w-4 h-4 text-violet-600 border-gray-300 rounded focus:ring-violet-500">
                        <span class="text-xs font-medium text-gray-900">{{ __('publish.enable_ab_testing') }}</span>
                    </label>
                    <button type="button" class="text-gray-400 hover:text-violet-600 transition" title="{{ __('publish.ab_testing_help') }}">
                        <i class="fas fa-info-circle text-xs"></i>
                    </button>
                </div>
                <div x-show="enableABTesting" x-transition class="space-y-2">
                    <p class="text-xs text-gray-600">{{ __('publish.ab_test_description') }}</p>
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600 mb-1">{{ __('publish.test_duration') }}</label>
                            <select x-model="abTestDuration" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-lg">
                                <option value="24">24 {{ __('publish.hours') }}</option>
                                <option value="48">48 {{ __('publish.hours') }}</option>
                                <option value="72">72 {{ __('publish.hours') }}</option>
                                <option value="168">7 {{ __('publish.days') }}</option>
                            </select>
                        </div>
                        <div class="flex-1">
                            <label class="block text-xs text-gray-600 mb-1">{{ __('publish.winning_metric') }}</label>
                            <select x-model="abTestMetric" class="w-full px-2 py-1.5 text-xs border border-gray-300 rounded-lg">
                                <option value="engagement">{{ __('publish.engagement_rate') }}</option>
                                <option value="clicks">{{ __('publish.click_rate') }}</option>
                                <option value="reach">{{ __('publish.reach') }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </template>
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
    <div x-show="content.global.media.length > 0" class="mt-4 grid grid-cols-4 gap-3">
        {{-- Media Items with Upload Status --}}
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

                {{-- Upload Status Overlays --}}
                {{-- Uploading Indicator --}}
                <template x-if="media.uploadStatus === 'uploading'">
                    <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-2 border-white border-t-transparent mb-2"></div>
                        <p class="text-white text-xs">{{ __('publish.uploading') }}</p>
                    </div>
                </template>

                {{-- Upload Success Indicator --}}
                <template x-if="media.uploadStatus === 'uploaded'">
                    <div class="absolute bottom-2 start-2 bg-green-500 text-white rounded-full w-6 h-6 flex items-center justify-center shadow-lg">
                        <i class="fas fa-check text-xs"></i>
                    </div>
                </template>

                {{-- Upload Failed Indicator --}}
                <template x-if="media.uploadStatus === 'failed'">
                    <div class="absolute inset-0 bg-red-500/60 flex flex-col items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-white text-2xl mb-2"></i>
                        <p class="text-white text-xs">{{ __('publish.upload_failed') }}</p>
                        <button @click="autoUploadMedia(index)" class="mt-2 px-2 py-1 bg-white text-red-600 text-xs rounded hover:bg-gray-100">
                            {{ __('publish.retry') }}
                        </button>
                    </div>
                </template>

                {{-- Processing Indicator (for post-upload processing) --}}
                <template x-if="mediaProcessingStatus[media.id] === 'processing'">
                    <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-2 border-white border-t-transparent mb-2"></div>
                        <p class="text-white text-xs">{{ __('publish.processing') }}</p>
                    </div>
                </template>

                {{-- Remove Button --}}
                <button @click="removeMedia(index)"
                        class="absolute top-2 end-2 w-6 h-6 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                    <i class="fas fa-times text-xs"></i>
                </button>
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
