    <div x-show="showHashtagManager"
         x-cloak
         x-transition:enter="transform transition ease-in-out duration-300"
         x-transition:enter-start="translate-x-full rtl:-translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transform transition ease-in-out duration-300"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full rtl:-translate-x-full"
         class="fixed inset-y-0 end-0 w-96 bg-white shadow-2xl border-s border-gray-200 flex flex-col z-[200]">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.hashtag_manager') }}</h3>
            <button @click="showHashtagManager = false" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        {{-- Platform Selector (for Trending hashtags) --}}
        <div class="px-6 pt-4" x-data="{ hashtagTab: 'sets', selectedPlatform: 'instagram' }">
            <div class="mb-3">
                <label class="block text-xs font-medium text-gray-700 mb-2">{{ __('publish.fetch_from_platform') }}</label>
                <select x-model="selectedPlatform" @change="loadTrendingHashtags(selectedPlatform)"
                        class="w-full rounded-lg border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="instagram">
                        <i class="fab fa-instagram"></i> Instagram
                    </option>
                    <option value="twitter">
                        <i class="fab fa-twitter"></i> Twitter / X
                    </option>
                    <option value="tiktok">
                        <i class="fab fa-tiktok"></i> TikTok
                    </option>
                </select>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200">
                <div class="flex space-x-4 rtl:space-x-reverse">
                    <button @click="hashtagTab = 'sets'"
                            :class="hashtagTab === 'sets' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-3 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('publish.my_sets') }}
                    </button>
                    <button @click="hashtagTab = 'recent'"
                            :class="hashtagTab === 'recent' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-3 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('publish.recent') }}
                    </button>
                    <button @click="hashtagTab = 'trending'; if (trendingHashtags.length === 0) loadTrendingHashtags(selectedPlatform)"
                            :class="hashtagTab === 'trending' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                            class="py-3 px-1 border-b-2 font-medium text-sm transition">
                        {{ __('publish.trending') }}
                    </button>
                </div>
            </div>

                {{-- Tab Content --}}
                <div class="flex-1 overflow-y-auto p-6">
                    {{-- My Sets Tab --}}
                    <div x-show="hashtagTab === 'sets'" class="space-y-4">
                        <template x-if="hashtagSets.length === 0">
                            <div class="text-center py-8">
                                <i class="fas fa-hashtag text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500 text-sm">{{ __('publish.no_hashtag_sets') }}</p>
                                <button class="mt-3 text-indigo-600 hover:text-indigo-700 text-sm font-medium">
                                    {{ __('publish.create_set') }}
                                </button>
                            </div>
                        </template>
                        <template x-for="set in hashtagSets" :key="set.id">
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition cursor-pointer"
                                 @click="insertHashtags(set.hashtags)">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-gray-900" x-text="set.name"></h4>
                                    <span class="text-xs text-gray-500" x-text="set.hashtags.length + ' tags'"></span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="tag in set.hashtags.slice(0, 10)" :key="tag">
                                        <span class="text-xs text-indigo-600" x-text="'#' + tag"></span>
                                    </template>
                                    <template x-if="set.hashtags.length > 10">
                                        <span class="text-xs text-gray-400">+<span x-text="set.hashtags.length - 10"></span></span>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Recent Tab --}}
                    <div x-show="hashtagTab === 'recent'" class="space-y-3">
                        <template x-if="recentHashtags.length === 0">
                            <div class="text-center py-8">
                                <i class="fas fa-clock text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500 text-sm">{{ __('publish.no_recent_hashtags') }}</p>
                            </div>
                        </template>
                        <div class="flex flex-wrap gap-2">
                            <template x-for="tag in recentHashtags" :key="tag">
                                <button @click="insertHashtags([tag])"
                                        class="px-3 py-1.5 bg-gray-100 hover:bg-indigo-100 text-gray-700 hover:text-indigo-700 rounded-lg text-sm transition">
                                    <span x-text="'#' + tag"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Trending Tab --}}
                    <div x-show="hashtagTab === 'trending'" class="space-y-3">
                        {{-- Loading State --}}
                        <template x-if="loadingTrendingHashtags">
                            <div class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-2 border-indigo-500 border-t-transparent mx-auto mb-3"></div>
                                <p class="text-gray-500 text-sm">{{ __('publish.loading_trending_hashtags') }}</p>
                            </div>
                        </template>

                        {{-- Empty State --}}
                        <template x-if="!loadingTrendingHashtags && trendingHashtags.length === 0">
                            <div class="text-center py-8">
                                <i class="fas fa-fire text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500 text-sm mb-2">{{ __('publish.no_trending_hashtags') }}</p>
                                <p class="text-gray-400 text-xs" x-text="`{{ __('publish.select_platform_above') }}`"></p>
                            </div>
                        </template>

                        {{-- Hashtags List --}}
                        <div x-show="!loadingTrendingHashtags && trendingHashtags.length > 0" class="flex flex-wrap gap-2">
                            <template x-for="(tag, index) in trendingHashtags" :key="index">
                                <button @click="insertHashtags([tag])"
                                        class="px-3 py-1.5 bg-gradient-to-r from-orange-100 to-red-100 hover:from-orange-200 hover:to-red-200 text-orange-700 rounded-lg text-sm transition">
                                    <span x-text="'#' + tag"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

{{-- PHASE 1: Mention Picker Modal --}}
