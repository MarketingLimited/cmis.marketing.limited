{{-- YouTube Platform Options --}}
<div x-show="platform === 'youtube'" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.video_title') }} <span class="text-red-500">*</span></label>
        <input type="text" x-model="content.platforms.youtube.video_title"
               placeholder="{{ __('publish.video_title_placeholder') }}"
               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm" required>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.video_description') }}</label>
        <textarea x-model="content.platforms.youtube.description" rows="3"
                  placeholder="{{ __('publish.video_description_placeholder') }}"
                  class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.category') }}</label>
            <select x-model="content.platforms.youtube.category" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="entertainment">{{ __('publish.cat_entertainment') }}</option>
                <option value="education">{{ __('publish.cat_education') }}</option>
                <option value="howto">{{ __('publish.cat_howto') }}</option>
                <option value="gaming">{{ __('publish.cat_gaming') }}</option>
                <option value="sports">{{ __('publish.cat_sports') }}</option>
                <option value="tech">{{ __('publish.cat_tech') }}</option>
                <option value="news">{{ __('publish.cat_news') }}</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.visibility') }}</label>
            <select x-model="content.platforms.youtube.visibility" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
                <option value="public">{{ __('publish.public') }}</option>
                <option value="unlisted">{{ __('publish.unlisted') }}</option>
                <option value="private">{{ __('publish.private') }}</option>
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.tags') }}</label>
        <input type="text" x-model="content.platforms.youtube.tags"
               placeholder="{{ __('publish.tags_placeholder') }}"
               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
        <p class="text-xs text-gray-500 mt-1">{{ __('publish.tags_hint') }}</p>
    </div>
    <div class="flex items-center gap-4">
        <label class="flex items-center text-sm">
            <input type="checkbox" x-model="content.platforms.youtube.notify_subscribers" class="rounded ms-2">
            {{ __('publish.notify_subscribers') }}
        </label>
        <label class="flex items-center text-sm">
            <input type="checkbox" x-model="content.platforms.youtube.embeddable" class="rounded ms-2">
            {{ __('publish.embeddable') }}
        </label>
        {{-- PHASE 5A: CREATE FIRST LIKE (VISTASOCIAL PARITY) --}}
        <label class="flex items-center text-sm">
            <input type="checkbox" x-model="content.platforms.youtube.create_first_like" class="rounded ms-2">
            {{ __('publish.create_first_like') }}
        </label>
    </div>
</div>
