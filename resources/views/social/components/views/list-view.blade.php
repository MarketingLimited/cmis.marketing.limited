{{-- Posts List View Component --}}
<div x-show="viewMode === 'list'" x-cloak class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700">
            <tr>
                <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <input type="checkbox" @change="toggleAllPosts($event)" class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600">
                </th>
                <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('social.platform') }}</th>
                <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('social.post_content') }}</th>
                <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('common.status') }}</th>
                <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('social.date') }}</th>
                <th class="px-4 py-4 text-end text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">{{ __('common.actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
            <template x-for="post in sortedFilteredPosts" :key="post.post_id">
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                    <td class="px-4 py-4">
                        <input type="checkbox"
                               :checked="selectedPosts.includes(post.post_id)"
                               @change="togglePostSelection(post.post_id)"
                               class="rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-indigo-600">
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center"
                                 :class="{
                                     'bg-blue-100 dark:bg-blue-900/40': post.platform === 'facebook',
                                     'bg-pink-100 dark:bg-pink-900/40': post.platform === 'instagram',
                                     'bg-sky-100 dark:bg-sky-900/40': post.platform === 'twitter',
                                     'bg-blue-100 dark:bg-blue-900/40': post.platform === 'linkedin'
                                 }">
                                <i :class="{
                                    'fab fa-facebook text-blue-600 dark:text-blue-400': post.platform === 'facebook',
                                    'fab fa-instagram text-pink-600 dark:text-pink-400': post.platform === 'instagram',
                                    'fab fa-twitter text-sky-500 dark:text-sky-400': post.platform === 'twitter',
                                    'fab fa-linkedin text-blue-700 dark:text-blue-300': post.platform === 'linkedin'
                                }"></i>
                            </div>
                            <span class="text-sm text-gray-700 dark:text-gray-300 font-medium" x-text="post.account_username || post.platform"></span>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            <template x-if="post.media && post.media.length > 0">
                                <img :src="post.media[0].url" class="w-10 h-10 object-cover rounded-lg shadow-sm">
                            </template>
                            <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-2 max-w-xs" x-text="post.post_text"></p>
                        </div>
                    </td>
                    <td class="px-4 py-4">
                        <span :class="{
                            'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300': post.status === 'scheduled',
                            'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300': post.status === 'published',
                            'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300': post.status === 'draft',
                            'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-300': post.status === 'failed'
                        }" class="px-2.5 py-1 rounded-lg text-xs font-semibold" x-text="getStatusLabel(post.status)"></span>
                    </td>
                    <td class="px-4 py-4 text-sm text-gray-500 dark:text-gray-400">
                        <span x-text="formatDate(post.scheduled_at || post.published_at || post.created_at)"></span>
                    </td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-1">
                            <button @click="editPost(post)" class="p-2 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="{{ __('social.edit_post') }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button @click="duplicatePost(post)" class="p-2 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors" title="{{ __('social.duplicate_post') }}">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button @click="deletePost(post.post_id)" class="p-2 text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors" title="{{ __('common.delete') }}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </template>
        </tbody>
    </table>

    <!-- Empty state for list view - Enhanced -->
    <div x-show="sortedFilteredPosts.length === 0" class="py-16 text-center">
        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-inbox text-3xl text-gray-400 dark:text-gray-500"></i>
        </div>
        <p class="text-gray-500 dark:text-gray-400 font-medium">{{ __("social.no_posts_found") }}</p>
    </div>
</div>
