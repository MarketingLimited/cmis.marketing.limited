    <!-- Queue Settings Modal - Enhanced UI/UX -->
    <div x-show="showQueueSettings" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
         @click.self="showQueueSettings = false"
         @keydown.escape.window="showQueueSettings = false">
        <div x-show="showQueueSettings"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4"
             class="bg-white dark:bg-gray-800 rounded-3xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-hidden flex flex-col"
             @click.stop>

            <!-- Header - Enhanced with pattern and better hierarchy -->
            <div class="relative p-6 sm:p-8 bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-700 text-white overflow-hidden">
                <!-- Subtle pattern overlay -->
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                        <defs>
                            <pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse">
                                <path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5"/>
                            </pattern>
                        </defs>
                        <rect width="100" height="100" fill="url(#grid)"/>
                    </svg>
                </div>

                <div class="relative flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center">
                                <i class="fas fa-rocket text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl sm:text-2xl font-bold leading-tight">
                                    {{ __("social.auto_publish_settings") }}
                                </h3>
                                <p class="text-purple-200 text-sm mt-0.5 leading-relaxed">
                                    {{ __("social.smart_scheduling_description") }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Close Button - Enhanced -->
                    <button @click="showQueueSettings = false"
                            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white/10 hover:bg-white/20 text-white/80 hover:text-white transition-all focus:outline-none focus:ring-2 focus:ring-white/50"
                            aria-label="{{ __('social.close') }}">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            <!-- Body - Enhanced with better spacing -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-6 bg-gray-50 dark:bg-gray-900/50">
                <div class="max-w-3xl mx-auto space-y-5">

                    <!-- Info Banner - Collapsible with better design -->
                    <div x-data="{ showInfo: true }"
                         class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border border-blue-200/50 dark:border-blue-800/50 rounded-2xl overflow-hidden">
                        <button @click="showInfo = !showInfo"
                                class="w-full p-4 flex items-center gap-3 text-end hover:bg-blue-100/50 dark:hover:bg-blue-900/30 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500">
                            <div class="w-10 h-10 bg-blue-500 dark:bg-blue-600 rounded-xl flex items-center justify-center flex-shrink-0 shadow-lg shadow-blue-500/30">
                                <i class="fas fa-lightbulb text-white"></i>
                            </div>
                            <div class="flex-1 text-end">
                                <p class="text-sm font-bold text-blue-900 dark:text-blue-100">{{ __("social.how_auto_publish_works") }}</p>
                                <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5" x-show="!showInfo">{{ __("social.click_for_details") }}</p>
                            </div>
                            <i class="fas fa-chevron-down text-blue-500 dark:text-blue-400 transition-transform duration-200" :class="{ 'rotate-180': showInfo }"></i>
                        </button>
                        <div x-show="showInfo" x-collapse class="px-4 pb-4">
                            <div class="bg-white/60 dark:bg-gray-800/60 rounded-xl p-4 space-y-3">
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">1</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ __("social.enable_auto_publish_instruction") }}</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">2</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ __("social.set_publish_times_instruction") }}</p>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div class="w-6 h-6 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                        <span class="text-xs font-bold text-purple-600 dark:text-purple-400">3</span>
                                    </div>
                                    <p class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">{{ __("social.auto_publish_description") }}.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Platform Queue Settings - Enhanced Cards -->
                    <div class="space-y-4">
                        <h4 class="text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-2 px-1">
                            <i class="fas fa-plug"></i>
                            {{ __('social.connected_accounts') }}
                            <span class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 px-2 py-0.5 rounded-full text-xs font-bold" x-text="connectedPlatforms.length"></span>
                        </h4>

                        <!-- Empty State -->
                        <template x-if="connectedPlatforms.length === 0">
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl p-8 text-center">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="fas fa-unlink text-2xl text-gray-400 dark:text-gray-500"></i>
                                </div>
                                <p class="text-gray-600 dark:text-gray-400 font-medium mb-2">{{ __('social.no_connected_accounts') }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-500">{{ __("social.connect_accounts_first") }}</p>
                            </div>
                        </template>

                        <template x-for="platform in connectedPlatforms" :key="platform.id">
                            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                                <!-- Platform Header - RTL Optimized -->
                                <div class="p-4 sm:p-5 flex items-center gap-4">
                                    <!-- Platform Icon -->
                                    <div class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0"
                                         :class="{
                                             'bg-gradient-to-br from-blue-500 to-blue-600': platform.type === 'facebook',
                                             'bg-gradient-to-br from-purple-500 via-pink-500 to-orange-400': platform.type === 'instagram',
                                             'bg-gradient-to-br from-sky-400 to-sky-500': platform.type === 'twitter',
                                             'bg-gradient-to-br from-blue-600 to-blue-700': platform.type === 'linkedin',
                                             'bg-gradient-to-br from-black to-gray-800': platform.type === 'tiktok'
                                         }">
                                        <i :class="{
                                            'fab fa-facebook-f': platform.type === 'facebook',
                                            'fab fa-instagram': platform.type === 'instagram',
                                            'fab fa-twitter': platform.type === 'twitter',
                                            'fab fa-linkedin-in': platform.type === 'linkedin',
                                            'fab fa-tiktok': platform.type === 'tiktok'
                                        }" class="text-white text-xl"></i>
                                    </div>

                                    <!-- Platform Info -->
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 dark:text-white truncate text-base" x-text="platform.name"></h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 capitalize" x-text="platform.type"></p>
                                    </div>

                                    <!-- Enable Toggle - RTL Optimized -->
                                    <label class="relative inline-flex items-center cursor-pointer flex-shrink-0 group">
                                        <span class="ms-3 text-sm font-semibold text-gray-700 dark:text-gray-300 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors hidden sm:inline">
                                            تفعيل
                                        </span>
                                        <input type="checkbox" class="sr-only peer"
                                               :checked="getQueueSetting(platform.integrationId, 'enabled')"
                                               @change="toggleQueue(platform.integrationId)">
                                        <div class="w-14 h-8 bg-gray-200 dark:bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 dark:peer-focus:ring-purple-800 rounded-full peer peer-checked:after:-translate-x-full rtl:peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[4px] after:right-[4px] rtl:after:right-auto rtl:after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all after:shadow-sm peer-checked:bg-gradient-to-r peer-checked:from-purple-600 peer-checked:to-indigo-600"></div>
                                    </label>
                                </div>

                                <!-- Queue Settings (shown when enabled) - Enhanced -->
                                <div x-show="getQueueSetting(platform.integrationId, 'enabled')"
                                     x-collapse
                                     class="border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
                                    <div class="p-4 sm:p-5 space-y-5">

                                        <!-- Posting Times - Enhanced -->
                                        <div x-data="{ times: ['09:00', '13:00', '18:00'] }">
                                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                                <div class="w-7 h-7 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-clock text-purple-600 dark:text-purple-400 text-xs"></i>
                                                </div>
                                                {{ __("social.daily_publish_times") }}
                                            </label>
                                            <div class="flex flex-wrap gap-2">
                                                <template x-for="(time, index) in times" :key="index">
                                                    <div class="group flex items-center gap-2 bg-white dark:bg-gray-800 border border-purple-200 dark:border-purple-800 rounded-xl px-3 py-2 shadow-sm hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all">
                                                        <i class="fas fa-clock text-purple-500 dark:text-purple-400 text-sm"></i>
                                                        <input type="time" x-model="times[index]"
                                                               class="border-0 bg-transparent text-sm font-medium text-gray-800 dark:text-gray-200 focus:ring-0 w-20 text-center p-0">
                                                        <button @click="times.splice(index, 1)"
                                                                class="w-6 h-6 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all opacity-0 group-hover:opacity-100">
                                                            <i class="fas fa-times text-xs"></i>
                                                        </button>
                                                    </div>
                                                </template>
                                                <button @click="times.push('12:00')"
                                                        class="flex items-center gap-2 px-4 py-2 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-sm font-medium text-gray-500 dark:text-gray-400 hover:border-purple-400 dark:hover:border-purple-500 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all">
                                                    <i class="fas fa-plus text-xs"></i>
                                                    إضافة
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Days of Week - Enhanced with circular buttons -->
                                        <div x-data="{ days: [1, 2, 3, 4, 5] }">
                                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                                <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-calendar-week text-indigo-600 dark:text-indigo-400 text-xs"></i>
                                                </div>
                                                {{ __("social.publish_days") }}
                                            </label>
                                            <div class="flex flex-wrap gap-2 sm:gap-3">
                                                <template x-for="day in [{v: 0, l: @json(__('social.days.sunday')), s: 'ح'}, {v: 1, l: @json(__('social.days.monday')), s: 'ن'}, {v: 2, l: @json(__('social.days.tuesday')), s: 'ث'}, {v: 3, l: @json(__('social.days.wednesday')), s: 'ر'}, {v: 4, l: @json(__('social.days.thursday')), s: 'خ'}, {v: 5, l: @json(__('social.days.friday')), s: 'ج'}, {v: 6, l: @json(__('social.days.saturday')), s: 'س'}]" :key="day.v">
                                                    <button @click="days.includes(day.v) ? days.splice(days.indexOf(day.v), 1) : days.push(day.v)"
                                                            :class="days.includes(day.v)
                                                                ? 'bg-gradient-to-br from-purple-600 to-indigo-600 text-white shadow-lg shadow-purple-500/30 scale-105'
                                                                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-purple-300 dark:hover:border-purple-600'"
                                                            class="w-12 h-12 sm:w-auto sm:h-auto sm:px-4 sm:py-2.5 rounded-xl text-sm font-bold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                                        <span class="hidden sm:inline" x-text="day.l"></span>
                                                        <span class="sm:hidden" x-text="day.s"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </div>

                                        <!-- Posts Per Day - Enhanced with stepper -->
                                        <div x-data="{ postsPerDay: 3 }">
                                            <label class="flex items-center gap-2 text-sm font-bold text-gray-700 dark:text-gray-300 mb-3">
                                                <div class="w-7 h-7 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-layer-group text-green-600 dark:text-green-400 text-xs"></i>
                                                </div>
                                                {{ __("social.posts_per_day") }}
                                            </label>
                                            <div class="inline-flex items-center bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">
                                                <button @click="postsPerDay = Math.max(1, postsPerDay - 1)"
                                                        class="w-12 h-12 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors border-l border-gray-200 dark:border-gray-700"
                                                        :disabled="postsPerDay <= 1"
                                                        :class="{ 'opacity-50 cursor-not-allowed': postsPerDay <= 1 }">
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <div class="w-16 h-12 flex items-center justify-center">
                                                    <span class="text-xl font-bold text-gray-900 dark:text-white tabular-nums" x-text="postsPerDay"></span>
                                                </div>
                                                <button @click="postsPerDay = Math.min(20, postsPerDay + 1)"
                                                        class="w-12 h-12 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors border-r border-gray-200 dark:border-gray-700"
                                                        :disabled="postsPerDay >= 20"
                                                        :class="{ 'opacity-50 cursor-not-allowed': postsPerDay >= 20 }">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-500 mt-2">{{ __("social.max_posts_per_day") }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer - Enhanced with clear action hierarchy -->
            <div class="p-4 sm:p-6 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex flex-col-reverse sm:flex-row justify-between items-center gap-3">
                <!-- Secondary Action -->
                <button @click="showQueueSettings = false"
                        class="w-full sm:w-auto px-6 py-3 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-xl font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300 dark:focus:ring-gray-600">
                    <i class="fas fa-times ms-2 text-sm"></i>
                    {{ __('social.close') }}
                </button>

                <!-- Primary Action -->
                <button @click="saveAllQueueSettings()"
                        class="w-full sm:w-auto px-8 py-3.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-bold shadow-lg shadow-purple-500/25 hover:shadow-xl hover:shadow-purple-500/30 transition-all focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ __('common.save_settings') }}</span>
                </button>
            </div>
        </div>
    </div>
