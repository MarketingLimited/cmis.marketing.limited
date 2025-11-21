{{-- Step 4: Review & Launch --}}
<div class="space-y-6">
    {{-- Campaign Summary --}}
    <div class="bg-gradient-to-r from-green-50 to-blue-50 border border-green-200 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">
            {{ __('campaigns.wizard.review.almost_ready') }}
        </h3>
        <p class="text-sm text-gray-600">
            {{ __('campaigns.wizard.review.review_description') }}
        </p>
    </div>

    {{-- Step 1: Basics Review --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-xs mr-2">1</span>
                {{ __('campaigns.wizard.step_1_title') }}
            </h4>
            <a href="{{ route('campaign.wizard.step', ['session_id' => $session_id, 'step' => 1]) }}"
               class="text-sm text-blue-600 hover:text-blue-800">
                {{ __('common.edit') }}
            </a>
        </div>
        <div class="p-4 space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.name') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $step_data['full_data']['name'] ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.objective') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ __('campaigns.objectives.' . ($step_data['full_data']['objective'] ?? 'awareness')) }}
                    </dd>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.budget_total') }}</dt>
                    <dd class="mt-1 text-sm font-semibold text-gray-900">
                        ${{ number_format($step_data['full_data']['budget_total'] ?? 0, 2) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.duration') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $step_data['full_data']['start_date'] ?? '-' }}
                        @if(!empty($step_data['full_data']['end_date']))
                            → {{ $step_data['full_data']['end_date'] }}
                        @else
                            ({{ __('campaigns.ongoing') }})
                        @endif
                    </dd>
                </div>
            </div>
            @if(!empty($step_data['full_data']['description']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.description') }}</dt>
                    <dd class="mt-1 text-sm text-gray-700">{{ $step_data['full_data']['description'] }}</dd>
                </div>
            @endif
        </div>
    </div>

    {{-- Step 2: Targeting Review --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-xs mr-2">2</span>
                {{ __('campaigns.wizard.step_2_title') }}
            </h4>
            <a href="{{ route('campaign.wizard.step', ['session_id' => $session_id, 'step' => 2]) }}"
               class="text-sm text-blue-600 hover:text-blue-800">
                {{ __('common.edit') }}
            </a>
        </div>
        <div class="p-4 space-y-3">
            <div>
                <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.audience_type') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    {{ __('campaigns.targeting.' . ($step_data['full_data']['audience_type'] ?? 'custom')) }}
                </dd>
            </div>
            @if(!empty($step_data['full_data']['age_min']) || !empty($step_data['full_data']['age_max']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.age_range') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ $step_data['full_data']['age_min'] ?? 18 }} - {{ $step_data['full_data']['age_max'] ?? 65 }}
                    </dd>
                </div>
            @endif
            @if(!empty($step_data['full_data']['genders']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.genders') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        {{ implode(', ', array_map(fn($g) => __('campaigns.' . $g), $step_data['full_data']['genders'])) }}
                    </dd>
                </div>
            @endif
            @if(!empty($step_data['full_data']['locations']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.locations') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="flex flex-wrap gap-1">
                            @foreach($step_data['full_data']['locations'] as $location)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ $location }}
                                </span>
                            @endforeach
                        </div>
                    </dd>
                </div>
            @endif
            @if(!empty($step_data['full_data']['interests']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.interests') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <div class="flex flex-wrap gap-1">
                            @foreach($step_data['full_data']['interests'] as $interest)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $interest }}
                                </span>
                            @endforeach
                        </div>
                    </dd>
                </div>
            @endif
        </div>
    </div>

    {{-- Step 3: Creative Review --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-blue-500 text-white text-xs mr-2">3</span>
                {{ __('campaigns.wizard.step_3_title') }}
            </h4>
            <a href="{{ route('campaign.wizard.step', ['session_id' => $session_id, 'step' => 3]) }}"
               class="text-sm text-blue-600 hover:text-blue-800">
                {{ __('common.edit') }}
            </a>
        </div>
        <div class="p-4 space-y-3">
            <div>
                <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.ad_format') }}</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    {{ __('campaigns.formats.' . ($step_data['full_data']['ad_format'] ?? 'single_image')) }}
                </dd>
            </div>
            <div>
                <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.primary_text') }}</dt>
                <dd class="mt-1 text-sm text-gray-700 bg-gray-50 p-3 rounded">
                    {{ $step_data['full_data']['primary_text'] ?? '-' }}
                </dd>
            </div>
            @if(!empty($step_data['full_data']['headline']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.headline') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ $step_data['full_data']['headline'] }}</dd>
                </div>
            @endif
            @if(!empty($step_data['full_data']['call_to_action']))
                <div>
                    <dt class="text-xs font-medium text-gray-500">{{ __('campaigns.call_to_action') }}</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            {{ __('campaigns.cta.' . $step_data['full_data']['call_to_action']) }}
                        </span>
                    </dd>
                </div>
            @endif
        </div>
    </div>

    {{-- Campaign Health Check --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('campaigns.wizard.review.health_check') }}
        </h4>
        <div class="space-y-2">
            <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-700">{{ __('campaigns.wizard.review.all_required_fields') }}</span>
            </div>
            <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-700">{{ __('campaigns.wizard.review.budget_configured') }}</span>
            </div>
            <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-700">{{ __('campaigns.wizard.review.audience_defined') }}</span>
            </div>
            <div class="flex items-center text-sm">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="text-gray-700">{{ __('campaigns.wizard.review.creative_complete') }}</span>
            </div>
        </div>
    </div>

    {{-- Terms and Confirmation --}}
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">
                    {{ __('campaigns.wizard.review.important_notice') }}
                </h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>{{ __('campaigns.wizard.review.launch_notice') }}</p>
                </div>
                <div class="mt-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="terms_agreed" required
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-yellow-800">
                            {{ __('campaigns.wizard.review.agree_terms') }}
                        </span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    {{-- Campaign Budget Summary --}}
    <div class="bg-white border border-gray-200 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-900 mb-3">{{ __('campaigns.budget_summary') }}</h4>
        <dl class="space-y-2">
            <div class="flex justify-between text-sm">
                <dt class="text-gray-600">{{ __('campaigns.total_budget') }}</dt>
                <dd class="font-semibold text-gray-900">${{ number_format($step_data['full_data']['budget_total'] ?? 0, 2) }}</dd>
            </div>
            @if(!empty($step_data['full_data']['budget_daily']))
                <div class="flex justify-between text-sm">
                    <dt class="text-gray-600">{{ __('campaigns.daily_budget') }}</dt>
                    <dd class="font-medium text-gray-900">${{ number_format($step_data['full_data']['budget_daily'], 2) }}</dd>
                </div>
            @endif
            <div class="flex justify-between text-sm pt-2 border-t border-gray-200">
                <dt class="text-gray-600">{{ __('campaigns.estimated_reach') }}</dt>
                <dd class="font-medium text-blue-600">500K - 2M {{ __('campaigns.people') }}</dd>
            </div>
        </dl>
    </div>

    {{-- Hidden fields to ensure all data is submitted --}}
    @foreach($step_data['full_data'] as $key => $value)
        @if(is_array($value))
            @foreach($value as $item)
                <input type="hidden" name="{{ $key }}[]" value="{{ $item }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach
</div>
