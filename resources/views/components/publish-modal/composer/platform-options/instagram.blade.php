{{-- Instagram Platform Options --}}
<div x-show="platform === 'instagram'" class="space-y-4">
    {{-- PHASE 5A: AUTO PUBLISH TYPE DROPDOWN (VISTASOCIAL PARITY + CAROUSEL) --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fas fa-paper-plane text-blue-600 me-1"></i>{{ __('publish.auto_publish_as') }}
        </label>
        <select x-model="content.platforms.instagram.post_type" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium">
            <option value="feed">
                <i class="fas fa-image"></i> {{ __('publish.publish_as_feed') }}
            </option>
            <template x-if="content.global.media.length >= 2 && content.global.media.length <= 10">
                <option value="carousel">
                    <i class="fas fa-images"></i> {{ __('publish.publish_as_carousel') }}
                </option>
            </template>
            <template x-if="content.global.media.some(m => m.type === 'video')">
                <option value="reel">
                    <i class="fas fa-video"></i> {{ __('publish.publish_as_reel') }}
                </option>
            </template>
            <option value="story">
                <i class="fas fa-circle"></i> {{ __('publish.publish_as_story') }}
            </option>
        </select>
        <p class="text-xs text-gray-500 mt-2" x-show="content.platforms.instagram.post_type === 'carousel'">
            <i class="fas fa-info-circle me-1"></i>{{ __('publish.instagram_carousel_info') }}
        </p>
    </div>

    {{-- PHASE 5A: TARGETING OPTIONS (VISTASOCIAL PARITY) --}}
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 border border-purple-200 rounded-lg p-3">
        <div class="flex items-center justify-between mb-3">
            <label class="text-sm font-semibold text-gray-800">
                <i class="fas fa-bullseye text-purple-600 me-1"></i>{{ __('publish.audience_targeting') }}
            </label>
            <button @click="content.platforms.instagram.targeting_enabled = !content.platforms.instagram.targeting_enabled"
                    class="text-xs text-purple-600 hover:text-purple-700 font-medium">
                <span x-text="content.platforms.instagram.targeting_enabled ? '{{ __('publish.disable') }}' : '{{ __('publish.enable') }}'"></span>
            </button>
        </div>

        <div x-show="content.platforms.instagram.targeting_enabled" class="space-y-3">
            {{-- Country Targeting --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.country_targeting') }}</label>
                <select x-model="content.platforms.instagram.target_country" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">{{ __('publish.all_countries') }}</option>
                    <option value="US">🇺🇸 United States</option>
                    <option value="GB">🇬🇧 United Kingdom</option>
                    <option value="SA">🇸🇦 Saudi Arabia</option>
                    <option value="AE">🇦🇪 United Arab Emirates</option>
                    <option value="EG">🇪🇬 Egypt</option>
                    <option value="FR">🇫🇷 France</option>
                    <option value="DE">🇩🇪 Germany</option>
                    <option value="IN">🇮🇳 India</option>
                </select>
            </div>

            {{-- Gender Targeting --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gender') }}</label>
                <select x-model="content.platforms.instagram.target_gender" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="all">{{ __('publish.all_genders') }}</option>
                    <option value="male">{{ __('publish.male') }}</option>
                    <option value="female">{{ __('publish.female') }}</option>
                </select>
            </div>

            {{-- Age Range --}}
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.min_age') }}</label>
                    <input type="number" x-model="content.platforms.instagram.target_min_age" min="13" max="65" placeholder="18" class="w-full rounded-lg border-gray-300 text-xs">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.max_age') }}</label>
                    <input type="number" x-model="content.platforms.instagram.target_max_age" min="13" max="65" placeholder="65" class="w-full rounded-lg border-gray-300 text-xs">
                </div>
            </div>

            {{-- Relationship Status --}}
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.relationship_status') }}</label>
                <select x-model="content.platforms.instagram.target_relationship" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">{{ __('publish.all_statuses') }}</option>
                    <option value="single">{{ __('publish.single') }}</option>
                    <option value="in_relationship">{{ __('publish.in_relationship') }}</option>
                    <option value="married">{{ __('publish.married') }}</option>
                    <option value="engaged">{{ __('publish.engaged') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- PHASE 2: Enhanced First Comment --}}
    <div>
        <div class="flex items-center justify-between mb-2">
            <label class="block text-sm font-medium text-gray-700">{{ __('publish.first_comment') }}</label>
            <span class="text-xs" :class="(content.platforms.instagram.first_comment?.length || 0) > 2200 ? 'text-red-500' : 'text-gray-500'">
                <span x-text="content.platforms.instagram.first_comment?.length || 0"></span>/2200
            </span>
        </div>
        <div class="relative">
            <textarea x-model="content.platforms.instagram.first_comment" rows="2"
                      placeholder="{{ __('publish.add_hashtags_comment') }}"
                      @input="updateFirstCommentCount()"
                      class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
            {{-- First Comment Toolbar --}}
            <div class="absolute bottom-2 start-2 end-2 flex items-center gap-2">
                <button @click="showEmojiPickerFirstComment = !showEmojiPickerFirstComment"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded text-xs"
                        title="{{ __('publish.emoji') }}">
                    <i class="far fa-smile"></i>
                </button>
                <button @click="showHashtagManager = true"
                        class="p-1 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded text-xs"
                        title="{{ __('publish.hashtags') }}">
                    <i class="fas fa-hashtag"></i>
                </button>
            </div>
        </div>
    </div>

    {{-- PHASE 5A: APPLY TO ALL PROFILES (VISTASOCIAL PARITY) --}}
    <div class="mt-4 pt-3 border-t border-gray-200">
        <button @click="applyToAllProfiles('instagram')"
                class="w-full text-center text-sm text-blue-600 hover:text-blue-700 font-medium py-2 hover:bg-blue-50 rounded transition">
            <i class="fas fa-copy me-1"></i>{{ __('publish.apply_to_all_instagram') }}
        </button>
    </div>
</div>
