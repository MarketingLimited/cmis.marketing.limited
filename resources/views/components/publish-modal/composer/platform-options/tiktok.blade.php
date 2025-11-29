{{-- TikTok Platform Options --}}
<div x-show="platform === 'tiktok'" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.video_caption') }}</label>
        <input type="text" x-model="content.platforms.tiktok.video_title"
               placeholder="{{ __('publish.video_caption_placeholder') }}"
               class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.privacy') }}</label>
        <select x-model="content.platforms.tiktok.privacy" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            <option value="public">{{ __('publish.public') }}</option>
            <option value="friends">{{ __('publish.friends_only') }}</option>
            <option value="private">{{ __('publish.private') }}</option>
        </select>
    </div>
    <div class="flex items-center gap-4">
        <label class="flex items-center text-sm">
            <input type="checkbox" x-model="content.platforms.tiktok.allow_comments" class="rounded ms-2">
            {{ __('publish.allow_comments') }}
        </label>
        <label class="flex items-center text-sm">
            <input type="checkbox" x-model="content.platforms.tiktok.allow_duet" class="rounded ms-2">
            {{ __('publish.allow_duet') }}
        </label>
        <label class="flex items-center text-sm">
            <input type="checkbox" x-model="content.platforms.tiktok.allow_stitch" class="rounded ms-2">
            {{ __('publish.allow_stitch') }}
        </label>
    </div>
</div>
