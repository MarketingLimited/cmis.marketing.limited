@extends('layouts.admin')

@section('title', __('Create Ad Set') . ' - ' . $campaign->name)

@section('content')
<div class="space-y-6" x-data="adSetForm()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ $campaign->name }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ __('Ad Sets') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Create') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Create Ad Set</h1>
        <p class="mt-1 text-sm text-gray-500">
            Campaign: <span class="font-medium">{{ $campaign->name }}</span>
            @if($campaign->platform)
                <span class="ml-2 px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ ucfirst($campaign->platform) }}
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

    <form action="{{ route('org.campaigns.ad-sets.store', [$currentOrg, $campaign->campaign_id]) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Basic Information</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Ad Set Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Paused</option>
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
                        <input type="number" name="daily_budget" id="daily_budget" value="{{ old('daily_budget') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div x-show="budgetType === 'lifetime'">
                        <label for="lifetime_budget" class="block text-sm font-medium text-gray-700">Lifetime Budget ($)</label>
                        <input type="number" name="lifetime_budget" id="lifetime_budget" value="{{ old('lifetime_budget') }}" step="0.01" min="0"
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
                        <input type="number" name="bid_amount" id="bid_amount" value="{{ old('bid_amount') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="billing_event" class="block text-sm font-medium text-gray-700">Billing Event</label>
                        <select name="billing_event" id="billing_event"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="IMPRESSIONS" {{ old('billing_event') === 'IMPRESSIONS' ? 'selected' : '' }}>Impressions</option>
                            <option value="LINK_CLICKS" {{ old('billing_event') === 'LINK_CLICKS' ? 'selected' : '' }}>Link Clicks</option>
                            <option value="APP_INSTALLS" {{ old('billing_event') === 'APP_INSTALLS' ? 'selected' : '' }}>App Installs</option>
                            <option value="VIDEO_VIEWS" {{ old('billing_event') === 'VIDEO_VIEWS' ? 'selected' : '' }}>Video Views</option>
                            <option value="THRUPLAY" {{ old('billing_event') === 'THRUPLAY' ? 'selected' : '' }}>ThruPlay</option>
                        </select>
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
                        <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700">End Date & Time</label>
                        <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">Leave empty for no end date</p>
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
                            <option value="LINK_CLICKS" {{ old('optimization_goal') === 'LINK_CLICKS' ? 'selected' : '' }}>Link Clicks</option>
                            <option value="LANDING_PAGE_VIEWS" {{ old('optimization_goal') === 'LANDING_PAGE_VIEWS' ? 'selected' : '' }}>Landing Page Views</option>
                            <option value="IMPRESSIONS" {{ old('optimization_goal') === 'IMPRESSIONS' ? 'selected' : '' }}>Impressions</option>
                            <option value="REACH" {{ old('optimization_goal') === 'REACH' ? 'selected' : '' }}>Reach</option>
                            <option value="CONVERSIONS" {{ old('optimization_goal') === 'CONVERSIONS' ? 'selected' : '' }}>Conversions</option>
                            <option value="VIDEO_VIEWS" {{ old('optimization_goal') === 'VIDEO_VIEWS' ? 'selected' : '' }}>Video Views</option>
                            <option value="POST_ENGAGEMENT" {{ old('optimization_goal') === 'POST_ENGAGEMENT' ? 'selected' : '' }}>Post Engagement</option>
                            <option value="APP_INSTALLS" {{ old('optimization_goal') === 'APP_INSTALLS' ? 'selected' : '' }}>App Installs</option>
                            <option value="LEAD_GENERATION" {{ old('optimization_goal') === 'LEAD_GENERATION' ? 'selected' : '' }}>Lead Generation</option>
                        </select>
                    </div>
                    <div>
                        <label for="conversion_event" class="block text-sm font-medium text-gray-700">Conversion Event</label>
                        <select name="conversion_event" id="conversion_event"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            <option value="">Select conversion event</option>
                            <option value="Purchase" {{ old('conversion_event') === 'Purchase' ? 'selected' : '' }}>Purchase</option>
                            <option value="Lead" {{ old('conversion_event') === 'Lead' ? 'selected' : '' }}>Lead</option>
                            <option value="CompleteRegistration" {{ old('conversion_event') === 'CompleteRegistration' ? 'selected' : '' }}>Complete Registration</option>
                            <option value="AddToCart" {{ old('conversion_event') === 'AddToCart' ? 'selected' : '' }}>Add to Cart</option>
                            <option value="InitiateCheckout" {{ old('conversion_event') === 'InitiateCheckout' ? 'selected' : '' }}>Initiate Checkout</option>
                            <option value="Subscribe" {{ old('conversion_event') === 'Subscribe' ? 'selected' : '' }}>Subscribe</option>
                            <option value="Contact" {{ old('conversion_event') === 'Contact' ? 'selected' : '' }}>Contact</option>
                        </select>
                    </div>
                    <div>
                        <label for="pixel_id" class="block text-sm font-medium text-gray-700">Pixel ID</label>
                        <input type="text" name="pixel_id" id="pixel_id" value="{{ old('pixel_id') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>
            </div>
        </div>

        {{-- Demographics Targeting --}}
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
                                <input type="number" name="age_range[min]" id="age_min" value="{{ old('age_range.min', 18) }}" min="13" max="65"
                                       class="block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Min">
                            </div>
                            <span class="text-gray-500">to</span>
                            <div>
                                <label for="age_max" class="sr-only">Max Age</label>
                                <input type="number" name="age_range[max]" id="age_max" value="{{ old('age_range.max', 65) }}" min="13" max="65"
                                       class="block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="Max">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="genders[]" value="male" {{ in_array('male', old('genders', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Male</span>
                            </label>
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="genders[]" value="female" {{ in_array('female', old('genders', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">Female</span>
                            </label>
                        </div>
                    </div>

                    @if(isset($audienceOptions['demographics']['relationship_statuses']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Relationship Status</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['relationship_statuses'] as $status)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="relationship_statuses[]" value="{{ $status['id'] }}"
                                       {{ in_array($status['id'], old('relationship_statuses', [])) ? 'checked' : '' }}
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
                            @foreach($audienceOptions['demographics']['education_levels'] as $level)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="education_levels[]" value="{{ $level['id'] }}"
                                       {{ in_array($level['id'], old('education_levels', [])) ? 'checked' : '' }}
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
                            @foreach($audienceOptions['demographics']['parental_status'] as $status)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="parental_statuses[]" value="{{ $status['id'] }}"
                                       {{ in_array($status['id'], old('parental_statuses', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $status['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['household_income']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Household Income</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['household_income'] as $income)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="household_incomes[]" value="{{ $income['id'] }}"
                                       {{ in_array($income['id'], old('household_incomes', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $income['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- LinkedIn-specific demographics --}}
                    @if(isset($audienceOptions['demographics']['seniority']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Job Seniority</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['seniority'] as $level)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="job_seniorities[]" value="{{ $level['id'] }}"
                                       {{ in_array($level['id'], old('job_seniorities', [])) ? 'checked' : '' }}
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
                            @foreach($audienceOptions['demographics']['company_sizes'] as $size)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="company_sizes[]" value="{{ $size['id'] }}"
                                       {{ in_array($size['id'], old('company_sizes', [])) ? 'checked' : '' }}
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
                            @foreach($audienceOptions['demographics']['industries'] as $industry)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="industries[]" value="{{ $industry['id'] }}"
                                       {{ in_array($industry['id'], old('industries', [])) ? 'checked' : '' }}
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
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], old('custom_audiences', [])) ? 'selected' : '' }}>
                                        {{ $audience['name'] }} @if($audience['size'] ?? null)({{ number_format($audience['size']) }})@endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Connect ad account to load audiences</option>
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Hold Ctrl/Cmd to select multiple</p>
                    </div>
                    <div>
                        <label for="lookalike_audiences" class="block text-sm font-medium text-gray-700 mb-2">Lookalike Audiences</label>
                        <p class="text-xs text-gray-500 mb-2">Find new people similar to your best customers</p>
                        <select name="lookalike_audiences[]" id="lookalike_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                x-data x-init="$el.setAttribute('size', '4')">
                            @if(!empty($audienceOptions['lookalike_audiences']))
                                @foreach($audienceOptions['lookalike_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], old('lookalike_audiences', [])) ? 'selected' : '' }}>
                                        {{ $audience['name'] }} @if($audience['size'] ?? null)({{ number_format($audience['size']) }})@endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>Connect ad account to load audiences</option>
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Hold Ctrl/Cmd to select multiple</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="excluded_audiences" class="block text-sm font-medium text-gray-700 mb-2">Excluded Audiences</label>
                        <p class="text-xs text-gray-500 mb-2">People to exclude from your targeting</p>
                        <select name="excluded_audiences[]" id="excluded_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                x-data x-init="$el.setAttribute('size', '3')">
                            @if(!empty($audienceOptions['custom_audiences']))
                                @foreach($audienceOptions['custom_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], old('excluded_audiences', [])) ? 'selected' : '' }}>
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
                    <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
                           :class="{ 'border-blue-500 bg-blue-50': selectedInterests.includes('{{ $interest['id'] }}') }"
                           x-data="{ checked: {{ in_array($interest['id'], old('interests', [])) ? 'true' : 'false' }} }">
                        <input type="checkbox" name="interests[]" value="{{ $interest['id'] }}"
                               {{ in_array($interest['id'], old('interests', [])) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                               @change="checked = $el.checked">
                        <span class="ml-2 text-sm text-gray-700">{{ $interest['name'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label for="interest_search" class="block text-sm font-medium text-gray-700 mb-2">Search for more interests</label>
                    <input type="text" id="interest_search" placeholder="Type to search interests..."
                           class="block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    <p class="mt-1 text-xs text-gray-400">Additional interests can be loaded from the platform API</p>
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
                <p class="text-sm text-gray-500 mb-4">
                    @if($audienceOptions['platform'] === 'google')
                        Reach people actively researching or planning to purchase products/services
                    @else
                        Target people based on their purchase behaviors and activities
                    @endif
                </p>
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
                                   {{ in_array($behavior['id'], old('behaviors', [])) ? 'checked' : '' }}
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

        {{-- Placements --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Placements</h3>
                <div class="space-y-4">
                    <div>
                        <label class="inline-flex items-center">
                            <input type="checkbox" name="automatic_placements" value="1" x-model="automaticPlacements"
                                   {{ old('automatic_placements', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">Advantage+ Placements (Recommended)</span>
                        </label>
                        <p class="ml-6 text-xs text-gray-500">Let the platform optimize your placements for best results.</p>
                    </div>
                    <div x-show="!automaticPlacements" class="mt-4 pl-6 space-y-2">
                        @php
                            $placements = [
                                'feed' => 'Feed',
                                'stories' => 'Stories',
                                'reels' => 'Reels',
                                'right_column' => 'Right Column',
                                'in_stream' => 'In-Stream Videos',
                                'search' => 'Search Results',
                                'marketplace' => 'Marketplace',
                                'messenger' => 'Messenger',
                            ];
                        @endphp
                        @foreach($placements as $key => $label)
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="placements[]" value="{{ $key }}"
                                       {{ in_array($key, old('placements', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Device Targeting --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">Device Targeting</h3>
                <div class="space-y-2">
                    @php
                        $devices = [
                            'mobile' => 'Mobile',
                            'desktop' => 'Desktop',
                            'tablet' => 'Tablet',
                        ];
                    @endphp
                    @foreach($devices as $key => $label)
                        <label class="inline-flex items-center mr-4">
                            <input type="checkbox" name="device_platforms[]" value="{{ $key }}"
                                   {{ in_array($key, old('device_platforms', ['mobile', 'desktop', 'tablet'])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('org.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}"
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Create Ad Set
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function adSetForm() {
    return {
        budgetType: '{{ old('budget_type', 'daily') }}',
        bidStrategy: '{{ old('bid_strategy', 'lowest_cost') }}',
        automaticPlacements: {{ old('automatic_placements', true) ? 'true' : 'false' }},
    }
}
</script>
@endpush
@endsection
