{{-- Step 2: Targeting & Audience --}}
<div class="space-y-6">
    {{-- Audience Type Selection --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-3">
            {{ __('campaigns.audience_type') }} <span class="text-red-500">*</span>
        </label>
        <div class="space-y-3">
            @foreach($step_data['audience_types'] as $key => $label)
                <label class="relative flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50
                    {{ old('audience_type', $session['data']['audience_type'] ?? '') == $key ? 'border-blue-500 bg-blue-50' : 'border-gray-300' }}">
                    <input type="radio" name="audience_type" value="{{ $key }}" required
                           {{ old('audience_type', $session['data']['audience_type'] ?? '') == $key ? 'checked' : '' }}
                           class="mt-1 text-blue-600 focus:ring-blue-500">
                    <div class="ml-3 flex-1">
                        <span class="block text-sm font-medium text-gray-900">{{ $label }}</span>
                        <span class="block text-sm text-gray-500">
                            @if($key === 'custom')
                                {{ __('campaigns.wizard.targeting.custom_help') }}
                            @elseif($key === 'lookalike')
                                {{ __('campaigns.wizard.targeting.lookalike_help') }}
                            @elseif($key === 'saved')
                                {{ __('campaigns.wizard.targeting.saved_help') }}
                            @endif
                        </span>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Saved Audience Selection (conditional) --}}
    <div x-data="{ audienceType: '{{ old('audience_type', $session['data']['audience_type'] ?? '') }}' }"
         x-show="audienceType === 'saved'" style="display: none;">
        <label for="saved_audience_id" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.saved_audience') }} <span class="text-red-500">*</span>
        </label>
        <select name="saved_audience_id" id="saved_audience_id"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">{{ __('common.select') }}</option>
            @foreach($step_data['saved_audiences'] as $audience)
                <option value="{{ $audience->id }}"
                        {{ old('saved_audience_id', $session['data']['saved_audience_id'] ?? '') == $audience->id ? 'selected' : '' }}>
                    {{ $audience->name }}
                    @if($audience->estimated_size)
                        ({{ number_format($audience->estimated_size) }} {{ __('campaigns.people') }})
                    @endif
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.targeting.saved_audience_help') }}</p>
    </div>

    {{-- Age Range --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('campaigns.age_range') }}
        </label>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="age_min" class="block text-xs text-gray-600 mb-1">{{ __('campaigns.min_age') }}</label>
                <input type="number" name="age_min" id="age_min"
                       value="{{ old('age_min', $session['data']['age_min'] ?? 18) }}"
                       min="18" max="65"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div>
                <label for="age_max" class="block text-xs text-gray-600 mb-1">{{ __('campaigns.max_age') }}</label>
                <input type="number" name="age_max" id="age_max"
                       value="{{ old('age_max', $session['data']['age_max'] ?? 65) }}"
                       min="18" max="65"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.targeting.age_help') }}</p>
    </div>

    {{-- Gender Selection --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            {{ __('campaigns.genders') }}
        </label>
        <div class="space-y-2">
            @php
                $selectedGenders = old('genders', $session['data']['genders'] ?? []);
            @endphp
            @foreach(['all' => __('campaigns.all_genders'), 'male' => __('campaigns.male'), 'female' => __('campaigns.female')] as $key => $label)
                <label class="flex items-center">
                    <input type="checkbox" name="genders[]" value="{{ $key }}"
                           {{ in_array($key, (array)$selectedGenders) ? 'checked' : '' }}
                           class="rounded text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                </label>
            @endforeach
        </div>
    </div>

    {{-- Locations --}}
    <div>
        <label for="locations" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.locations') }}
        </label>
        <div class="mt-1">
            <select name="locations[]" id="locations" multiple
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    size="5">
                @php
                    $selectedLocations = old('locations', $session['data']['locations'] ?? []);
                    $countries = [
                        'SA' => __('campaigns.countries.saudi_arabia'),
                        'AE' => __('campaigns.countries.uae'),
                        'EG' => __('campaigns.countries.egypt'),
                        'KW' => __('campaigns.countries.kuwait'),
                        'QA' => __('campaigns.countries.qatar'),
                        'BH' => __('campaigns.countries.bahrain'),
                        'OM' => __('campaigns.countries.oman'),
                        'JO' => __('campaigns.countries.jordan'),
                        'LB' => __('campaigns.countries.lebanon'),
                    ];
                @endphp
                @foreach($countries as $code => $name)
                    <option value="{{ $code }}" {{ in_array($code, (array)$selectedLocations) ? 'selected' : '' }}>
                        {{ $name }}
                    </option>
                @endforeach
            </select>
        </div>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('campaigns.wizard.targeting.locations_help') }}
        </p>
    </div>

    {{-- Interests (Tags Input) --}}
    <div>
        <label for="interests" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.interests') }}
        </label>
        <div class="mt-1">
            <input type="text" name="interests_text" id="interests"
                   value="{{ old('interests_text', implode(', ', $session['data']['interests'] ?? [])) }}"
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   placeholder="{{ __('campaigns.wizard.targeting.interests_placeholder') }}">
        </div>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('campaigns.wizard.targeting.interests_help') }}
        </p>
    </div>

    {{-- Estimated Reach (Display only) --}}
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h4 class="text-sm font-medium text-blue-900">{{ __('campaigns.estimated_reach') }}</h4>
                <p class="text-sm text-blue-700">
                    {{ __('campaigns.wizard.targeting.reach_estimate', ['min' => '500K', 'max' => '2M']) }}
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Update audience type tracking for conditional fields
document.querySelectorAll('input[name="audience_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelector('[x-data]').__x.$data.audienceType = this.value;
    });
});

// Convert interests text to array on form submit
document.querySelector('form').addEventListener('submit', function(e) {
    const interestsText = document.getElementById('interests').value;
    if (interestsText) {
        const interests = interestsText.split(',').map(i => i.trim()).filter(i => i);
        interests.forEach(interest => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'interests[]';
            input.value = interest;
            this.appendChild(input);
        });
    }
});
</script>
@endpush
