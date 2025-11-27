@extends('layouts.admin')

@section('title', __('campaigns.create_ad_set') . ' - ' . $campaign->name)

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" x-data="adSetForm()" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('campaigns.campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.show', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ $campaign->name }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}" class="hover:text-blue-600 transition">{{ __('campaigns.ad_sets') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('campaigns.create') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="mb-6 {{ $isRtl ? 'text-right' : '' }}">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('campaigns.create_ad_set') }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ __('campaigns.campaign_label') }}: <span class="font-medium">{{ $campaign->name }}</span>
            @if($campaign->platform)
                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    {{ ucfirst($campaign->platform) }}
                </span>
            @endif
        </p>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="{{ $isRtl ? 'mr-3 text-right' : 'ml-3' }}">
                    <h3 class="text-sm font-medium text-red-800">{{ __('campaigns.fix_errors') }}</h3>
                    <ul class="mt-2 text-sm text-red-700 {{ $isRtl ? 'list-inside' : 'list-disc list-inside' }}">
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
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.basic_information') }}</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_set_name') }} *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                    </div>
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.description') }}</label>
                        <textarea name="description" id="description" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">{{ old('description') }}</textarea>
                    </div>
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.status') }}</label>
                        <select name="status" id="status"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <option value="draft" {{ old('status') === 'draft' ? 'selected' : '' }}>{{ __('campaigns.status.draft') }}</option>
                            <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>{{ __('campaigns.status.active') }}</option>
                            <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>{{ __('campaigns.status.paused') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Budget & Bidding --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.budget_bidding') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="budget_type" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.budget_type') }}</label>
                        <select name="budget_type" id="budget_type" x-model="budgetType"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <option value="daily">{{ __('campaigns.daily_budget') }}</option>
                            <option value="lifetime">{{ __('campaigns.lifetime_budget') }}</option>
                        </select>
                    </div>
                    <div x-show="budgetType === 'daily'">
                        <label for="daily_budget" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.daily_budget') }} ({{ $isRtl ? 'ر.س' : '$' }})</label>
                        <input type="number" name="daily_budget" id="daily_budget" value="{{ old('daily_budget') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" dir="ltr">
                    </div>
                    <div x-show="budgetType === 'lifetime'">
                        <label for="lifetime_budget" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.lifetime_budget') }} ({{ $isRtl ? 'ر.س' : '$' }})</label>
                        <input type="number" name="lifetime_budget" id="lifetime_budget" value="{{ old('lifetime_budget') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" dir="ltr">
                    </div>
                    <div>
                        <label for="bid_strategy" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.bid_strategy') }}</label>
                        <select name="bid_strategy" id="bid_strategy" x-model="bidStrategy"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <option value="lowest_cost">{{ __('campaigns.bid_strategies.lowest_cost') }}</option>
                            <option value="cost_cap">{{ __('campaigns.bid_strategies.cost_cap') }}</option>
                            <option value="bid_cap">{{ __('campaigns.bid_strategies.bid_cap') }}</option>
                            <option value="target_cost">{{ __('campaigns.bid_strategies.target_cost') }}</option>
                            <option value="manual">{{ __('campaigns.manual') }}</option>
                        </select>
                    </div>
                    <div x-show="bidStrategy !== 'lowest_cost'">
                        <label for="bid_amount" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.bid_amount') }} ({{ $isRtl ? 'ر.س' : '$' }})</label>
                        <input type="number" name="bid_amount" id="bid_amount" value="{{ old('bid_amount') }}" step="0.01" min="0"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" dir="ltr">
                    </div>
                    <div>
                        <label for="billing_event" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.billing_event') }}</label>
                        <select name="billing_event" id="billing_event"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <option value="IMPRESSIONS" {{ old('billing_event') === 'IMPRESSIONS' ? 'selected' : '' }}>{{ __('campaigns.metrics.impressions') }}</option>
                            <option value="LINK_CLICKS" {{ old('billing_event') === 'LINK_CLICKS' ? 'selected' : '' }}>{{ __('campaigns.billing_events.link_clicks') }}</option>
                            <option value="APP_INSTALLS" {{ old('billing_event') === 'APP_INSTALLS' ? 'selected' : '' }}>{{ __('campaigns.billing_events.app_installs') }}</option>
                            <option value="VIDEO_VIEWS" {{ old('billing_event') === 'VIDEO_VIEWS' ? 'selected' : '' }}>{{ __('campaigns.billing_events.video_views') }}</option>
                            <option value="THRUPLAY" {{ old('billing_event') === 'THRUPLAY' ? 'selected' : '' }}>{{ __('campaigns.billing_events.thruplay') }}</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.schedule') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="start_time" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.start_date_time') }}</label>
                        <input type="datetime-local" name="start_time" id="start_time" value="{{ old('start_time') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" dir="ltr">
                    </div>
                    <div>
                        <label for="end_time" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.end_date_time') }}</label>
                        <input type="datetime-local" name="end_time" id="end_time" value="{{ old('end_time') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" dir="ltr">
                        <p class="mt-1 text-xs text-gray-500 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.leave_empty_no_end_date') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Optimization --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.optimization') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="optimization_goal" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.optimization_goal') }}</label>
                        <select name="optimization_goal" id="optimization_goal"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <option value="">{{ __('campaigns.select_optimization_goal') }}</option>
                            <option value="LINK_CLICKS" {{ old('optimization_goal') === 'LINK_CLICKS' ? 'selected' : '' }}>{{ __('campaigns.optimization_goals.link_clicks') }}</option>
                            <option value="LANDING_PAGE_VIEWS" {{ old('optimization_goal') === 'LANDING_PAGE_VIEWS' ? 'selected' : '' }}>{{ __('campaigns.optimization_goals.landing_page_views') }}</option>
                            <option value="IMPRESSIONS" {{ old('optimization_goal') === 'IMPRESSIONS' ? 'selected' : '' }}>{{ __('campaigns.metrics.impressions') }}</option>
                            <option value="REACH" {{ old('optimization_goal') === 'REACH' ? 'selected' : '' }}>{{ __('campaigns.metrics.reach') }}</option>
                            <option value="CONVERSIONS" {{ old('optimization_goal') === 'CONVERSIONS' ? 'selected' : '' }}>{{ __('campaigns.metrics.conversions') }}</option>
                            <option value="VIDEO_VIEWS" {{ old('optimization_goal') === 'VIDEO_VIEWS' ? 'selected' : '' }}>{{ __('campaigns.optimization_goals.video_views') }}</option>
                            <option value="POST_ENGAGEMENT" {{ old('optimization_goal') === 'POST_ENGAGEMENT' ? 'selected' : '' }}>{{ __('campaigns.optimization_goals.post_engagement') }}</option>
                            <option value="APP_INSTALLS" {{ old('optimization_goal') === 'APP_INSTALLS' ? 'selected' : '' }}>{{ __('campaigns.objectives.app_installs') }}</option>
                            <option value="LEAD_GENERATION" {{ old('optimization_goal') === 'LEAD_GENERATION' ? 'selected' : '' }}>{{ __('campaigns.objectives.leads') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="conversion_event" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.conversion_event') }}</label>
                        <select name="conversion_event" id="conversion_event"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}">
                            <option value="">{{ __('campaigns.select_conversion_event') }}</option>
                            <option value="Purchase" {{ old('conversion_event') === 'Purchase' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.purchase') }}</option>
                            <option value="Lead" {{ old('conversion_event') === 'Lead' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.lead') }}</option>
                            <option value="CompleteRegistration" {{ old('conversion_event') === 'CompleteRegistration' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.complete_registration') }}</option>
                            <option value="AddToCart" {{ old('conversion_event') === 'AddToCart' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.add_to_cart') }}</option>
                            <option value="InitiateCheckout" {{ old('conversion_event') === 'InitiateCheckout' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.initiate_checkout') }}</option>
                            <option value="Subscribe" {{ old('conversion_event') === 'Subscribe' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.subscribe') }}</option>
                            <option value="Contact" {{ old('conversion_event') === 'Contact' ? 'selected' : '' }}>{{ __('campaigns.conversion_events.contact') }}</option>
                        </select>
                    </div>
                    <div>
                        <label for="pixel_id" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.pixel_id') }}</label>
                        <input type="text" name="pixel_id" id="pixel_id" value="{{ old('pixel_id') }}"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" dir="ltr">
                    </div>
                </div>
            </div>
        </div>

        {{-- Demographics Targeting --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right flex-row-reverse' : '' }} flex items-center">
                    <i class="fas fa-users {{ $isRtl ? 'ml-2' : 'mr-2' }} text-blue-500"></i>{{ __('campaigns.demographics') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.age_range') }}</label>
                        <div class="flex items-center {{ $isRtl ? 'space-x-reverse space-x-4' : 'space-x-4' }}">
                            <div>
                                <label for="age_min" class="sr-only">{{ __('campaigns.min_age') }}</label>
                                <input type="number" name="age_range[min]" id="age_min" value="{{ old('age_range.min', 18) }}" min="13" max="65"
                                       class="block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="{{ __('campaigns.min') }}" dir="ltr">
                            </div>
                            <span class="text-gray-500">{{ __('campaigns.to') }}</span>
                            <div>
                                <label for="age_max" class="sr-only">{{ __('campaigns.max_age') }}</label>
                                <input type="number" name="age_range[max]" id="age_max" value="{{ old('age_range.max', 65) }}" min="13" max="65"
                                       class="block w-20 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                       placeholder="{{ __('campaigns.max') }}" dir="ltr">
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.gender') }}</label>
                        <div class="space-y-2">
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="genders[]" value="male" {{ in_array('male', old('genders', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ __('campaigns.male') }}</span>
                            </label>
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="genders[]" value="female" {{ in_array('female', old('genders', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ __('campaigns.female') }}</span>
                            </label>
                        </div>
                    </div>

                    @if(isset($audienceOptions['demographics']['relationship_statuses']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.relationship_status') }}</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['relationship_statuses'] as $status)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="relationship_statuses[]" value="{{ $status['id'] }}"
                                       {{ in_array($status['id'], old('relationship_statuses', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $status['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['education_levels']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.education_level') }}</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['education_levels'] as $level)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="education_levels[]" value="{{ $level['id'] }}"
                                       {{ in_array($level['id'], old('education_levels', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $level['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['parental_status']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.parental_status') }}</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['parental_status'] as $status)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="parental_statuses[]" value="{{ $status['id'] }}"
                                       {{ in_array($status['id'], old('parental_statuses', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $status['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['household_income']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.household_income') }}</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['household_income'] as $income)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="household_incomes[]" value="{{ $income['id'] }}"
                                       {{ in_array($income['id'], old('household_incomes', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $income['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- LinkedIn-specific demographics --}}
                    @if(isset($audienceOptions['demographics']['seniority']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.job_seniority') }}</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['seniority'] as $level)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="job_seniorities[]" value="{{ $level['id'] }}"
                                       {{ in_array($level['id'], old('job_seniorities', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $level['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['company_sizes']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.company_size') }}</label>
                        <div class="space-y-1">
                            @foreach($audienceOptions['demographics']['company_sizes'] as $size)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                <input type="checkbox" name="company_sizes[]" value="{{ $size['id'] }}"
                                       {{ in_array($size['id'], old('company_sizes', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $size['name'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    @if(isset($audienceOptions['demographics']['industries']))
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.industries') }}</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                            @foreach($audienceOptions['demographics']['industries'] as $industry)
                            <label class="inline-flex items-center">
                                <input type="checkbox" name="industries[]" value="{{ $industry['id'] }}"
                                       {{ in_array($industry['id'], old('industries', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $industry['name'] }}</span>
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
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right flex-row-reverse' : '' }} flex items-center">
                    <i class="fas fa-user-friends {{ $isRtl ? 'ml-2' : 'mr-2' }} text-purple-500"></i>{{ __('campaigns.custom_lookalike_audiences') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="custom_audiences" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.custom_audiences') }}</label>
                        <p class="text-xs text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.custom_audiences_desc') }}</p>
                        <select name="custom_audiences[]" id="custom_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                x-data x-init="$el.setAttribute('size', '4')">
                            @if(!empty($audienceOptions['custom_audiences']))
                                @foreach($audienceOptions['custom_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], old('custom_audiences', [])) ? 'selected' : '' }}>
                                        {{ $audience['name'] }} @if($audience['size'] ?? null)({{ number_format($audience['size']) }})@endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>{{ __('campaigns.connect_ad_account') }}</option>
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-400 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.hold_ctrl_select_multiple') }}</p>
                    </div>
                    <div>
                        <label for="lookalike_audiences" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.lookalike_audiences') }}</label>
                        <p class="text-xs text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.lookalike_audiences_desc') }}</p>
                        <select name="lookalike_audiences[]" id="lookalike_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                x-data x-init="$el.setAttribute('size', '4')">
                            @if(!empty($audienceOptions['lookalike_audiences']))
                                @foreach($audienceOptions['lookalike_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], old('lookalike_audiences', [])) ? 'selected' : '' }}>
                                        {{ $audience['name'] }} @if($audience['size'] ?? null)({{ number_format($audience['size']) }})@endif
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>{{ __('campaigns.connect_ad_account') }}</option>
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-400 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.hold_ctrl_select_multiple') }}</p>
                    </div>
                    <div class="md:col-span-2">
                        <label for="excluded_audiences" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.excluded_audiences') }}</label>
                        <p class="text-xs text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.excluded_audiences_desc') }}</p>
                        <select name="excluded_audiences[]" id="excluded_audiences" multiple
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}"
                                x-data x-init="$el.setAttribute('size', '3')">
                            @if(!empty($audienceOptions['custom_audiences']))
                                @foreach($audienceOptions['custom_audiences'] as $audience)
                                    <option value="{{ $audience['id'] }}" {{ in_array($audience['id'], old('excluded_audiences', [])) ? 'selected' : '' }}>
                                        {{ $audience['name'] }}
                                    </option>
                                @endforeach
                            @else
                                <option value="" disabled>{{ __('campaigns.connect_ad_account') }}</option>
                            @endif
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interest Targeting --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right flex-row-reverse' : '' }} flex items-center">
                    <i class="fas fa-heart {{ $isRtl ? 'ml-2' : 'mr-2' }} text-red-500"></i>{{ __('campaigns.interest_targeting') }}
                </h3>
                <p class="text-sm text-gray-500 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.interest_targeting_desc') }}</p>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3">
                    @foreach($audienceOptions['interests'] as $interest)
                    <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ $isRtl ? 'flex-row-reverse' : '' }}"
                           :class="{ 'border-blue-500 bg-blue-50': selectedInterests.includes('{{ $interest['id'] }}') }"
                           x-data="{ checked: {{ in_array($interest['id'], old('interests', [])) ? 'true' : 'false' }} }">
                        <input type="checkbox" name="interests[]" value="{{ $interest['id'] }}"
                               {{ in_array($interest['id'], old('interests', [])) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                               @change="checked = $el.checked">
                        <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $interest['name'] }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="mt-4">
                    <label for="interest_search" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.search_more_interests') }}</label>
                    <input type="text" id="interest_search" placeholder="{{ __('campaigns.type_to_search_interests') }}"
                           class="block w-full md:w-1/2 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm {{ $isRtl ? 'text-right' : '' }}" dir="{{ $dir }}">
                    <p class="mt-1 text-xs text-gray-400 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.additional_interests_api') }}</p>
                </div>
            </div>
        </div>

        {{-- Behavior & In-Market Targeting --}}
        @if(!empty($audienceOptions['behaviors']))
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right flex-row-reverse' : '' }} flex items-center">
                    <i class="fas fa-shopping-cart {{ $isRtl ? 'ml-2' : 'mr-2' }} text-green-500"></i>
                    @if($audienceOptions['platform'] === 'google')
                        {{ __('campaigns.in_market_audiences') }}
                    @else
                        {{ __('campaigns.behavior_targeting') }}
                    @endif
                </h3>
                <p class="text-sm text-gray-500 mb-4 {{ $isRtl ? 'text-right' : '' }}">
                    @if($audienceOptions['platform'] === 'google')
                        {{ __('campaigns.in_market_desc') }}
                    @else
                        {{ __('campaigns.behavior_targeting_desc') }}
                    @endif
                </p>
                @php
                    $behaviorsByCategory = collect($audienceOptions['behaviors'])->groupBy('category');
                @endphp
                @foreach($behaviorsByCategory as $category => $behaviors)
                <div class="mb-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ $category }}</h4>
                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                        @foreach($behaviors as $behavior)
                        <label class="inline-flex items-center p-2 border rounded-lg hover:bg-gray-50 cursor-pointer transition-colors {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="checkbox" name="behaviors[]" value="{{ $behavior['id'] }}"
                                   {{ in_array($behavior['id'], old('behaviors', [])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $behavior['name'] }}</span>
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
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.placements') }}</h3>
                <div class="space-y-4">
                    <div>
                        <label class="inline-flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <input type="checkbox" name="automatic_placements" value="1" x-model="automaticPlacements"
                                   {{ old('automatic_placements', true) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ __('campaigns.advantage_plus_recommended') }}</span>
                        </label>
                        <p class="{{ $isRtl ? 'mr-6 text-right' : 'ml-6' }} text-xs text-gray-500">{{ __('campaigns.platform_optimize_placements') }}</p>
                    </div>
                    <div x-show="!automaticPlacements" class="mt-4 {{ $isRtl ? 'pr-6' : 'pl-6' }} space-y-2">
                        @php
                            $placements = [
                                'feed' => __('campaigns.placement_feed'),
                                'stories' => __('campaigns.placement_stories'),
                                'reels' => __('campaigns.placement_reels'),
                                'right_column' => __('campaigns.placement_right_column'),
                                'in_stream' => __('campaigns.placement_in_stream'),
                                'search' => __('campaigns.placement_search'),
                                'marketplace' => __('campaigns.placement_marketplace'),
                                'messenger' => __('campaigns.placement_messenger'),
                            ];
                        @endphp
                        @foreach($placements as $key => $label)
                            <label class="inline-flex items-center {{ $isRtl ? 'ml-4 flex-row-reverse' : 'mr-4' }}">
                                <input type="checkbox" name="placements[]" value="{{ $key }}"
                                       {{ in_array($key, old('placements', [])) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                                <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Device Targeting --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.device_targeting') }}</h3>
                <div class="space-y-2">
                    @php
                        $devices = [
                            'mobile' => __('campaigns.device_mobile'),
                            'desktop' => __('campaigns.device_desktop'),
                            'tablet' => __('campaigns.device_tablet'),
                        ];
                    @endphp
                    @foreach($devices as $key => $label)
                        <label class="inline-flex items-center {{ $isRtl ? 'ml-4 flex-row-reverse' : 'mr-4' }}">
                            <input type="checkbox" name="device_platforms[]" value="{{ $key }}"
                                   {{ in_array($key, old('device_platforms', ['mobile', 'desktop', 'tablet'])) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500">
                            <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-sm text-gray-700">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'justify-end space-x-3' }}">
            <a href="{{ route('org.campaigns.ad-sets.index', [$currentOrg, $campaign->campaign_id]) }}"
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                {{ __('campaigns.cancel') }}
            </a>
            <button type="submit"
                    class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                {{ __('campaigns.create_ad_set') }}
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
