    <div x-show="showMentionPicker"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50"
         style="display: none;"
         @click.self="showMentionPicker = false">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md mx-4"
             @click.stop>
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('publish.mention_picker') }}</h3>
                <button @click="showMentionPicker = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Search --}}
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="relative">
                    <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text"
                           x-model="mentionSearch"
                           class="w-full ps-10 pe-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                           :placeholder="'{{ __('publish.search_accounts') }}'">
                </div>
            </div>

            {{-- Account List --}}
            <div class="max-h-96 overflow-y-auto">
                <template x-if="availableMentions.length === 0">
                    <div class="text-center py-8">
                        <i class="fas fa-user-slash text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500 text-sm">{{ __('publish.no_accounts_found') }}</p>
                    </div>
                </template>
                <template x-for="profile in availableMentions" :key="profile.id">
                    <button @click="insertMention(profile)"
                            class="w-full flex items-center gap-3 px-6 py-3 hover:bg-gray-50 transition text-start">
                        <img :src="profile.avatar_url || '/images/default-avatar.png'"
                             :alt="profile.account_name"
                             class="w-10 h-10 rounded-full">
                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-gray-900 truncate" x-text="profile.account_name"></p>
                            <p class="text-sm text-gray-500 truncate">
                                <i class="fab" :class="'fa-' + profile.platform" class="me-1"></i>
                                <span x-text="profile.platform_handle || '@' + profile.account_name.replace(/\s+/g, '')"></span>
                            </p>
                        </div>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- PHASE 3: Calendar View Modal --}}
