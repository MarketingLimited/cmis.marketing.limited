@props([
    'name' => 'timezone',
    'value' => 'UTC',
    'required' => false,
    'label' => null,
    'helpText' => null,
    'showInheritance' => false,
    'inheritedFrom' => null,
    'inheritedValue' => null,
])

@php
    $timezones = [
        'UTC' => [
            'UTC' => 'UTC',
        ],
        'Asia' => [
            'Asia/Dubai' => 'Dubai (UAE)',
            'Asia/Riyadh' => 'Riyadh (Saudi Arabia)',
            'Asia/Kuwait' => 'Kuwait',
            'Asia/Bahrain' => 'Bahrain',
            'Asia/Qatar' => 'Qatar',
            'Asia/Muscat' => 'Muscat (Oman)',
            'Asia/Baghdad' => 'Baghdad (Iraq)',
            'Asia/Beirut' => 'Beirut (Lebanon)',
            'Asia/Amman' => 'Amman (Jordan)',
            'Asia/Damascus' => 'Damascus (Syria)',
            'Asia/Jerusalem' => 'Jerusalem',
            'Asia/Tokyo' => 'Tokyo',
            'Asia/Hong_Kong' => 'Hong Kong',
            'Asia/Singapore' => 'Singapore',
            'Asia/Shanghai' => 'Shanghai',
            'Asia/Kolkata' => 'Kolkata (India)',
            'Asia/Karachi' => 'Karachi (Pakistan)',
        ],
        'Africa' => [
            'Africa/Cairo' => 'Cairo (Egypt)',
            'Africa/Casablanca' => 'Casablanca (Morocco)',
            'Africa/Tunis' => 'Tunis (Tunisia)',
            'Africa/Algiers' => 'Algiers (Algeria)',
            'Africa/Tripoli' => 'Tripoli (Libya)',
            'Africa/Khartoum' => 'Khartoum (Sudan)',
            'Africa/Nairobi' => 'Nairobi (Kenya)',
            'Africa/Lagos' => 'Lagos (Nigeria)',
            'Africa/Johannesburg' => 'Johannesburg (South Africa)',
        ],
        'Europe' => [
            'Europe/London' => 'London (UK)',
            'Europe/Paris' => 'Paris (France)',
            'Europe/Berlin' => 'Berlin (Germany)',
            'Europe/Rome' => 'Rome (Italy)',
            'Europe/Madrid' => 'Madrid (Spain)',
            'Europe/Amsterdam' => 'Amsterdam (Netherlands)',
            'Europe/Brussels' => 'Brussels (Belgium)',
            'Europe/Vienna' => 'Vienna (Austria)',
            'Europe/Stockholm' => 'Stockholm (Sweden)',
            'Europe/Moscow' => 'Moscow (Russia)',
            'Europe/Istanbul' => 'Istanbul (Turkey)',
        ],
        'America' => [
            'America/New_York' => 'New York (US Eastern)',
            'America/Chicago' => 'Chicago (US Central)',
            'America/Denver' => 'Denver (US Mountain)',
            'America/Los_Angeles' => 'Los Angeles (US Pacific)',
            'America/Toronto' => 'Toronto (Canada)',
            'America/Mexico_City' => 'Mexico City',
            'America/Sao_Paulo' => 'São Paulo (Brazil)',
            'America/Buenos_Aires' => 'Buenos Aires (Argentina)',
        ],
        'Pacific' => [
            'Pacific/Auckland' => 'Auckland (New Zealand)',
            'Pacific/Sydney' => 'Sydney (Australia)',
            'Pacific/Fiji' => 'Fiji',
        ],
    ];
@endphp

<div class="mb-4">
    @if($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    @if($showInheritance && $inheritedFrom && $inheritedValue)
        <div class="mb-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-200">
                <i class="fas fa-info-circle me-1"></i>
                {{ __('common.inherits_from') }} <strong>{{ $inheritedFrom }}</strong>: {{ $inheritedValue }}
            </p>
            <p class="text-xs text-blue-600 dark:text-blue-300 mt-1">
                {{ __('common.leave_empty_to_inherit') }}
            </p>
        </div>
    @endif

    <select
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100']) }}
    >
        @if(!$required && $showInheritance)
            <option value="">{{ __('common.inherit_from_parent') }}</option>
        @endif

        @foreach($timezones as $region => $zoneList)
            <optgroup label="{{ $region }}">
                @foreach($zoneList as $zone => $label)
                    <option value="{{ $zone }}" {{ $value === $zone ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </optgroup>
        @endforeach
    </select>

    @if($helpText)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $helpText }}
        </p>
    @endif
</div>
