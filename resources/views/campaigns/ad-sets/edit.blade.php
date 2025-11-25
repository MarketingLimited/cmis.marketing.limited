@extends('layouts.app')

@section('title', __('Edit Ad Set') . ' - ' . $adSet->name)

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="adSetForm()">
    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li>
                <a href="{{ route('org.campaigns.index', $currentOrg) }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-bullhorn mr-1"></i> Campaigns
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('org.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="text-gray-500 hover:text-gray-700">
                        {{ $campaign->name }}
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <a href="{{ route('org.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}" class="text-gray-500 hover:text-gray-700">
                        Ad Sets
                    </a>
                </div>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700 font-medium">Edit: {{ $adSet->name }}</span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Edit Ad Set</h1>
        <p class="mt-1 text-sm text-gray-500">
            Campaign: <span class="font-medium">{{ $campaign->name }}</span>
            @if($adSet->external_ad_set_id)
                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                    <i class="fas fa-link"></i> Synced
                </span>
            @endif
        </p>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <form action="{{ route('org.campaigns.ad-sets.update', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Basic Information --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ad Set Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $adSet->name) }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('description', $adSet->description) }}</textarea>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="draft" {{ old('status', $adSet->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status', $adSet->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ old('status', $adSet->status) === 'paused' ? 'selected' : '' }}>Paused</option>
                            <option value="completed" {{ old('status', $adSet->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="archived" {{ old('status', $adSet->status) === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget & Bidding --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Budget & Bidding</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="budget_type" class="block text-sm font-medium text-gray-700">Budget Type</label>
                        <select name="budget_type" id="budget_type" x-model="budgetType"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="daily">Daily Budget</option>
                            <option value="lifetime">Lifetime Budget</option>
                        </select>
                    </div>
                    <div x-show="budgetType === 'daily'">
                        <label for="daily_budget" class="block text-sm font-medium text-gray-700">Daily Budget ($)</label>
                        <input type="number" name="daily_budget" id="daily_budget" value="{{ old('daily_budget', $adSet->daily_budget) }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div x-show="budgetType === 'lifetime'">
                        <label for="lifetime_budget" class="block text-sm font-medium text-gray-700">Lifetime Budget ($)</label>
                        <input type="number" name="lifetime_budget" id="lifetime_budget" value="{{ old('lifetime_budget', $adSet->lifetime_budget) }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="bid_strategy" class="block text-sm font-medium text-gray-700">Bid Strategy</label>
                        <select name="bid_strategy" id="bid_strategy" x-model="bidStrategy"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="lowest_cost">Lowest Cost (Auto)</option>
                            <option value="cost_cap">Cost Cap</option>
                            <option value="bid_cap">Bid Cap</option>
                            <option value="target_cost">Target Cost</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div x-show="bidStrategy !== 'lowest_cost'">
                        <label for="bid_amount" class="block text-sm font-medium text-gray-700">Bid Amount ($)</label>
                        <input type="number" name="bid_amount" id="bid_amount" value="{{ old('bid_amount', $adSet->bid_amount) }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Schedule</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700">Start Date & Time</label>
                        <input type="datetime-local" name="start_time" id="start_time"
                               value="{{ old('start_time', $adSet->start_time ? $adSet->start_time->format('Y-m-d\TH:i') : '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">End Date & Time</label>
                        <input type="datetime-local" name="end_time" id="end_time"
                               value="{{ old('end_time', $adSet->end_time ? $adSet->end_time->format('Y-m-d\TH:i') : '') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Optimization --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Optimization</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="optimization_goal" class="block text-sm font-medium text-gray-700">Optimization Goal</label>
                        <select name="optimization_goal" id="optimization_goal"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select optimization goal</option>
                            @foreach(['LINK_CLICKS', 'LANDING_PAGE_VIEWS', 'IMPRESSIONS', 'REACH', 'CONVERSIONS', 'VIDEO_VIEWS', 'POST_ENGAGEMENT', 'APP_INSTALLS', 'LEAD_GENERATION'] as $goal)
                                <option value="{{ $goal }}" {{ old('optimization_goal', $adSet->optimization_goal) === $goal ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', ucwords(strtolower($goal), '_')) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="conversion_event" class="block text-sm font-medium text-gray-700">Conversion Event</label>
                        <select name="conversion_event" id="conversion_event"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select conversion event</option>
                            @foreach(['Purchase', 'Lead', 'CompleteRegistration', 'AddToCart', 'InitiateCheckout', 'Subscribe', 'Contact'] as $event)
                                <option value="{{ $event }}" {{ old('conversion_event', $adSet->conversion_event) === $event ? 'selected' : '' }}>
                                    {{ $event }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="pixel_id" class="block text-sm font-medium text-gray-700">Pixel ID</label>
                        <input type="text" name="pixel_id" id="pixel_id" value="{{ old('pixel_id', $adSet->pixel_id) }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Demographics Targeting --}}
        @php
            $savedAgeRange = old('age_range', $adSet->age_range ?? ['min' => 18, 'max' => 65]);
            $savedGenders = old('genders', $adSet->genders ?? []);
            $savedInterests = old('interests', $adSet->interests ?? []);
            $savedBehaviors = old('behaviors', $adSet->behaviors ?? []);
            $savedCustomAudiences = old('custom_audiences', $adSet->custom_audiences ?? []);
            $savedLookalikeAudiences = old('lookalike_audiences', $adSet->lookalike_audiences ?? []);
            $savedExcludedAudiences = old('excluded_audiences', $adSet->excluded_audiences ?? []);
        @endphp
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                    <i class="fas fa-users mr-2 text-blue-500"></i>Demographics
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Age Range</label>
                        <div class="flex items-center space-x-4">
                            <div>
                                <label for="age_min" class="sr-only">Min Age</label>
                                <input type="number" name="age_range[min]" id="age_min" value="{{ $savedAgeRange['min'] ?? 18 }}" min="13" max="65"
                                       class="block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Min">
                            </div>
                            <span class="text-gray-500">to</span>
                            <div>
                                <label for="age_max" class="sr-only">Max Age</label>
                                <input type="number" name="age_range[max]" id="age_max" value="{{ $savedAgeRange['max'] ?? 65 }}" min="13" max="65"
                                       class="block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Max">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="genders[]" value="male" {{ in_array('male', $savedGenders) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Male</span>
                            </label>
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="genders[]" value="female" {{ in_array('female', $savedGenders) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Female</span>
                            </label>
                        </div>
                    </div>

                    @if(isset($audienceOptions['demographics']['relationship_statuses']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Relationship Status</label>
                        <div class="space-y-1">
                            @php $savedRelStatuses = old('relationship_statuses', $adSet->relationship_statuses ?? []); @endphp
                            @foreach($audienceOptions['demographics']['relationship_statuses'] as $status)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="relationship_statuses[]" value="{{ $status['id'] }}"
                                       {{ in_array($status['id'], $savedRelStatuses) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $status['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['education_levels']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Education Level</label>
                        <div class="space-y-1">
                            @php $savedEdLevels = old('education_levels', $adSet->education_levels ?? []); @endphp
                            @foreach($audienceOptions['demographics']['education_levels'] as $level)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="education_levels[]" value="{{ $level['id'] }}"
                                       {{ in_array($level['id'], $savedEdLevels) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $level['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['parental_status']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Parental Status</label>
                        <div class="space-y-1">
                            @php $savedParentalStatus = old('parental_statuses', $adSet->parental_statuses ?? []); @endphp
                            @foreach($audienceOptions['demographics']['parental_status'] as $status)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="parental_statuses[]" value="{{ $status['id'] }}"
                                       {{ in_array($status['id'], $savedParentalStatus) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $status['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['seniority']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Seniority</label>
                        <div class="space-y-1">
                            @php $savedSeniorities = old('job_seniorities', $adSet->job_seniorities ?? []); @endphp
                            @foreach($audienceOptions['demographics']['seniority'] as $level)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="job_seniorities[]" value="{{ $level['id'] }}"
                                       {{ in_array($level['id'], $savedSeniorities) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $level['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['company_sizes']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Company Size</label>
                        <div class="space-y-1">
                            @php $savedSizes = old('company_sizes', $adSet->company_sizes ?? []); @endphp
                            @foreach($audienceOptions['demographics']['company_sizes'] as $size)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="company_sizes[]" value="{{ $size['id'] }}"
                                       {{ in_array($size['id'], $savedSizes) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $size['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['industries']))
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Industries</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @php $savedIndustries = old('industries', $adSet->industries ?? []); @endphp
                            @foreach($audienceOptions['demographics']['industries'] as $industry)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="industries[]" value="{{ $industry['id'] }}"
                                       {{ in_array($industry['id'], $savedIndustries) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $industry['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Custom & Lookalike Audiences --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                    <i class="fas fa-user-friends mr-2 text-purple-500"></i>Custom & Lookalike Audiences
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="custom_audiences" class="block text-sm font-medium text-gray-700 mb-2">Custom Audiences</label>
                        <p class="text-xs text-gray-500 mb-2">Website visitors, customer lists, app users</p>
                        <select name="custom_audiences[]" id="custom_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                x-data x-init="$el.setAttribute('size', '4')">
                            @if(!empty($audienceOptions['custom_audiences']))
                                @foreach($audienceOptions['custom_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], $savedCustomAudiences) ? 'selected' : '' }}>
                                        {{ $audience['name'] }} @if($audience['size'] ?? null)({{ number_format($audience['size']) }})@endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Connect ad account to load audiences</option>
                            @endif
                        </select>
                    </div>
                    <div>
                        <label for="lookalike_audiences" class="block text-sm font-medium text-gray-700 mb-2">Lookalike Audiences</label>
                        <p class="text-xs text-gray-500 mb-2">Find new people similar to your best customers</p>
                        <select name="lookalike_audiences[]" id="lookalike_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                x-data x-init="$el.setAttribute('size', '4')">
                            @if(!empty($audienceOptions['lookalike_audiences']))
                                @foreach($audienceOptions['lookalike_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], $savedLookalikeAudiences) ? 'selected' : '' }}>
                                        {{ $audience['name'] }} @if($audience['size'] ?? null)({{ number_format($audience['size']) }})@endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Connect ad account to load audiences</option>
                            @endif
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label for="excluded_audiences" class="block text-sm font-medium text-gray-700 mb-2">Excluded Audiences</label>
                        <p class="text-xs text-gray-500 mb-2">People to exclude from your targeting</p>
                        <select name="excluded_audiences[]" id="excluded_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                x-data x-init="$el.setAttribute('size', '3')">
                            @if(!empty($audienceOptions['custom_audiences']))
                                @foreach($audienceOptions['custom_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], $savedExcludedAudiences) ? 'selected' : '' }}>
                                        {{ $audience['name'] }}
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Connect ad account to load audiences</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interest Targeting --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                    <i class="fas fa-heart mr-2 text-red-500"></i>Interest Targeting
                </h3>
                <p class="text-sm text-gray-500 mb-4">Reach people based on their interests, hobbies, and activities</p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach($audienceOptions['interests'] as $interest)
                    <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                        <input type="checkbox" name="interests[]" value="{{ $interest['id'] }}"
                               {{ in_array($interest['id'], $savedInterests) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">{{ $interest['name'] }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Behavior & In-Market Targeting --}}
        @if(!empty($audienceOptions['behaviors']))
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
                    <i class="fas fa-shopping-cart mr-2 text-green-500"></i>
                    @if($audienceOptions['platform'] === 'google')
                        In-Market Audiences
                    @else
                        Behavior Targeting
                    @endif
                </h3>
                @php
                    $behaviorsByCategory = collect($audienceOptions['behaviors'])->groupBy('category');
                @endphp
                @foreach($behaviorsByCategory as $category => $behaviors)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2">{{ $category }}</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($behaviors as $behavior)
                        <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors">
                            <input type="checkbox" name="behaviors[]" value="{{ $behavior['id'] }}"
                                   {{ in_array($behavior['id'], $savedBehaviors) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ $behavior['name'] }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="flex justify-between">
            <form action="{{ route('org.campaigns.ad-sets.destroy', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                  method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this ad set?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-4 py-2 text-sm font-medium text-red-600 hover:text-red-700">
                    <i class="fas fa-trash mr-1"></i> Delete Ad Set
                </button>
            </form>
            <div class="flex space-x-3">
                <a href="{{ route('org.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}"
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </a>
                <button type="submit"
                        class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Changes
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function adSetForm() {
    return {
        budgetType: '{{ old('budget_type', $adSet->budget_type ?? 'daily') }}',
        bidStrategy: '{{ old('bid_strategy', $adSet->bid_strategy ?? 'lowest_cost') }}',
    }
}
</script>
@endpush
@endsection
