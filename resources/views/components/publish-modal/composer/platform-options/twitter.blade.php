{{-- Twitter Platform Options --}}
<div x-show="platform === 'twitter'" class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('publish.reply_settings') }}</label>
        <select x-model="content.platforms.twitter.reply_settings" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-sm">
            <option value="everyone">{{ __('publish.everyone_can_reply') }}</option>
            <option value="following">{{ __('publish.following_only') }}</option>
            <option value="mentioned">{{ __('publish.mentioned_only') }}</option>
        </select>
    </div>
</div>
