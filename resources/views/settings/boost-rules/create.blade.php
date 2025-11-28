@extends('layouts.admin')

@section('title', __('settings.create_boost_rule') . ' - ' . __('common.settings'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('common.settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.boost-rules.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('settings.boost_rules') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('common.create') }}</span>
        </nav>
    </div>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('settings.create_boost_rule') }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('settings.boost_rules_description') }}
            </p>
        </div>
    </div>

    <form action="{{ route('orgs.settings.boost-rules.store', $currentOrg) }}" method="POST" class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-info-circle text-orange-500 me-2"></i>{{ __('settings.basic_information') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.rule_name') }} <span class="text-red-500">*</span></label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500"
                           placeholder="{{ __('settings.boost_rules_placeholder_name') }}">
                    @error('name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="profile_group_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.profile_group') }}</label>
                    <select name="profile_group_id" id="profile_group_id"
                            class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('settings.all_profile_groups') }}</option>
                        @foreach($profileGroups ?? [] as $group)
                            <option value="{{ $group->group_id }}" {{ old('profile_group_id') == $group->group_id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="ad_account_id" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.ad_account') }} <span class="text-red-500">*</span></label>
                    <select name="ad_account_id" id="ad_account_id" required
                            class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        <option value="">{{ __('settings.select_ad_account') }}</option>
                        @foreach($adAccounts ?? [] as $account)
                            <option value="{{ $account->account_id }}" {{ old('ad_account_id') == $account->account_id ? 'selected' : '' }}>
                                {{ $account->account_name }} ({{ ucfirst($account->platform) }})
                            </option>
                        @endforeach
                    </select>
                    @error('ad_account_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Trigger Conditions --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-bolt text-yellow-500 me-2"></i>{{ __('settings.trigger_conditions') }}
            </h3>
            <p class="text-sm text-gray-500 mb-4">{{ __('settings.trigger_conditions_description') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="trigger_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.trigger_type') }}</label>
                    <select name="trigger_type" id="trigger_type"
                            class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        <option value="engagement_threshold" {{ old('trigger_type', 'engagement_threshold') == 'engagement_threshold' ? 'selected' : '' }}>{{ __('settings.trigger_type_engagement') }}</option>
                        <option value="time_based" {{ old('trigger_type') == 'time_based' ? 'selected' : '' }}>{{ __('settings.trigger_type_time_based') }}</option>
                        <option value="viral_velocity" {{ old('trigger_type') == 'viral_velocity' ? 'selected' : '' }}>{{ __('settings.trigger_type_viral') }}</option>
                    </select>
                </div>

                <div>
                    <label for="trigger_metric" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.metric') }}</label>
                    <select name="trigger_metric" id="trigger_metric"
                            class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        <option value="engagement_rate" {{ old('trigger_metric', 'engagement_rate') == 'engagement_rate' ? 'selected' : '' }}>{{ __('settings.metric_engagement_rate') }}</option>
                        <option value="likes" {{ old('trigger_metric') == 'likes' ? 'selected' : '' }}>{{ __('settings.metric_likes') }}</option>
                        <option value="comments" {{ old('trigger_metric') == 'comments' ? 'selected' : '' }}>{{ __('settings.metric_comments') }}</option>
                        <option value="shares" {{ old('trigger_metric') == 'shares' ? 'selected' : '' }}>{{ __('settings.metric_shares') }}</option>
                        <option value="saves" {{ old('trigger_metric') == 'saves' ? 'selected' : '' }}>{{ __('settings.metric_saves') }}</option>
                        <option value="reach" {{ old('trigger_metric') == 'reach' ? 'selected' : '' }}>{{ __('settings.metric_reach') }}</option>
                    </select>
                </div>

                <div>
                    <label for="trigger_threshold" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.threshold_value') }}</label>
                    <input type="number" name="trigger_threshold" id="trigger_threshold" value="{{ old('trigger_threshold', 5) }}"
                           min="0" step="0.1"
                           class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                    <p class="mt-1 text-xs text-gray-500">{{ __('settings.boost_rules_threshold_help') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label for="evaluation_period_hours" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.evaluation_period_hours') }}</label>
                    <input type="number" name="evaluation_period_hours" id="evaluation_period_hours" value="{{ old('evaluation_period_hours', 24) }}"
                           min="1" max="168"
                           class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                    <p class="mt-1 text-xs text-gray-500">{{ __('settings.boost_rules_evaluation_help') }}</p>
                </div>

                <div>
                    <label for="min_post_age_hours" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.min_post_age_hours') }}</label>
                    <input type="number" name="min_post_age_hours" id="min_post_age_hours" value="{{ old('min_post_age_hours', 2) }}"
                           min="0" max="72"
                           class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                    <p class="mt-1 text-xs text-gray-500">{{ __('settings.boost_rules_min_age_help') }}</p>
                </div>
            </div>
        </div>

        {{-- Budget Configuration --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-dollar-sign text-green-500 me-2"></i>{{ __('settings.budget_configuration') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="budget_amount" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.budget_per_boost') }} <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 ps-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="budget_amount" id="budget_amount" value="{{ old('budget_amount', 50) }}"
                               min="1" step="0.01" required
                               class="w-full ps-8 rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                    </div>
                </div>

                <div>
                    <label for="budget_currency" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.currency') }}</label>
                    <select name="budget_currency" id="budget_currency"
                            class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        <option value="USD" {{ old('budget_currency', 'USD') == 'USD' ? 'selected' : '' }}>USD</option>
                        <option value="EUR" {{ old('budget_currency') == 'EUR' ? 'selected' : '' }}>EUR</option>
                        <option value="GBP" {{ old('budget_currency') == 'GBP' ? 'selected' : '' }}>GBP</option>
                        <option value="AED" {{ old('budget_currency') == 'AED' ? 'selected' : '' }}>AED</option>
                    </select>
                </div>

                <div>
                    <label for="duration_hours" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.boost_duration_hours') }}</label>
                    <input type="number" name="duration_hours" id="duration_hours" value="{{ old('duration_hours', 48) }}"
                           min="1" max="168"
                           class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label for="daily_budget_cap" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.daily_budget_cap') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 ps-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="daily_budget_cap" id="daily_budget_cap" value="{{ old('daily_budget_cap') }}"
                               min="0" step="0.01"
                               class="w-full ps-8 rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="{{ __('settings.boost_rules_no_limit') }}">
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ __('settings.boost_rules_daily_cap_help') }}</p>
                </div>

                <div>
                    <label for="monthly_budget_cap" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.monthly_budget_cap') }}</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 ps-3 flex items-center text-gray-500">$</span>
                        <input type="number" name="monthly_budget_cap" id="monthly_budget_cap" value="{{ old('monthly_budget_cap') }}"
                               min="0" step="0.01"
                               class="w-full ps-8 rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500"
                               placeholder="{{ __('settings.boost_rules_no_limit') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- Targeting (Simplified) --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-bullseye text-red-500 me-2"></i>{{ __('settings.targeting') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="targeting_type" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.audience') }}</label>
                    <select name="targeting_type" id="targeting_type"
                            class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                        <option value="page_engagers" {{ old('targeting_type', 'page_engagers') == 'page_engagers' ? 'selected' : '' }}>{{ __('settings.audience_page_engagers') }}</option>
                        <option value="lookalike" {{ old('targeting_type') == 'lookalike' ? 'selected' : '' }}>{{ __('settings.audience_lookalike') }}</option>
                        <option value="interests" {{ old('targeting_type') == 'interests' ? 'selected' : '' }}>{{ __('settings.audience_interests') }}</option>
                        <option value="broad" {{ old('targeting_type') == 'broad' ? 'selected' : '' }}>{{ __('settings.audience_broad') }}</option>
                    </select>
                </div>

                <div>
                    <label for="age_range" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.age_range') }}</label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="min_age" value="{{ old('min_age', 18) }}" min="13" max="65"
                               class="w-20 rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500 text-sm">
                        <span class="text-gray-500">{{ __('settings.to') }}</span>
                        <input type="number" name="max_age" value="{{ old('max_age', 65) }}" min="13" max="65"
                               class="w-20 rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500 text-sm">
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('settings.locations') }}</label>
                <div x-data="{ locations: {{ json_encode(old('locations', [])) }} }" class="space-y-2">
                    <div class="flex flex-wrap gap-2 min-h-[40px] p-3 bg-gray-50 rounded-lg border border-gray-200">
                        <template x-for="(loc, index) in locations" :key="index">
                            <span class="inline-flex items-center gap-1 px-2 py-1 bg-orange-100 text-orange-700 rounded-full text-xs">
                                <span x-text="loc"></span>
                                <button type="button" @click="locations.splice(index, 1)" class="hover:text-orange-900">
                                    <i class="fas fa-times"></i>
                                </button>
                                <input type="hidden" name="locations[]" :value="loc">
                            </span>
                        </template>
                        <span x-show="locations.length === 0" class="text-sm text-gray-400">{{ __('settings.boost_rules_no_locations') }}</span>
                    </div>
                    <div class="flex gap-2">
                        <input type="text" x-ref="newLocation" placeholder="{{ __('settings.boost_rules_add_location') }}"
                               class="flex-1 rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500 text-sm"
                               @keydown.enter.prevent="if($refs.newLocation.value.trim()) { locations.push($refs.newLocation.value.trim()); $refs.newLocation.value = ''; }">
                        <button type="button" @click="if($refs.newLocation.value.trim()) { locations.push($refs.newLocation.value.trim()); $refs.newLocation.value = ''; }"
                                class="px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 text-sm">
                            <i class="fas fa-plus me-1"></i>{{ __('common.add') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Schedule --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-clock text-blue-500 me-2"></i>{{ __('settings.schedule') }}
            </h3>

            <div class="space-y-4">
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('settings.business_hours_only') }}</p>
                        <p class="text-xs text-gray-500">{{ __('settings.business_hours_description') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="business_hours_only" value="1" {{ old('business_hours_only') ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>

                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ __('settings.exclude_weekends') }}</p>
                        <p class="text-xs text-gray-500">{{ __('settings.exclude_weekends_description') }}</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="exclude_weekends" value="1" {{ old('exclude_weekends') ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                    </label>
                </div>

                <div>
                    <label for="max_boosts_per_day" class="block text-sm font-medium text-gray-700 mb-1">{{ __('settings.max_boosts_per_day') }}</label>
                    <input type="number" name="max_boosts_per_day" id="max_boosts_per_day" value="{{ old('max_boosts_per_day', 5) }}"
                           min="1" max="50"
                           class="w-full rounded-lg border-gray-300 focus:ring-orange-500 focus:border-orange-500">
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900">{{ __('settings.activate_rule') }}</h3>
                    <p class="text-sm text-gray-500">{{ __('settings.activate_rule_description') }}</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-orange-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-orange-600"></div>
                </label>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('orgs.settings.boost-rules.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                {{ __('common.cancel') }}
            </a>
            <button type="submit"
                    class="px-6 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition text-sm font-medium">
                <i class="fas fa-rocket me-2"></i>{{ __('settings.create_rule') }}
            </button>
        </div>
    </form>
</div>
@endsection
