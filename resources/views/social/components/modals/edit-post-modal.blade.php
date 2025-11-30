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
                        <p class="font-medium text-gray-900 dark:text-white" x-text="editingPost.account_username || editingPost.platform"></p>
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
                    <textarea x-model="editingPost.content" rows="5"
                              class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-lg p-4 resize-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="{{ __("social.post_content_placeholder") }}"></textarea>
                    <div class="text-xs text-gray-500 mt-1">
                        <span x-text="editingPost.content.length"></span> {{ __('social.characters') }}
                    </div>
                </div>

                <!-- Current Media Preview -->
                <template x-if="editingPost.media && editingPost.media.length > 0">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <i class="fas fa-image ms-1"></i>
                            {{ __('social.current_media') }}
                        </label>
                        <div class="grid grid-cols-4 gap-2">
                            <template x-for="(media, index) in editingPost.media" :key="index">
                                <div class="relative">
                                    <template x-if="media.type === 'video'">
                                        <div class="relative">
                                            <video :src="media.url" class="w-full h-20 object-cover rounded-lg"></video>
                                            <div class="absolute inset-0 flex items-center justify-center bg-black/30 rounded-lg">
                                                <i class="fas fa-play-circle text-white text-xl"></i>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="media.type === 'image' || !media.type">
                                        <img :src="media.url" class="w-full h-20 object-cover rounded-lg">
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Schedule (for draft/scheduled posts) -->
                <template x-if="editingPost.status === 'draft' || editingPost.status === 'scheduled'">
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            <i class="fas fa-clock ms-1"></i>
                            {{ __('social.schedule_datetime') }}
                            <template x-if="profileGroupTimezone">
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                    (<span x-text="profileGroupTimezone"></span>)
                                </span>
                            </template>
                        </label>
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

                        <!-- Timezone Info -->
                        <template x-if="profileGroupTimezone && profileGroupName">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                <i class="fas fa-info-circle me-1"></i>
                                {{ __('social.timezone') }}: <span x-text="profileGroupName"></span>
                                <template x-if="timezoneOffset">
                                    <span x-text="'(UTC' + timezoneOffset + ')'"></span>
                                </template>
                            </p>
                        </template>
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
                            :disabled="isUpdating || !editingPost.content.trim()"
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
