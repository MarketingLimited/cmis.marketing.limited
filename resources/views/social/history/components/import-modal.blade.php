<!-- Import Modal -->
<div x-show="showImportModal" x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-sm" @click="showImportModal = false"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full shadow-2xl"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-t-3xl p-6 text-white">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold">{{ __("social.import_posts") }}</h3>
                            <p class="text-blue-100 text-sm">{{ __("social.from_connected_accounts") }}</p>
                        </div>
                    </div>
                    <button @click="showImportModal = false" class="p-2 hover:bg-white/20 rounded-xl transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-5">
                <!-- Platform Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __("social.select_platform") }} <span class="text-red-500">*</span>
                    </label>
                    <select x-model="importData.integration_id"
                            class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                        <option value="">-- {{ __("social.select_connected_platform") }} --</option>
                        <template x-for="integration in integrations" :key="integration.integration_id">
                            <option :value="integration.integration_id"
                                    x-text="getPlatformName(integration.platform_type) + ' - ' + (integration.account_name || integration.username || '{{ __('social.unknown') }}')">
                            </option>
                        </template>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __("social.from_date") }}</label>
                        <input type="date" x-model="importData.start_date"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">{{ __("social.to_date") }}</label>
                        <input type="date" x-model="importData.end_date"
                               class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                    </div>
                </div>

                <!-- Limit -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ __("social.number_of_posts") }}
                    </label>
                    <input type="number" x-model="importData.limit" min="1" max="500" placeholder="100"
                           class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-900 border-0 rounded-xl focus:ring-2 focus:ring-blue-500 dark:text-white">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ __("social.max_limit_posts") }}</p>
                </div>

                <!-- Auto Analyze Toggle -->
                <label class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-900 rounded-xl cursor-pointer group">
                    <div>
                        <span class="font-medium text-gray-900 dark:text-white">{{ __("social.auto_analyze") }}</span>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __("social.ai_analyze_posts") }}</p>
                    </div>
                    <div class="relative">
                        <input type="checkbox" x-model="importData.auto_analyze" class="sr-only">
                        <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full transition"
                             :class="importData.auto_analyze ? 'bg-blue-600' : ''"></div>
                        <div class="absolute top-0.5 right-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform"
                             :class="importData.auto_analyze ? '-translate-x-5' : ''"></div>
                    </div>
                </label>
            </div>

            <!-- Footer -->
            <div class="p-6 pt-0">
                <button @click="startImport()"
                        :disabled="!importData.integration_id || importing"
                        class="w-full py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 disabled:from-gray-400 disabled:to-gray-400 text-white font-bold rounded-xl shadow-lg shadow-blue-500/25 disabled:shadow-none transition flex items-center justify-center gap-2">
                    <template x-if="!importing">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            {{ __("social.start_import") }}
                        </span>
                    </template>
                    <template x-if="importing">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ __("social.importing") }}...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </div>
</div>
