{{-- Posts Grid View Component --}}
<div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
    <template x-for="post in sortedFilteredPosts" :key="post.post_id">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden hover:shadow-xl hover:shadow-gray-200/50 dark:hover:shadow-gray-900/50 hover:-translate-y-1 transition-all duration-300 group relative"
             :class="{'ring-2 ring-indigo-500 dark:ring-indigo-400 ring-offset-2 dark:ring-offset-gray-900': selectedPosts.includes(post.post_id)}">
            <!-- Selection Checkbox - Enhanced -->
            <div class="absolute top-3 right-3 z-10">
                <input type="checkbox"
                       :checked="selectedPosts.includes(post.post_id)"
                       @change="togglePostSelection(post.post_id)"
                       class="w-5 h-5 text-indigo-600 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-indigo-500 focus:ring-offset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200 cursor-pointer bg-white dark:bg-gray-700"
                       :class="{'opacity-100': selectedPosts.includes(post.post_id)}">
            </div>

            <!-- Platform Badge & Status - Enhanced -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/30">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shadow-sm"
                         :class="{
                             'bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400': post.platform === 'facebook',
                             'bg-gradient-to-br from-purple-100 to-pink-100 dark:from-purple-900/40 dark:to-pink-900/40 text-pink-600 dark:text-pink-400': post.platform === 'instagram',
                             'bg-sky-100 dark:bg-sky-900/40 text-sky-500 dark:text-sky-400': post.platform === 'twitter',
                             'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300': post.platform === 'linkedin'
                         }">
                        <i :class="{
                            'fab fa-facebook-f': post.platform === 'facebook',
                            'fab fa-instagram': post.platform === 'instagram',
                            'fab fa-twitter': post.platform === 'twitter',
                            'fab fa-linkedin-in': post.platform === 'linkedin'
                        }"></i>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700 dark:text-gray-200 text-sm" x-text="post.account_username || post.platform"></span>
                    </div>
                </div>

                <!-- Status Badge - Enhanced -->
                <span :class="{
                    'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300 border-yellow-200 dark:border-yellow-800': post.status === 'scheduled',
                    'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 border-green-200 dark:border-green-800': post.status === 'published',
                    'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border-gray-200 dark:border-gray-600': post.status === 'draft',
                    'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300 border-red-200 dark:border-red-800': post.status === 'failed'
                }" class="px-2.5 py-1 rounded-lg text-xs font-semibold border">
                    <span x-text="getStatusLabel(post.status)"></span>
                </span>
            </div>

            <!-- Post Content - Enhanced -->
            <div class="p-4">
                <p class="text-gray-700 dark:text-gray-300 text-sm line-clamp-3 mb-3 leading-relaxed" x-text="post.post_text"></p>

                <!-- Media Preview - Enhanced -->
                <template x-if="post.media && post.media.length > 0">
                    <div class="relative mb-3 rounded-xl overflow-hidden group/media">
                        <template x-if="post.media[0].type === 'video'">
                            <div class="relative">
                                <video :src="post.media[0].url" class="w-full h-40 object-cover"></video>
                                <div class="absolute inset-0 flex items-center justify-center bg-black/40 group-hover/media:bg-black/50 transition-colors">
                                    <i class="fas fa-play-circle text-white text-5xl opacity-90 group-hover/media:opacity-100 group-hover/media:scale-110 transition-all"></i>
                                </div>
                            </div>
                        </template>
                        <template x-if="post.media[0].type !== 'video'">
                            <img :src="post.media[0].url" class="w-full h-40 object-cover group-hover/media:scale-105 transition-transform duration-300">
                        </template>
                        <div x-show="post.media.length > 1"
                             class="absolute bottom-2 left-2 bg-black/70 backdrop-blur-sm text-white text-xs px-2.5 py-1 rounded-full font-medium">
                            <i class="fas fa-images ms-1"></i>
                            <span x-text="post.media.length"></span>
                        </div>
                    </div>
                </template>

                <!-- Metrics (for published posts) - Enhanced -->
                <template x-if="post.status === 'published'">
                    <div class="flex items-center justify-between py-2.5 border-t border-b border-gray-100 dark:border-gray-700 mb-3 text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex items-center gap-1.5 hover:text-red-500 dark:hover:text-red-400 transition-colors cursor-pointer" title="{{ __('social.likes') }}">
                            <i class="far fa-heart"></i>
                            <span class="tabular-nums" x-text="formatNumber(post.likes || 0)"></span>
                        </div>
                        <div class="flex items-center gap-1.5 hover:text-blue-500 dark:hover:text-blue-400 transition-colors cursor-pointer" title="{{ __('social.comments') }}">
                            <i class="far fa-comment"></i>
                            <span class="tabular-nums" x-text="formatNumber(post.comments || 0)"></span>
                        </div>
                        <div class="flex items-center gap-1.5 hover:text-green-500 dark:hover:text-green-400 transition-colors cursor-pointer" title="{{ __('social.shares') }}">
                            <i class="far fa-share-square"></i>
                            <span class="tabular-nums" x-text="formatNumber(post.shares || 0)"></span>
                        </div>
                        <div class="flex items-center gap-1.5 hover:text-purple-500 dark:hover:text-purple-400 transition-colors cursor-pointer" title="{{ __('social.reach') }}">
                            <i class="far fa-eye"></i>
                            <span class="tabular-nums" x-text="formatNumber(post.reach || 0)"></span>
                        </div>
                    </div>
                </template>

                <!-- Scheduled Time - Enhanced (uses post's timezone from inheritance hierarchy) -->
                <template x-if="post.scheduled_at && post.status === 'scheduled'">
                    <div class="flex items-center gap-2 text-xs text-yellow-700 dark:text-yellow-300 mb-3 bg-yellow-50 dark:bg-yellow-900/20 p-2.5 rounded-xl border border-yellow-100 dark:border-yellow-800/30">
                        <i class="fas fa-clock"></i>
                        <span x-text="formatDate(post.scheduled_at, post.display_timezone)"></span>
                    </div>
                </template>

                <!-- Published Time - Enhanced (uses post's timezone from inheritance hierarchy) -->
                <template x-if="post.published_at && post.status === 'published'">
                    <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-3">
                        <i class="fas fa-check-circle text-green-500 dark:text-green-400"></i>
                        <span>{{ __('social.published_at') }} <span x-text="formatDate(post.published_at, post.display_timezone)"></span></span>
                    </div>
                </template>

                <!-- Error Message for Failed Posts - Enhanced -->
                <template x-if="post.status === 'failed' && post.error_message">
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/30 rounded-xl p-3 mb-3">
                        <div class="flex items-start gap-2">
                            <i class="fas fa-exclamation-circle text-red-500 dark:text-red-400 mt-0.5"></i>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-red-800 dark:text-red-300">{{ __("social.failure_reason") }}</p>
                                <p class="text-xs text-red-700 dark:text-red-400 mt-1" x-text="post.error_message"></p>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Quick Actions - Enhanced -->
                <div class="flex items-center gap-1.5 border-t border-gray-100 dark:border-gray-700 pt-3">
                    <button @click="editPost(post)"
                            class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            title="{{ __('social.edit_post') }}">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button @click="duplicatePost(post)"
                            class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            title="{{ __('social.duplicate') }}">
                        <i class="fas fa-copy"></i>
                    </button>
                    <template x-if="post.status === 'scheduled' || post.status === 'draft'">
                        <button @click="publishNow(post.post_id)"
                                class="flex-1 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors"
                                title="{{ __('social.publish_now') }}">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </template>
                    <template x-if="post.status === 'failed'">
                        <button @click="retryPost(post.post_id)"
                                class="flex-1 bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"
                                title="{{ __('social.retry') }}">
                            <i class="fas fa-redo"></i>
                        </button>
                    </template>
                    <template x-if="post.permalink">
                        <a :href="post.permalink" target="_blank"
                           class="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-3 py-2 rounded-xl text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-center"
                           title="{{ __("social.view_post") }}">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    </template>
                    <button @click="deletePost(post.post_id)"
                            class="bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 px-3 py-2 rounded-xl text-sm hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors"
                            title="{{ __("common.delete") }}">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
