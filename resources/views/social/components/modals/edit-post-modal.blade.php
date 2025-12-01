    <!-- Edit Post Modal -->
    <div x-show="showEditPostModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
         @click.self="showEditPostModal = false">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                    <i class="fas fa-edit text-indigo-600 ms-2"></i>
                    {{ __("social.edit_post") }}
                </h3>
                <button @click="showEditPostModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-6">
                <!-- Platform Info -->
                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                         :class="{
                             'bg-blue-100 text-blue-600': editingPost.platform === 'facebook',
                             'bg-gradient-to-br from-purple-100 to-pink-100 text-pink-600': editingPost.platform === 'instagram',
                             'bg-sky-100 text-sky-600': editingPost.platform === 'twitter',
                             'bg-blue-100 text-blue-700': editingPost.platform === 'linkedin'
                         }">
                        <i :class="{
                            'fab fa-facebook-f': editingPost.platform === 'facebook',
                            'fab fa-instagram': editingPost.platform === 'instagram',
                            'fab fa-twitter': editingPost.platform === 'twitter',
                            'fab fa-linkedin-in': editingPost.platform === 'linkedin'
                        }" class="text-lg"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white" x-text="editingPost.social_account_username || editingPost.social_account_display_name || editingPost.account_username || editingPost.platform"></p>
                        <p class="text-xs text-gray-500">
                            <span x-text="editingPost.platform"></span>
                            •
                            <span :class="{
                                'text-yellow-600': editingPost.status === 'scheduled',
                                'text-green-600': editingPost.status === 'published',
                                'text-gray-600': editingPost.status === 'draft',
                                'text-red-600': editingPost.status === 'failed'
                            }" x-text="getStatusLabel(editingPost.status)"></span>
                        </p>
                    </div>
                </div>

                <!-- Content -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        <i class="fas fa-pen ms-1"></i>
                        {{ __('social.post_content') }}
                    </label>

                    <!-- Editing Toolbar -->
                    <div class="flex items-center gap-1 p-2 bg-gray-50 dark:bg-gray-700 rounded-t-lg border border-b-0 border-gray-300 dark:border-gray-600">
                        <!-- Emoji Picker -->
                        <div class="relative">
                            <button @click="showEditEmojiPicker = !showEditEmojiPicker"
                                    type="button"
                                    class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
                                    :class="{ 'bg-gray-200 dark:bg-gray-600': showEditEmojiPicker }"
                                    title="{{ __('social.insert_emoji') }}">
                                <i class="fas fa-smile text-gray-500 dark:text-gray-400"></i>
                            </button>
                            <!-- Emoji Dropdown -->
                            <div x-show="showEditEmojiPicker"
                                 x-cloak
                                 @click.away="showEditEmojiPicker = false"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute top-full start-0 mt-1 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 w-72 max-h-56 overflow-y-auto">
                                <div class="grid grid-cols-8 gap-1">
                                    <template x-for="emoji in editCommonEmojis" :key="emoji">
                                        <button @click="insertEditEmoji(emoji)"
                                                type="button"
                                                class="p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 rounded text-xl transition"
                                                x-text="emoji"></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- Hashtag Button -->
                        <button @click="insertEditText('#')"
                                type="button"
                                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
                                title="{{ __('social.insert_hashtag') }}">
                            <i class="fas fa-hashtag text-gray-500 dark:text-gray-400"></i>
                        </button>

                        <!-- Mention Button -->
                        <button @click="insertEditText('@')"
                                type="button"
                                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition"
                                title="{{ __('social.insert_mention') }}">
                            <i class="fas fa-at text-gray-500 dark:text-gray-400"></i>
                        </button>

                        <!-- Divider -->
                        <div class="w-px h-6 bg-gray-300 dark:bg-gray-600 mx-1"></div>

                        <!-- Bold Button -->
                        <button @click="formatEditText('bold')"
                                type="button"
                                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition font-bold text-gray-600 dark:text-gray-400"
                                title="{{ __('social.format_bold') }}">
                            B
                        </button>

                        <!-- Italic Button -->
                        <button @click="formatEditText('italic')"
                                type="button"
                                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition italic text-gray-600 dark:text-gray-400"
                                title="{{ __('social.format_italic') }}">
                            I
                        </button>

                        <!-- Spacer -->
                        <div class="flex-1"></div>

                        <!-- Clear Formatting -->
                        <button @click="editingPost.content = editingPost.content.replace(/[*_~]/g, '')"
                                type="button"
                                class="p-2 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition text-gray-500 dark:text-gray-400 text-xs"
                                title="{{ __('social.clear_formatting') }}">
                            <i class="fas fa-remove-format"></i>
                        </button>
                    </div>

                    <textarea x-model="editingPost.content" rows="5"
                              x-ref="editContentTextarea"
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-b-lg p-4 resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent rounded-t-none"
                              :class="{
                                  'border-red-500 focus:ring-red-500': getEditCharacterStatus() === 'exceeded',
                                  'border-orange-400 focus:ring-orange-400': getEditCharacterStatus() === 'warning'
                              }"
                              placeholder="{{ __("social.post_content_placeholder") }}"></textarea>

                    <!-- Enhanced Character Counter with Progress Bar -->
                    <div class="mt-2">
                        <div class="flex justify-between items-center text-xs mb-1">
                            <div class="flex items-center gap-2">
                                <span class="text-gray-500 dark:text-gray-400">
                                    <span x-text="editingPost.content?.length || 0"></span>
                                    /
                                    <span x-text="getEditCharacterLimit()"></span>
                                    {{ __('social.characters') }}
                                </span>
                                <span class="text-gray-400 dark:text-gray-500">•</span>
                                <span class="capitalize text-gray-500 dark:text-gray-400" x-text="editingPost.platform"></span>
                            </div>
                            <div>
                                <template x-if="getEditCharacterStatus() === 'exceeded'">
                                    <span class="text-red-600 dark:text-red-400 font-medium flex items-center gap-1">
                                        <i class="fas fa-exclamation-circle"></i>
                                        {{ __('social.character_limit_exceeded') }}
                                    </span>
                                </template>
                                <template x-if="getEditCharacterStatus() === 'warning'">
                                    <span class="text-orange-600 dark:text-orange-400 font-medium flex items-center gap-1">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span x-text="getEditCharacterLimit() - (editingPost.content?.length || 0)"></span>
                                        {{ __('social.characters_remaining') }}
                                    </span>
                                </template>
                                <template x-if="getEditCharacterStatus() === 'ok' || getEditCharacterStatus() === 'caution'">
                                    <span class="text-gray-500 dark:text-gray-400">
                                        <span x-text="getEditCharacterLimit() - (editingPost.content?.length || 0)"></span>
                                        {{ __('social.characters_remaining') }}
                                    </span>
                                </template>
                            </div>
                        </div>
                        <!-- Progress Bar -->
                        <div class="h-1.5 bg-gray-200 dark:bg-gray-600 rounded-full overflow-hidden">
                            <div class="h-full transition-all duration-300 rounded-full"
                                 :class="{
                                     'bg-green-500': getEditCharacterStatus() === 'ok',
                                     'bg-yellow-500': getEditCharacterStatus() === 'caution',
                                     'bg-orange-500': getEditCharacterStatus() === 'warning',
                                     'bg-red-500': getEditCharacterStatus() === 'exceeded'
                                 }"
                                 :style="'width: ' + Math.min(getEditCharacterPercentage(), 100) + '%'">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Media Management Section -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            <i class="fas fa-image ms-1"></i>
                            {{ __('social.media') }}
                        </label>
                        <span class="text-xs text-gray-500 dark:text-gray-400"
                              x-show="editingPost.media && editingPost.media.length > 0"
                              x-text="'(' + (editingPost.media?.length || 0) + ' {{ __('social.items') }})'"></span>
                    </div>

                    <!-- Media Grid with Drag & Drop -->
                    <div class="grid grid-cols-4 gap-2"
                         x-show="editingPost.media && editingPost.media.length > 0"
                         @dragend="editMediaDraggedIndex = null; editMediaDragOverIndex = null">
                        <template x-for="(media, index) in editingPost.media" :key="media.url || index">
                            <div class="relative group"
                                 draggable="true"
                                 @dragstart="editMediaDraggedIndex = index; $event.dataTransfer.effectAllowed = 'move'"
                                 @dragover.prevent="if (editMediaDraggedIndex !== null && editMediaDraggedIndex !== index) { editMediaDragOverIndex = index; }"
                                 @dragleave="if (editMediaDragOverIndex === index) editMediaDragOverIndex = null"
                                 @drop.prevent="if (editMediaDraggedIndex !== null && editMediaDraggedIndex !== index) { reorderEditMedia(editMediaDraggedIndex, index); editMediaDraggedIndex = null; editMediaDragOverIndex = null; }"
                                 :class="{
                                     'ring-2 ring-indigo-500 ring-offset-2': editMediaDragOverIndex === index,
                                     'opacity-50': editMediaDraggedIndex === index
                                 }">

                                <!-- Media Thumbnail -->
                                <template x-if="media.type === 'video'">
                                    <div class="relative">
                                        <video :src="media.url || media.preview_url" class="w-full h-20 object-cover rounded-lg"></video>
                                        <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-lg">
                                            <i class="fas fa-play-circle text-white text-xl"></i>
                                        </div>
                                    </div>
                                </template>
                                <template x-if="media.type === 'image' || !media.type">
                                    <img :src="media.url || media.preview_url" class="w-full h-20 object-cover rounded-lg">
                                </template>

                                <!-- Order Badge -->
                                <div class="absolute top-1 start-1 bg-black/60 text-white text-xs px-1.5 py-0.5 rounded-full font-medium"
                                     x-text="index + 1"></div>

                                <!-- Drag Handle (visible on hover) -->
                                <div class="absolute top-1 end-1 opacity-0 group-hover:opacity-100 transition-opacity cursor-move bg-black/60 text-white text-xs px-1.5 py-0.5 rounded">
                                    <i class="fas fa-grip-vertical"></i>
                                </div>

                                <!-- Remove Button (visible on hover) -->
                                <button @click="removeEditMedia(index)"
                                        type="button"
                                        class="absolute bottom-1 end-1 opacity-0 group-hover:opacity-100 transition-opacity bg-red-500 hover:bg-red-600 text-white text-xs px-1.5 py-0.5 rounded"
                                        title="{{ __('social.remove_media') }}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>

                                <!-- Upload Progress Overlay -->
                                <template x-if="media.uploading">
                                    <div class="absolute inset-0 bg-black/60 flex flex-col items-center justify-center rounded-lg">
                                        <div class="animate-spin rounded-full h-6 w-6 border-2 border-white border-t-transparent"></div>
                                        <span class="text-white text-xs mt-1">{{ __('social.uploading') }}</span>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Add Media Area -->
                    <div class="mt-2">
                        <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 text-center cursor-pointer hover:border-indigo-400 hover:bg-indigo-50/50 dark:hover:bg-indigo-900/10 transition"
                             @click="$refs.editMediaInput.click()"
                             @dragover.prevent="editMediaDragging = true"
                             @dragleave.prevent="editMediaDragging = false"
                             @drop.prevent="handleEditMediaDrop($event)"
                             :class="{ 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20': editMediaDragging }">
                            <input type="file"
                                   x-ref="editMediaInput"
                                   @change="handleEditMediaUpload($event)"
                                   multiple
                                   accept="image/*,video/*"
                                   class="hidden">
                            <i class="fas fa-cloud-upload-alt text-2xl text-gray-400 dark:text-gray-500 mb-1"></i>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('social.drop_media_here') }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {{ __('social.supported_formats') }}
                            </p>
                        </div>
                    </div>

                    <!-- Reorder Hint -->
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2"
                       x-show="editingPost.media && editingPost.media.length > 1">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ __('social.drag_to_reorder') }}
                    </p>
                </div>

                <!-- AI Content Assistance Panel -->
                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                    <button @click="showEditAIPanel = !showEditAIPanel"
                            type="button"
                            class="w-full flex items-center justify-between p-3 bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-900/20 dark:to-indigo-900/20 hover:from-purple-100 hover:to-indigo-100 dark:hover:from-purple-900/30 dark:hover:to-indigo-900/30 transition">
                        <span class="flex items-center gap-2 text-sm font-medium text-purple-700 dark:text-purple-300">
                            <i class="fas fa-magic"></i>
                            {{ __('social.ai_assistant') }}
                        </span>
                        <i class="fas transition-transform duration-200"
                           :class="showEditAIPanel ? 'fa-chevron-up' : 'fa-chevron-down'"
                           class="text-purple-500"></i>
                    </button>

                    <div x-show="showEditAIPanel"
                         x-collapse
                         class="p-4 space-y-4 bg-white dark:bg-gray-800">

                        <!-- Quick AI Actions -->
                        <div class="flex flex-wrap gap-2">
                            <button @click="generateEditHashtags()"
                                    :disabled="editAILoading || !editingPost.content.trim()"
                                    type="button"
                                    class="px-3 py-1.5 text-xs bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded-full hover:bg-purple-200 dark:hover:bg-purple-900/50 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                <template x-if="!editAILoading || editAILoadingType !== 'hashtags'">
                                    <span><i class="fas fa-hashtag"></i> {{ __('social.suggest_hashtags') }}</span>
                                </template>
                                <template x-if="editAILoading && editAILoadingType === 'hashtags'">
                                    <span><i class="fas fa-spinner fa-spin"></i> {{ __('common.loading') }}</span>
                                </template>
                            </button>

                            <button @click="transformEditContent('emojis')"
                                    :disabled="editAILoading || !editingPost.content.trim()"
                                    type="button"
                                    class="px-3 py-1.5 text-xs bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 rounded-full hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                <template x-if="!editAILoading || editAILoadingType !== 'emojis'">
                                    <span><i class="fas fa-smile"></i> {{ __('social.add_emojis') }}</span>
                                </template>
                                <template x-if="editAILoading && editAILoadingType === 'emojis'">
                                    <span><i class="fas fa-spinner fa-spin"></i></span>
                                </template>
                            </button>

                            <button @click="improveEditContent()"
                                    :disabled="editAILoading || !editingPost.content.trim()"
                                    type="button"
                                    class="px-3 py-1.5 text-xs bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full hover:bg-blue-200 dark:hover:bg-blue-900/50 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1">
                                <template x-if="!editAILoading || editAILoadingType !== 'improve'">
                                    <span><i class="fas fa-lightbulb"></i> {{ __('social.improve') }}</span>
                                </template>
                                <template x-if="editAILoading && editAILoadingType === 'improve'">
                                    <span><i class="fas fa-spinner fa-spin"></i></span>
                                </template>
                            </button>
                        </div>

                        <!-- Content Transformations -->
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">{{ __('social.transform_content') }}</p>
                            <div class="flex flex-wrap gap-2">
                                <button @click="transformEditContent('shorter')"
                                        :disabled="editAILoading || !editingPost.content.trim()"
                                        type="button"
                                        class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50">
                                    <i class="fas fa-compress-alt me-1"></i> {{ __('social.make_shorter') }}
                                </button>
                                <button @click="transformEditContent('longer')"
                                        :disabled="editAILoading || !editingPost.content.trim()"
                                        type="button"
                                        class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50">
                                    <i class="fas fa-expand-alt me-1"></i> {{ __('social.make_longer') }}
                                </button>
                                <button @click="transformEditContent('formal')"
                                        :disabled="editAILoading || !editingPost.content.trim()"
                                        type="button"
                                        class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50">
                                    <i class="fas fa-user-tie me-1"></i> {{ __('social.more_formal') }}
                                </button>
                                <button @click="transformEditContent('casual')"
                                        :disabled="editAILoading || !editingPost.content.trim()"
                                        type="button"
                                        class="px-3 py-1 text-xs border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition disabled:opacity-50">
                                    <i class="fas fa-smile-wink me-1"></i> {{ __('social.more_casual') }}
                                </button>
                            </div>
                        </div>

                        <!-- AI Suggestions Display -->
                        <template x-if="editAISuggestions.hashtags && editAISuggestions.hashtags.length > 0">
                            <div class="p-3 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-medium text-purple-700 dark:text-purple-300">
                                        <i class="fas fa-hashtag me-1"></i> {{ __('social.suggested_hashtags') }}
                                    </p>
                                    <button @click="insertAllEditHashtags()"
                                            type="button"
                                            class="text-xs text-purple-600 hover:text-purple-800 dark:text-purple-400 dark:hover:text-purple-200">
                                        {{ __('social.insert_all') }}
                                    </button>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="tag in editAISuggestions.hashtags" :key="tag">
                                        <button @click="insertEditHashtag(tag)"
                                                type="button"
                                                class="px-2 py-0.5 text-xs bg-white dark:bg-gray-800 rounded border border-purple-200 dark:border-purple-700 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition"
                                                x-text="tag.startsWith('#') ? tag : '#' + tag"></button>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <!-- Improvement Suggestions -->
                        <template x-if="editAISuggestions.improved">
                            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs font-medium text-blue-700 dark:text-blue-300">
                                        <i class="fas fa-lightbulb me-1"></i> {{ __('social.improved_content') }}
                                    </p>
                                    <button @click="applyEditSuggestion(editAISuggestions.improved)"
                                            type="button"
                                            class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                                        {{ __('social.apply') }}
                                    </button>
                                </div>
                                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap" x-text="editAISuggestions.improved"></p>
                            </div>
                        </template>

                        <!-- Error Display -->
                        <template x-if="editAIError">
                            <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                                <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <span x-text="editAIError"></span>
                                </p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Schedule (for draft/scheduled posts) -->
                <template x-if="editingPost.status === 'draft' || editingPost.status === 'scheduled'">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <i class="fas fa-clock ms-1"></i>
                                {{ __('social.schedule_datetime') }}
                            </label>
                            <!-- Timezone indicator -->
                            <span class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                <template x-if="editTimezoneLoading">
                                    <span><i class="fas fa-spinner fa-spin"></i></span>
                                </template>
                                <template x-if="!editTimezoneLoading">
                                    <span>
                                        <i class="fas fa-globe"></i>
                                        <span x-text="editTimezone || 'UTC'"></span>
                                    </span>
                                </template>
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">{{ __('social.date') }}</label>
                                <input type="date" x-model="editingPost.scheduledDate"
                                       :min="minDate"
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-2">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">{{ __('social.time') }}</label>
                                <input type="time" x-model="editingPost.scheduledTime"
                                       class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-2">
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Footer -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <button @click="showEditPostModal = false"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200">
                    {{ __('common.cancel') }}
                </button>
                <div class="flex gap-3">
                    <button @click="updatePost()"
                            :disabled="isUpdating || !editingPost.content.trim() || getEditCharacterStatus() === 'exceeded'"
                            class="px-6 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg font-medium hover:shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isUpdating">
                            <i class="fas fa-save ms-2"></i>
                            {{ __('social.save_changes') }}
                        </span>
                        <span x-show="isUpdating">
                            <i class="fas fa-spinner fa-spin ms-2"></i>
                            {{ __('social.saving') }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
