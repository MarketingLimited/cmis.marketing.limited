<!-- Create Post Modal -->
<div x-show="showCreateModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     @keydown.escape.window="showCreateModal = false">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showCreateModal = false"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <form @submit.prevent="createPost">
                <div class="bg-white px-6 pt-5 pb-4">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-semibold text-gray-900">{{ __('social.create_social_post') }}</h3>
                        <button type="button" @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Platform Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('social.select_platforms') }}</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            <template x-for="platform in availablePlatforms" :key="platform.key">
                                <div
                                    @click="togglePlatform(platform.key)"
                                    :class="selectedPlatforms.includes(platform.key) ? 'border-blue-600 bg-blue-50' : 'border-gray-300 hover:border-gray-400'"
                                    class="border-2 rounded-lg p-4 cursor-pointer transition">
                                    <div class="flex items-center gap-3">
                                        <input type="checkbox" :checked="selectedPlatforms.includes(platform.key)" class="pointer-events-none">
                                        <span class="text-2xl" x-html="platform.icon"></span>
                                        <div class="flex-1">
                                            <div class="font-medium text-sm" x-text="platform.name"></div>
                                            <div class="text-xs text-gray-500" x-show="platform.accounts && platform.accounts.length > 0">
                                                <span x-text="platform.accounts.length"></span> {{ __('social.connected') }}
                                            </div>
                                            <div class="text-xs text-orange-600" x-show="!platform.accounts || platform.accounts.length === 0">
                                                {{ __('social.not_connected') }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Platform-Specific Accounts -->
                    <template x-if="selectedPlatforms.length > 0">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('social.select_accounts') }}</label>
                            <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 rounded-lg p-3">
                                <template x-for="platform in selectedPlatformsData" :key="platform.key">
                                    <div class="mb-4">
                                        <div class="font-medium text-sm text-gray-700 mb-2 flex items-center gap-2">
                                            <span x-html="platform.icon"></span>
                                            <span x-text="platform.name"></span>
                                        </div>
                                        <template x-if="platform.accounts && platform.accounts.length > 0">
                                            <div class="space-y-2 ms-6">
                                                <template x-for="account in platform.accounts" :key="account.id">
                                                    <label class="flex items-center gap-2 p-2 hover:bg-gray-50 rounded cursor-pointer">
                                                        <input
                                                            type="checkbox"
                                                            :value="account.id"
                                                            @change="toggleAccount(platform.key, account)"
                                                            :checked="isAccountSelected(account.id)"
                                                            class="rounded text-blue-600">
                                                        <template x-if="account.picture">
                                                            <img :src="account.picture" class="w-6 h-6 rounded-full" alt="">
                                                        </template>
                                                        <span class="text-sm" x-text="account.name || account.username"></span>
                                                        <template x-if="account.followers">
                                                            <span class="text-xs text-gray-500" x-text="account.followers + ' {{ __('social.followers') }}'"></span>
                                                        </template>
                                                    </label>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!platform.accounts || platform.accounts.length === 0">
                                            <div class="ms-6 text-sm text-orange-600">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                {{ __('social.connect_account') }}
                                                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg->org_id) }}" class="underline">{{ __('social.platform_connections') }}</a>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- Post Type Selection -->
                    <template x-if="selectedPlatforms.length > 0">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('social.post_type') }}</label>
                            <select x-model="postData.post_type" class="w-full border border-gray-300 rounded-lg px-4 py-2">
                                <option value="feed">{{ __('social.feed_post') }}</option>
                                <option value="story">{{ __('social.story') }}</option>
                                <option value="reel">{{ __('social.reel') }}</option>
                                <option value="carousel">{{ __('social.carousel') }}</option>
                                <option value="article">{{ __('common.article') }}</option>
                                <option value="poll">{{ __('common.poll') }}</option>
                            </select>
                        </div>
                    </template>

                    <!-- Content -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            {{ __('social.post_content') }}
                            <span x-show="postData.content.length > 0" class="text-gray-500 font-normal">
                                (<span x-text="postData.content.length"></span><span x-show="characterLimit > 0" x-text="'/' + characterLimit"></span> {{ __('social.characters') }})
                            </span>
                        </label>
                        <textarea
                            x-model="postData.content"
                            rows="6"
                            :maxlength="characterLimit > 0 ? characterLimit : undefined"
                            class="w-full border border-gray-300 rounded-lg px-4 py-2"
                            placeholder="{{ __('social.what_to_share') }}"></textarea>
                        <template x-if="characterLimit > 0 && postData.content.length > characterLimit * 0.9">
                            <p class="text-xs text-orange-600 mt-1">
                                <span x-text="characterLimit - postData.content.length"></span> {{ __('social.characters_remaining') }}
                            </p>
                        </template>
                        <p class="text-xs text-gray-500 mt-1">{{ __('social.emoji_tip') }}</p>
                    </div>

                    <!-- Media Upload -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('social.media_optional') }}</label>
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-gray-400 transition">
                            <input
                                type="file"
                                @change="handleFileUpload($event)"
                                multiple
                                accept="image/*,video/*"
                                class="hidden"
                                x-ref="fileInput">
                            <button
                                type="button"
                                @click="$refs.fileInput.click()"
                                class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-cloud-upload-alt text-3xl mb-2"></i>
                                <p>{{ __('social.click_upload') }}</p>
                            </button>
                            <p class="text-xs text-gray-500 mt-2">{{ __('social.file_types') }}</p>
                        </div>

                        <!-- File Preview -->
                        <template x-if="postData.files.length > 0">
                            <div class="mt-4 grid grid-cols-4 gap-3">
                                <template x-for="(file, index) in postData.files" :key="index">
                                    <div class="relative group">
                                        <div class="aspect-square rounded-lg overflow-hidden bg-gray-100">
                                            <template x-if="file.type.startsWith('image/')">
                                                <img :src="getFilePreview(file)" class="w-full h-full object-cover" alt="Preview">
                                            </template>
                                            <template x-if="file.type.startsWith('video/')">
                                                <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                                    <i class="fas fa-video text-gray-600 text-2xl"></i>
                                                </div>
                                            </template>
                                        </div>
                                        <button
                                            type="button"
                                            @click="removeFile(index)"
                                            class="absolute top-1 end-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Publishing Options -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('social.publishing_options') }}</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div
                                @click="postData.publish_type = 'now'"
                                :class="postData.publish_type === 'now' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                <i class="fas fa-bolt text-2xl mb-2" :class="postData.publish_type === 'now' ? 'text-blue-600' : 'text-gray-400'"></i>
                                <p class="font-medium text-sm">{{ __('social.publish_now') }}</p>
                            </div>
                            <div
                                @click="postData.publish_type = 'scheduled'"
                                :class="postData.publish_type === 'scheduled' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                <i class="far fa-clock text-2xl mb-2" :class="postData.publish_type === 'scheduled' ? 'text-blue-600' : 'text-gray-400'"></i>
                                <p class="font-medium text-sm">{{ __('social.schedule') }}</p>
                            </div>
                            <div
                                @click="postData.publish_type = 'queue'"
                                :class="postData.publish_type === 'queue' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                <i class="fas fa-stream text-2xl mb-2" :class="postData.publish_type === 'queue' ? 'text-blue-600' : 'text-gray-400'"></i>
                                <p class="font-medium text-sm">{{ __('social.add_to_queue') }}</p>
                            </div>
                            <div
                                @click="postData.publish_type = 'draft'"
                                :class="postData.publish_type === 'draft' ? 'border-blue-600 bg-blue-50' : 'border-gray-300'"
                                class="border-2 rounded-lg p-4 cursor-pointer text-center transition">
                                <i class="far fa-save text-2xl mb-2" :class="postData.publish_type === 'draft' ? 'text-blue-600' : 'text-gray-400'"></i>
                                <p class="font-medium text-sm">{{ __('social.save_draft') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Schedule DateTime -->
                    <template x-if="postData.publish_type === 'scheduled'">
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('social.schedule_datetime') }}
                                <template x-if="profileGroupTimezone && profileGroupName">
                                    <span class="text-xs text-gray-500 font-normal">
                                        (<span x-text="profileGroupName"></span> - <span x-text="profileGroupTimezone"></span>)
                                    </span>
                                </template>
                            </label>
                            <input
                                type="datetime-local"
                                x-model="postData.scheduled_at"
                                :min="minDateTime"
                                class="w-full border border-gray-300 rounded-lg px-4 py-2">

                            <!-- Timezone Info -->
                            <template x-if="profileGroupTimezone && timezoneOffset">
                                <p class="text-xs text-gray-500 mt-2">
                                    <i class="fas fa-clock me-1"></i>
                                    {{ __('social.times_in_timezone') }} <span x-text="profileGroupName"></span>
                                    <span x-text="'(UTC' + timezoneOffset + ')'"></span>
                                </p>
                            </template>
                        </div>
                    </template>
                </div>

                <!-- Footer Actions -->
                <div class="bg-gray-50 px-6 py-4 flex items-center justify-end gap-3">
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                        {{ __('common.cancel') }}
                    </button>
                    <button
                        type="submit"
                        :disabled="!canPublish || submitting"
                        :class="canPublish && !submitting ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
                        class="px-6 py-2 text-white rounded-lg font-medium">
                        <template x-if="submitting">
                            <span><i class="fas fa-spinner fa-spin me-2"></i> {{ __('social.publishing') }}</span>
                        </template>
                        <template x-if="!submitting">
                            <span x-text="postData.publish_type === 'now' ? '{{ __('social.publish_now') }}' : (postData.publish_type === 'scheduled' ? '{{ __('social.schedule_post') }}' : (postData.publish_type === 'queue' ? '{{ __('social.add_to_queue') }}' : '{{ __('social.save_draft') }}'))"></span>
                        </template>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
