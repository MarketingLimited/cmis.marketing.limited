@extends('layouts.admin')

@section('title', $profile->effective_name . ' - ' . __('profiles.title'))

@section('content')
<div class="space-y-6" x-data="profileDetail()">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.profiles.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('profiles.title') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $profile->effective_name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $profile->effective_name }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('profiles.configure_subtitle') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showImageUpload = true"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-image me-2"></i>
                {{ __('profiles.update_image') }}
            </button>
            {{-- Remove button disabled: profiles should be removed via Platform Connections assets page --}}
        </div>
    </div>

    {{-- Profile Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-start gap-6">
            {{-- Avatar and basic info --}}
            <div class="flex items-center gap-4">
                <div class="relative">
                    @if($profile->avatar_url)
                        <img class="h-20 w-20 rounded-full object-cover border-4 border-white shadow-lg"
                             src="{{ $profile->avatar_url }}"
                             alt="{{ $profile->effective_name }}">
                    @else
                        <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center border-4 border-white shadow-lg">
                            <i class="fas fa-user text-gray-400 text-2xl"></i>
                        </div>
                    @endif
                    {{-- Platform badge --}}
                    <span class="absolute bottom-0 end-0 transform translate-x-1 translate-y-1">
                        @include('components.platform-icon', ['platform' => $profile->platform, 'size' => 'lg'])
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $profile->effective_name }}</h2>
                    @if($profile->username)
                        <p class="text-sm text-gray-500">{{ '@' . $profile->username }}</p>
                    @endif
                    @if($profile->bio)
                        <p class="text-sm text-gray-600 mt-1">{{ $profile->bio }}</p>
                    @endif
                </div>
            </div>

            {{-- Stats grid --}}
            <div class="flex-1 grid grid-cols-2 md:grid-cols-5 gap-4 md:gap-6">
                {{-- Platform --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.platform') }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        @include('components.platform-icon', ['platform' => $profile->platform, 'size' => 'sm'])
                        <span class="text-sm font-medium text-gray-900 capitalize">{{ $profile->platform }}</span>
                    </div>
                </div>

                {{-- Status --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.status') }}</p>
                    @php $statusLabel = $profile->status_label; @endphp
                    <span class="inline-flex items-center mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $statusLabel === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $statusLabel === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $statusLabel === 'error' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ __('profiles.status_' . $statusLabel) }}
                    </span>
                </div>

                {{-- Type --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.type') }}</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">
                        {{ __('profiles.type_' . ($profile->profile_type ?? 'business')) }}
                    </p>
                </div>

                {{-- Connected --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.connected') }}</p>
                    <p class="mt-1 text-sm font-medium text-blue-600">
                        {{ $profile->created_at?->format('M d, Y') ?? '—' }}
                    </p>
                </div>

                {{-- Team member --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.team_member') }}</p>
                    <p class="mt-1 text-sm font-medium text-blue-600">
                        {{ $profile->creator?->name ?? $profile->connectedByUser?->name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Industry (editable) --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center gap-2">
                <p class="text-sm text-gray-500">{{ __('profiles.industry') }}</p>
                <button @click="editingIndustry = true" class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-pencil-alt text-xs"></i>
                </button>
            </div>
            <div x-show="!editingIndustry" class="mt-1">
                <p class="text-sm font-medium {{ $profile->industry ? 'text-blue-600' : 'text-gray-400' }}">
                    {{ $industries[$profile->industry] ?? __('profiles.not_set') }}
                </p>
            </div>
            <div x-show="editingIndustry" x-cloak class="mt-1 flex items-center gap-2">
                <select x-model="industryValue"
                        class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">{{ __('profiles.not_set') }}</option>
                    @foreach($industries as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button @click="saveIndustry()"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    {{ __('profiles.save') }}
                </button>
                <button @click="editingIndustry = false; industryValue = '{{ $profile->industry ?? '' }}'"
                        class="px-3 py-1.5 border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                    {{ __('profiles.cancel') }}
                </button>
            </div>
        </div>

        {{-- Timezone (editable with inheritance) --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center gap-2">
                <p class="text-sm text-gray-500">{{ __('profiles.timezone') }}</p>
                <button @click="editingTimezone = true" class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-pencil-alt text-xs"></i>
                </button>
            </div>

            {{-- Display mode --}}
            <div x-show="!editingTimezone" class="mt-1">
                @if($profile->timezone)
                    <p class="text-sm font-medium text-blue-600">{{ $profile->timezone }}</p>
                @else
                    <p class="text-sm font-medium text-gray-400">{{ __('profiles.inherited_timezone') }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ __('common.inherits_from') }}:
                        @if($profile->profileGroup && $profile->profileGroup->timezone)
                            {{ $profile->profileGroup->name }} ({{ $profile->profileGroup->timezone }})
                        @else
                            {{ $organization->name ?? __('Organization') }} ({{ $organization->timezone ?? 'UTC' }})
                        @endif
                    </p>
                @endif
            </div>

            {{-- Edit mode --}}
            <div x-show="editingTimezone" x-cloak class="mt-2">
                {{-- Inheritance info banner --}}
                <div class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle me-1"></i>
                        {{ __('common.inherits_from') }} <strong>
                            @if($profile->profileGroup && $profile->profileGroup->timezone)
                                {{ $profile->profileGroup->name }}
                            @else
                                {{ $organization->name ?? __('Organization') }}
                            @endif
                        </strong>:
                        {{ $profile->profileGroup?->timezone ?? $organization->timezone ?? 'UTC' }}
                    </p>
                    <p class="text-xs text-blue-600 mt-1">{{ __('common.leave_empty_to_inherit') }}</p>
                </div>

                <select x-model="timezoneValue"
                        class="block w-full max-w-sm rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">{{ __('common.inherit_from_parent') }}</option>
                    <optgroup label="UTC">
                        <option value="UTC">UTC</option>
                    </optgroup>
                    <optgroup label="Asia">
                        <option value="Asia/Dubai">Dubai (UAE)</option>
                        <option value="Asia/Riyadh">Riyadh (Saudi Arabia)</option>
                        <option value="Asia/Kuwait">Kuwait</option>
                        <option value="Asia/Bahrain">Bahrain</option>
                        <option value="Asia/Qatar">Qatar</option>
                        <option value="Asia/Muscat">Muscat (Oman)</option>
                        <option value="Asia/Baghdad">Baghdad (Iraq)</option>
                        <option value="Asia/Beirut">Beirut (Lebanon)</option>
                        <option value="Asia/Amman">Amman (Jordan)</option>
                        <option value="Asia/Damascus">Damascus (Syria)</option>
                        <option value="Asia/Jerusalem">Jerusalem</option>
                        <option value="Asia/Tokyo">Tokyo</option>
                        <option value="Asia/Hong_Kong">Hong Kong</option>
                        <option value="Asia/Singapore">Singapore</option>
                        <option value="Asia/Shanghai">Shanghai</option>
                        <option value="Asia/Kolkata">Kolkata (India)</option>
                        <option value="Asia/Karachi">Karachi (Pakistan)</option>
                    </optgroup>
                    <optgroup label="Africa">
                        <option value="Africa/Cairo">Cairo (Egypt)</option>
                        <option value="Africa/Casablanca">Casablanca (Morocco)</option>
                        <option value="Africa/Tunis">Tunis (Tunisia)</option>
                        <option value="Africa/Algiers">Algiers (Algeria)</option>
                        <option value="Africa/Tripoli">Tripoli (Libya)</option>
                        <option value="Africa/Khartoum">Khartoum (Sudan)</option>
                        <option value="Africa/Nairobi">Nairobi (Kenya)</option>
                        <option value="Africa/Lagos">Lagos (Nigeria)</option>
                        <option value="Africa/Johannesburg">Johannesburg (South Africa)</option>
                    </optgroup>
                    <optgroup label="Europe">
                        <option value="Europe/London">London (UK)</option>
                        <option value="Europe/Paris">Paris (France)</option>
                        <option value="Europe/Berlin">Berlin (Germany)</option>
                        <option value="Europe/Rome">Rome (Italy)</option>
                        <option value="Europe/Madrid">Madrid (Spain)</option>
                        <option value="Europe/Amsterdam">Amsterdam (Netherlands)</option>
                        <option value="Europe/Brussels">Brussels (Belgium)</option>
                        <option value="Europe/Vienna">Vienna (Austria)</option>
                        <option value="Europe/Stockholm">Stockholm (Sweden)</option>
                        <option value="Europe/Moscow">Moscow (Russia)</option>
                        <option value="Europe/Istanbul">Istanbul (Turkey)</option>
                    </optgroup>
                    <optgroup label="America">
                        <option value="America/New_York">New York (US Eastern)</option>
                        <option value="America/Chicago">Chicago (US Central)</option>
                        <option value="America/Denver">Denver (US Mountain)</option>
                        <option value="America/Los_Angeles">Los Angeles (US Pacific)</option>
                        <option value="America/Toronto">Toronto (Canada)</option>
                        <option value="America/Mexico_City">Mexico City</option>
                        <option value="America/Sao_Paulo">São Paulo (Brazil)</option>
                        <option value="America/Buenos_Aires">Buenos Aires (Argentina)</option>
                    </optgroup>
                    <optgroup label="Pacific">
                        <option value="Pacific/Auckland">Auckland (New Zealand)</option>
                        <option value="Pacific/Sydney">Sydney (Australia)</option>
                        <option value="Pacific/Fiji">Fiji</option>
                    </optgroup>
                </select>
                <div class="flex items-center gap-2 mt-2">
                    <button @click="saveTimezone()"
                            class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                        {{ __('profiles.save') }}
                    </button>
                    <button @click="editingTimezone = false; timezoneValue = '{{ $profile->timezone ?? '' }}'"
                            class="px-3 py-1.5 border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                        {{ __('profiles.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Profile Groups Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-blue-600"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 uppercase tracking-wider">
                    {{ __('profiles.profile_groups') }}
                </h3>
            </div>
            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}"
               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                {{ __('profiles.manage_profile_groups') }}
            </a>
        </div>

        @if($profile->profileGroup)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold"
                         style="background-color: {{ $profile->profileGroup->color ?? '#3B82F6' }}">
                        {{ strtoupper(substr($profile->profileGroup->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $profile->profileGroup->name }}</p>
                        <p class="text-xs text-gray-500">
                            Client · {{ $profile->profileGroup->client_country ?? 'Not set' }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-layer-group text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-3">{{ __('profiles.no_groups_message') }}</p>
                <button @click="showGroupsModal = true"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    {{ __('profiles.manage_groups') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Publishing Queues Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-100 to-indigo-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-semibold text-gray-900 uppercase tracking-wider">
                        {{ __('profiles.publishing_queues') }}
                    </h3>
                    @if($queueSettings && $queueSettings->queue_enabled)
                        @php
                            $totalSlots = 0;
                            $schedule = $queueSettings->schedule ?? [];
                            foreach ($schedule as $dayTimes) {
                                $totalSlots += count($dayTimes);
                            }
                            $daysEnabled = $queueSettings->days_enabled ?? [];
                        @endphp
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ count($daysEnabled) }} {{ __('profiles.days_active') }} • {{ $totalSlots }} {{ __('profiles.time_slots') }}
                        </p>
                    @endif
                </div>
            </div>
            <button @click="showQueueModal = true"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                {{ __('profiles.queue_settings') }}
            </button>
        </div>

        @if($queueSettings && $queueSettings->queue_enabled)
            {{-- Queue Status Banner --}}
            <div class="mb-6 p-3 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-lg">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-green-900">{{ __('profiles.queue_enabled') }}</p>
                        <p class="text-xs text-green-700">{{ __('profiles.queue_enabled_description') }}</p>
                    </div>
                </div>
            </div>

            {{-- Days Schedule Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @php
                    $daysOfWeek = [
                        'monday' => __('common.monday'),
                        'tuesday' => __('common.tuesday'),
                        'wednesday' => __('common.wednesday'),
                        'thursday' => __('common.thursday'),
                        'friday' => __('common.friday'),
                        'saturday' => __('common.saturday'),
                        'sunday' => __('common.sunday'),
                    ];
                    $schedule = $queueSettings->schedule ?? [];
                    $daysEnabled = $queueSettings->days_enabled ?? [];
                @endphp

                @foreach($daysOfWeek as $dayKey => $dayName)
                    @php
                        $isEnabled = in_array($dayKey, $daysEnabled);
                        $times = $schedule[$dayKey] ?? [];
                    @endphp
                    <div class="border rounded-lg overflow-hidden transition-all hover:shadow-md
                                {{ $isEnabled ? 'border-blue-200 bg-gradient-to-br from-white to-blue-50' : 'border-gray-200 bg-gray-50' }}">
                        {{-- Day Header --}}
                        <div class="px-3 py-2 {{ $isEnabled ? 'bg-gradient-to-r from-blue-100 to-indigo-100 border-b border-blue-200' : 'bg-gray-100 border-b border-gray-200' }}">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold {{ $isEnabled ? 'text-gray-900' : 'text-gray-400' }}">
                                    {{ $dayName }}
                                </span>
                                <div class="flex items-center gap-2">
                                    @if($isEnabled && count($times) > 0)
                                        <span class="px-2 py-0.5 text-xs font-medium text-blue-700 bg-blue-100 rounded-full">
                                            {{ count($times) }}
                                        </span>
                                    @endif
                                    <span class="w-2 h-2 rounded-full {{ $isEnabled ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Time Slots --}}
                        <div class="px-3 py-2 min-h-[60px]">
                            @if($isEnabled && count($times) > 0)
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($times as $slot)
                                        @php
                                            // Handle both old format (string) and new format (object)
                                            $timeValue = is_array($slot) ? ($slot['time'] ?? null) : $slot;
                                            $labelId = is_array($slot) ? ($slot['label_id'] ?? null) : null;
                                            $isEvergreen = is_array($slot) ? ($slot['is_evergreen'] ?? false) : false;
                                            $label = $labelId ? $queueLabels->firstWhere('id', $labelId) : null;
                                        @endphp
                                        @if($timeValue)
                                            <div class="inline-flex items-center gap-1">
                                                {{-- Label badge if exists --}}
                                                @if($label)
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                                                          style="background: {{ $label->color_type === 'gradient' ? 'linear-gradient(135deg, ' . $label->gradient_start . ', ' . $label->gradient_end . ')' : $label->background_color }}; color: {{ $label->text_color }};">
                                                        {{ $label->name }}
                                                    </span>
                                                @endif
                                                {{-- Time badge --}}
                                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800 border border-blue-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    {{ \Carbon\Carbon::createFromFormat('H:i', $timeValue)->format('g:i A') }}
                                                    {{-- Evergreen indicator --}}
                                                    @if($isEvergreen)
                                                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20" title="{{ __('profiles.evergreen') }}">
                                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                                                        </svg>
                                                    @endif
                                                </span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @elseif($isEnabled)
                                <p class="text-xs text-gray-400 italic text-center py-2">{{ __('profiles.no_times_set') }}</p>
                            @else
                                <p class="text-xs text-gray-400 italic text-center py-2">{{ __('profiles.queue_disabled') }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <div class="w-16 h-16 mx-auto bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h4 class="text-base font-semibold text-gray-900 mb-2">{{ __('profiles.queue_disabled') }}</h4>
                <p class="text-sm text-gray-500 mb-4 max-w-md mx-auto">{{ __('profiles.queue_disabled_description') }}</p>
                <button @click="showQueueModal = true"
                        class="inline-flex items-center px-5 py-2.5 border-2 border-blue-300 rounded-lg shadow-sm text-sm font-medium text-blue-700 bg-white hover:bg-blue-50 transition-colors">
                    <svg class="w-5 h-5 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    {{ __('profiles.setup_queue') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Boost Settings Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-rocket text-orange-600"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 uppercase tracking-wider">
                    {{ __('profiles.boost_settings') }}
                </h3>
            </div>
            <button @click="showBoostModal = true; editingBoostId = null"
                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-plus me-1"></i>
                {{ __('profiles.add_boost') }}
            </button>
        </div>

        @if($boostRules->count() > 0)
            <div class="space-y-3">
                @foreach($boostRules as $boost)
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $boost->name }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ __('profiles.trigger_' . $boost->trigger_type) }}
                                    @if($boost->delay_after_publish)
                                        · {{ $boost->delay_after_publish['value'] ?? 0 }} {{ $boost->delay_after_publish['unit'] ?? 'hours' }}
                                    @endif
                                    @if($boost->budget_amount)
                                        · {{ number_format($boost->budget_amount, 2) }} {{ $boost->budget_currency ?? 'USD' }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $boost->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $boost->is_active ? __('profiles.status_active') : __('profiles.status_inactive') }}
                                </span>
                                <button @click="editBoost('{{ $boost->boost_rule_id }}')"
                                        class="p-1 text-gray-400 hover:text-blue-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="toggleBoost('{{ $boost->boost_rule_id }}')"
                                        class="p-1 text-gray-400 hover:text-yellow-600">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <button @click="deleteBoost('{{ $boost->boost_rule_id }}')"
                                        class="p-1 text-gray-400 hover:text-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-rocket text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-3">{{ __('profiles.no_boosts_message') }}</p>
                @if(!$profile->profile_group_id)
                    <p class="text-xs text-yellow-600 mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        {{ __('profiles.profile_must_be_in_group') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Boost Modal --}}
    @include('settings.profiles.partials._boost-modal')

    {{-- Manage Groups Modal --}}
    @include('settings.profiles.partials._groups-modal')

    {{-- Queue Settings Modal --}}
    @include('settings.profiles.partials._queue-modal')

    {{-- Label Management Modals --}}
    @include('settings.profiles.partials._manage-labels-modal')
    @include('settings.profiles.partials._label-editor-modal')
</div>

@push('scripts')
<script>
function profileDetail() {
    return {
        loading: false,
        editingIndustry: false,
        industryValue: '{{ $profile->industry ?? '' }}',
        editingTimezone: false,
        timezoneValue: '{{ $profile->timezone ?? '' }}',
        showBoostModal: false,
        showGroupsModal: false,
        showQueueModal: false,
        showImageUpload: false,
        editingBoostId: null,
        selectedGroupId: '{{ $profile->profile_group_id ?? '' }}',

        // Label management state
        queueLabels: @json($queueLabels ?? []),
        showManageLabelsModal: false,
        showLabelEditorModal: false,
        editingLabel: null,
        labelSearch: '',
        isSavingLabel: false,
        labelEditorData: {
            name: '',
            color_type: 'solid',
            background_color: '#3B82F6',
            text_color: '#FFFFFF',
            gradient_start: '#667EEA',
            gradient_end: '#764BA2'
        },

        // Color presets
        solidColors: [
            { name: 'Blue', value: '#3B82F6' },
            { name: 'Purple', value: '#8B5CF6' },
            { name: 'Pink', value: '#EC4899' },
            { name: 'Red', value: '#EF4444' },
            { name: 'Orange', value: '#F97316' },
            { name: 'Yellow', value: '#EAB308' },
            { name: 'Green', value: '#22C55E' },
            { name: 'Teal', value: '#14B8A6' },
            { name: 'Cyan', value: '#06B6D4' },
            { name: 'Indigo', value: '#6366F1' },
            { name: 'Gray', value: '#6B7280' },
            { name: 'Slate', value: '#64748B' },
            { name: 'Rose', value: '#F43F5E' },
            { name: 'Fuchsia', value: '#D946EF' },
            { name: 'Lime', value: '#84CC16' }
        ],
        gradientPresets: [
            { name: 'Purple Dream', start: '#667EEA', end: '#764BA2' },
            { name: 'Ocean Blue', start: '#2193B0', end: '#6DD5ED' },
            { name: 'Sunset', start: '#F093FB', end: '#F5576C' },
            { name: 'Fresh Mint', start: '#11998E', end: '#38EF7D' },
            { name: 'Fire', start: '#F12711', end: '#F5AF19' },
            { name: 'Royal', start: '#141E30', end: '#243B55' },
            { name: 'Pink Love', start: '#FF6B6B', end: '#FFC6C6' },
            { name: 'Sky', start: '#56CCF2', end: '#2F80ED' },
            { name: 'Forest', start: '#134E5E', end: '#71B280' },
            { name: 'Passion', start: '#E53935', end: '#E35D5B' }
        ],
        textColors: [
            { name: 'White', value: '#FFFFFF' },
            { name: 'Black', value: '#1F2937' },
            { name: 'Gray', value: '#6B7280' }
        ],

        async saveIndustry() {
            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ industry: this.industryValue })
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.profile_updated") }}', type: 'success' }
                    }));
                    this.editingIndustry = false;
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
            this.loading = false;
        },

        async saveTimezone() {
            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ timezone: this.timezoneValue || null })
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.timezone_updated") }}', type: 'success' }
                    }));
                    this.editingTimezone = false;
                    location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: data.message || '{{ __("common.error") }}', type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: '{{ __("common.error") }}', type: 'error' }
                }));
            }
            this.loading = false;
        },

        async editBoost(boostId) {
            this.editingBoostId = boostId;
            this.showBoostModal = true;
        },

        async toggleBoost(boostId) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boosts/${boostId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.boost_toggled") }}', type: 'success' }
                    }));
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async deleteBoost(boostId) {
            if (!confirm('{{ __("profiles.confirm_delete_boost") }}')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boosts/${boostId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.boost_deleted") }}', type: 'success' }
                    }));
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async assignToGroup() {
            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/groups`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ group_id: this.selectedGroupId })
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.group_assigned") }}', type: 'success' }
                    }));
                    this.showGroupsModal = false;
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
            this.loading = false;
        },

        async confirmRemove() {
            if (!confirm('{{ __("profiles.confirm_remove") }}')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.profile_removed") }}', type: 'success' }
                    }));
                    window.location.href = '{{ route("orgs.settings.profiles.index", $currentOrg) }}';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        // Label management computed properties
        get filteredLabels() {
            if (!this.labelSearch) return this.queueLabels;
            const search = this.labelSearch.toLowerCase();
            return this.queueLabels.filter(label =>
                label.name.toLowerCase().includes(search)
            );
        },

        // Label style helper
        getLabelStyle(labelId) {
            const label = this.queueLabels.find(l => l.id === labelId);
            if (!label) return {};
            if (label.color_type === 'gradient') {
                return {
                    background: `linear-gradient(135deg, ${label.gradient_start}, ${label.gradient_end})`,
                    color: label.text_color
                };
            }
            return {
                backgroundColor: label.background_color,
                color: label.text_color
            };
        },

        // Editor label style
        getEditorLabelStyle() {
            if (this.labelEditorData.color_type === 'gradient') {
                return {
                    background: `linear-gradient(135deg, ${this.labelEditorData.gradient_start}, ${this.labelEditorData.gradient_end})`,
                    color: this.labelEditorData.text_color
                };
            }
            return {
                backgroundColor: this.labelEditorData.background_color,
                color: this.labelEditorData.text_color
            };
        },

        // Open label editor
        openLabelEditor(label = null) {
            this.editingLabel = label;
            if (label) {
                this.labelEditorData = {
                    name: label.name,
                    color_type: label.color_type || 'solid',
                    background_color: label.background_color || '#3B82F6',
                    text_color: label.text_color || '#FFFFFF',
                    gradient_start: label.gradient_start || '#667EEA',
                    gradient_end: label.gradient_end || '#764BA2'
                };
            } else {
                this.labelEditorData = {
                    name: '',
                    color_type: 'solid',
                    background_color: '#3B82F6',
                    text_color: '#FFFFFF',
                    gradient_start: '#667EEA',
                    gradient_end: '#764BA2'
                };
            }
            this.showLabelEditorModal = true;
        },

        // Save label
        async saveLabel() {
            if (!this.labelEditorData.name) return;

            this.isSavingLabel = true;
            const url = this.editingLabel
                ? `/orgs/{{ $currentOrg }}/settings/queue-labels/${this.editingLabel.id}`
                : `/orgs/{{ $currentOrg }}/settings/queue-labels`;
            const method = this.editingLabel ? 'PATCH' : 'POST';

            try {
                const response = await fetch(url, {
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(this.labelEditorData)
                });
                const data = await response.json();
                if (data.success) {
                    if (this.editingLabel) {
                        const index = this.queueLabels.findIndex(l => l.id === this.editingLabel.id);
                        if (index !== -1) {
                            this.queueLabels[index] = data.data;
                        }
                    } else {
                        this.queueLabels.push(data.data);
                    }
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: {
                            message: this.editingLabel ? '{{ __("profiles.label_updated") }}' : '{{ __("profiles.label_created") }}',
                            type: 'success'
                        }
                    }));
                    this.showLabelEditorModal = false;
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: data.message || '{{ __("profiles.label_create_failed") }}', type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: '{{ __("profiles.label_create_failed") }}', type: 'error' }
                }));
            }
            this.isSavingLabel = false;
        },

        // Confirm delete label
        async confirmDeleteLabel(label) {
            if (!confirm('{{ __("profiles.confirm_delete_label") }}')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/queue-labels/${label.id}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.queueLabels = this.queueLabels.filter(l => l.id !== label.id);
                    this.showLabelEditorModal = false;
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.label_deleted") }}', type: 'success' }
                    }));
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: data.message || '{{ __("profiles.label_delete_failed") }}', type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: '{{ __("profiles.label_delete_failed") }}', type: 'error' }
                }));
            }
        }
    };
}
</script>
@endpush
@endsection
