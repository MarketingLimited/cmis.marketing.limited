{{-- Google Business Profile Platform Options --}}
<div x-show="platform === 'google_business'" class="space-y-4">
    {{-- Post Type Selection --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fab fa-google text-blue-600 me-1"></i>{{ __('publish.gbp_post_type') }}
        </label>
        <select x-model="content.platforms.google_business.post_type" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm font-medium">
            <option value="update">{{ __('publish.gbp_update') }}</option>
            <option value="event">{{ __('publish.gbp_event') }}</option>
            <option value="offer">{{ __('publish.gbp_offer') }}</option>
        </select>
    </div>

    {{-- Call-to-Action for Update Posts --}}
    <div x-show="content.platforms.google_business.post_type === 'update'" class="bg-green-50 border border-green-200 rounded-lg p-3">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fas fa-mouse-pointer text-green-600 me-1"></i>{{ __('publish.gbp_cta_button') }}
        </label>
        <select x-model="content.platforms.google_business.cta_type" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm mb-3">
            <option value="">{{ __('publish.gbp_no_cta') }}</option>
            <option value="BOOK">{{ __('publish.gbp_cta_book') }}</option>
            <option value="ORDER">{{ __('publish.gbp_cta_order') }}</option>
            <option value="SHOP">{{ __('publish.gbp_cta_shop') }}</option>
            <option value="LEARN_MORE">{{ __('publish.gbp_cta_learn_more') }}</option>
            <option value="SIGN_UP">{{ __('publish.gbp_cta_sign_up') }}</option>
            <option value="CALL">{{ __('publish.gbp_cta_call') }}</option>
        </select>
        <div x-show="content.platforms.google_business.cta_type && content.platforms.google_business.cta_type !== 'CALL'">
            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_cta_url') }}</label>
            <input type="url" x-model="content.platforms.google_business.cta_url"
                   placeholder="https://example.com"
                   class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
    </div>

    {{-- Event Post Fields --}}
    <div x-show="content.platforms.google_business.post_type === 'event'" class="bg-purple-50 border border-purple-200 rounded-lg p-3 space-y-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-calendar-alt text-purple-600 me-1"></i>{{ __('publish.gbp_event_title') }} <span class="text-red-500">*</span>
            </label>
            <input type="text" x-model="content.platforms.google_business.event_title"
                   placeholder="{{ __('publish.gbp_event_title_placeholder') }}"
                   class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_event_start_date') }}</label>
                <input type="date" x-model="content.platforms.google_business.event_start_date"
                       class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_event_start_time') }}</label>
                <input type="time" x-model="content.platforms.google_business.event_start_time"
                       class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_event_end_date') }}</label>
                <input type="date" x-model="content.platforms.google_business.event_end_date"
                       class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_event_end_time') }}</label>
                <input type="time" x-model="content.platforms.google_business.event_end_time"
                       class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
        </div>
    </div>

    {{-- Offer Post Fields --}}
    <div x-show="content.platforms.google_business.post_type === 'offer'" class="bg-orange-50 border border-orange-200 rounded-lg p-3 space-y-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                <i class="fas fa-tag text-orange-600 me-1"></i>{{ __('publish.gbp_offer_title') }} <span class="text-red-500">*</span>
            </label>
            <input type="text" x-model="content.platforms.google_business.offer_title"
                   placeholder="{{ __('publish.gbp_offer_title_placeholder') }}"
                   class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_coupon_code') }}</label>
            <input type="text" x-model="content.platforms.google_business.offer_coupon_code"
                   placeholder="{{ __('publish.gbp_coupon_placeholder') }}"
                   class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_redeem_url') }}</label>
            <input type="url" x-model="content.platforms.google_business.offer_redeem_url"
                   placeholder="https://example.com/redeem"
                   class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">{{ __('publish.gbp_terms_conditions') }}</label>
            <textarea x-model="content.platforms.google_business.offer_terms_conditions" rows="2"
                      placeholder="{{ __('publish.gbp_terms_placeholder') }}"
                      class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
        </div>
    </div>
</div>
