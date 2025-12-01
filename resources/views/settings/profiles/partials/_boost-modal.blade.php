{{-- Boost Settings Modal --}}
<div x-show="showBoostModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="boost-modal-title"
     role="dialog"
     aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="showBoostModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             @click="showBoostModal = false"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="showBoostModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto"
             x-data="boostForm()">

            <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900" id="boost-modal-title">
                        <span x-text="editingBoostId ? '{{ __("profiles.edit_boost") }}' : '{{ __("profiles.create_boost") }}'"></span>
                    </h3>
                    <button @click="showBoostModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form @submit.prevent="saveBoost()" class="space-y-4">
                    {{-- Boost Name --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.boost_name') }}</label>
                        <input type="text"
                               x-model="form.name"
                               required
                               placeholder="{{ __('profiles.boost_name_placeholder') }}"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>

                    {{-- Ad Account --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.ad_account') }}</label>
                        <select x-model="form.ad_account_id"
                                @change="onAdAccountChange()"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('profiles.select_ad_account') }}</option>
                            @forelse($adAccounts ?? [] as $account)
                                <option value="{{ $account->id }}">
                                    {{ $account->account_name }} ({{ ucfirst($account->platform) }} - {{ $account->currency ?? 'USD' }})
                                </option>
                            @empty
                                <option value="" disabled>{{ __('profiles.no_ad_accounts_available') }}</option>
                            @endforelse
                        </select>
                        @if(empty($adAccounts) || (isset($adAccounts) && $adAccounts->isEmpty()))
                            <p class="mt-1 text-xs text-yellow-600">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <a href="{{ route('orgs.settings.platform-connections', $currentOrg) }}" class="underline hover:text-yellow-700">
                                    {{ __('profiles.connect_ad_account_first') }}
                                </a>
                            </p>
                        @endif
                    </div>

                    {{-- Campaign Objective --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.campaign_objective') }}</label>
                        <select x-model="form.objective"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('profiles.select_objective') }}</option>
                            <template x-for="obj in objectives" :key="obj.id">
                                <option :value="obj.id" x-text="obj.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500" x-text="objectives.find(o => o.id === form.objective)?.description || ''"></p>
                    </div>

                    {{-- Boost Delay --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.boost_delay') }}</label>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="number"
                                   x-model="form.delay_value"
                                   min="0"
                                   placeholder="1"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <select x-model="form.delay_unit"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <option value="hours">{{ __('profiles.hours') }}</option>
                                <option value="days">{{ __('profiles.days') }}</option>
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('profiles.boost_delay_hint') }}</p>
                    </div>

                    {{-- Budget and Duration --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.budget') }}</label>
                            <div class="relative">
                                <input type="number"
                                       x-model="form.budget_amount"
                                       @input="debouncedValidateBudget()"
                                       min="0"
                                       step="0.01"
                                       placeholder="{{ __('profiles.enter_budget') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <span x-show="validatingBudget" class="absolute end-3 top-1/2 -translate-y-1/2">
                                    <i class="fas fa-spinner fa-spin text-gray-400 text-sm"></i>
                                </span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.campaign_days') }}</label>
                            <input type="number"
                                   x-model="form.duration_days"
                                   @input="debouncedValidateBudget()"
                                   min="1"
                                   placeholder="{{ __('profiles.number_of_days') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>

                    {{-- Budget Validation Feedback --}}
                    <div x-show="budgetValidation.errors.length > 0 || budgetValidation.warnings.length > 0" class="rounded-md p-3" :class="budgetValidation.errors.length > 0 ? 'bg-red-50' : 'bg-yellow-50'">
                        <template x-for="error in budgetValidation.errors" :key="error">
                            <p class="text-sm text-red-600 flex items-center gap-1 mb-1">
                                <i class="fas fa-times-circle"></i>
                                <span x-text="error"></span>
                            </p>
                        </template>
                        <template x-for="warning in budgetValidation.warnings" :key="warning">
                            <p class="text-sm text-yellow-600 flex items-center gap-1 mb-1">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span x-text="warning"></span>
                            </p>
                        </template>
                    </div>
                    <p class="text-xs text-gray-500">{{ __('profiles.budget_note') }}</p>

                    {{-- Platform Features Loading Indicator --}}
                    <div x-show="loadingPlatformConfig" class="text-center py-4 border-t border-gray-200 mt-4">
                        <i class="fas fa-spinner fa-spin text-blue-500 me-2"></i>
                        <span class="text-sm text-gray-600">{{ __('profiles.loading_platform_config') }}...</span>
                    </div>

                    {{-- Advantage+ Audience Section (Meta only) --}}
                    <div x-show="showAdvantagePlus && !loadingPlatformConfig" x-cloak class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">{{ __('profiles.advantage_plus_audience') }}</h4>
                                <p class="text-xs text-gray-500">{{ __('profiles.advantage_plus_description') }}</p>
                            </div>
                            <button type="button"
                                    @click="form.advantage_plus_enabled = !form.advantage_plus_enabled"
                                    :class="form.advantage_plus_enabled ? 'bg-blue-600' : 'bg-gray-200'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span :class="form.advantage_plus_enabled ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>

                        {{-- Advantage+ Options (shown when enabled) --}}
                        <div x-show="form.advantage_plus_enabled" x-cloak class="space-y-3 ps-4 border-s-2 border-blue-200">
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.advantage_plus_settings.audience_expansion"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ms-2 text-sm text-gray-700">{{ __('profiles.audience_expansion') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.advantage_plus_settings.auto_placements"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ms-2 text-sm text-gray-700">{{ __('profiles.auto_placements') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" x-model="form.advantage_plus_settings.dynamic_creative"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ms-2 text-sm text-gray-700">{{ __('profiles.dynamic_creative') }}</span>
                            </label>
                        </div>
                    </div>

                    {{-- TikTok Spark Ads (TikTok only) --}}
                    <div x-show="showSparkAds && !loadingPlatformConfig" x-cloak class="border-t border-gray-200 pt-4 mt-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">{{ __('profiles.spark_ads') }}</h4>
                                <p class="text-xs text-gray-500">{{ __('profiles.spark_ads_description') }}</p>
                            </div>
                            <button type="button"
                                    @click="form.spark_ads = !form.spark_ads"
                                    :class="form.spark_ads ? 'bg-blue-600' : 'bg-gray-200'"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <span :class="form.spark_ads ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'"
                                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"></span>
                            </button>
                        </div>
                    </div>

                    {{-- Placements Section (when available) --}}
                    <div x-show="showPlacements && !loadingPlatformConfig" x-cloak class="border-t border-gray-200 pt-4 mt-4">
                        <button type="button" @click="showPlacementsSection = !showPlacementsSection"
                                class="flex items-center justify-between w-full text-start">
                            <h4 class="text-sm font-medium text-gray-900">{{ __('profiles.placements') }}</h4>
                            <i :class="showPlacementsSection ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-gray-400 text-sm"></i>
                        </button>

                        <div x-show="showPlacementsSection" x-cloak class="mt-3 space-y-2">
                            <label class="flex items-center mb-2">
                                <input type="checkbox" x-model="form.auto_placements"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span class="ms-2 text-sm text-gray-700">{{ __('profiles.auto_placements_recommended') }}</span>
                            </label>

                            <div x-show="!form.auto_placements" class="grid grid-cols-2 gap-2">
                                <template x-for="placement in platformConfig?.placements || []" :key="placement.id">
                                    <label class="flex items-center">
                                        <input type="checkbox" :value="placement.id" x-model="form.placements"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ms-2 text-sm text-gray-700" x-text="placement.name"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Bidding Strategy (Google, TikTok) --}}
                    <div x-show="showBiddingStrategy && !loadingPlatformConfig" x-cloak class="border-t border-gray-200 pt-4 mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.bidding_strategy') }}</label>
                        <select x-model="form.bidding_strategy"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('profiles.auto_bidding') }}</option>
                            <template x-for="strategy in platformConfig?.bidding_strategies || []" :key="strategy.id">
                                <option :value="strategy.id" x-text="strategy.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- B2B Targeting (LinkedIn only) --}}
                    <div x-show="showB2BTargeting && !loadingPlatformConfig" x-cloak class="border-t border-gray-200 pt-4 mt-4">
                        <button type="button" @click="showB2BSection = !showB2BSection"
                                class="flex items-center justify-between w-full text-start">
                            <h4 class="text-sm font-medium text-gray-900">{{ __('profiles.b2b_targeting') }}</h4>
                            <i :class="showB2BSection ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-gray-400 text-sm"></i>
                        </button>

                        <div x-show="showB2BSection" x-cloak class="mt-4 space-y-4">
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">{{ __('profiles.job_titles') }}</label>
                                <input type="text" x-model="form.targeting.job_titles"
                                       placeholder="{{ __('profiles.enter_job_titles') }}"
                                       class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">{{ __('profiles.company_size') }}</label>
                                <select x-model="form.targeting.company_size"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">{{ __('profiles.any_size') }}</option>
                                    <template x-for="size in platformConfig?.company_sizes || []" :key="size.id">
                                        <option :value="size.id" x-text="size.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">{{ __('profiles.seniority') }}</label>
                                <select x-model="form.targeting.seniority"
                                        class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">{{ __('profiles.any_seniority') }}</option>
                                    <template x-for="level in platformConfig?.seniority_levels || []" :key="level.id">
                                        <option :value="level.id" x-text="level.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-600 mb-1">{{ __('profiles.industries') }}</label>
                                <input type="text" x-model="form.targeting.industries"
                                       placeholder="{{ __('profiles.enter_industries') }}"
                                       class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- Audiences Section (collapsed by default) --}}
                    <div class="border-t border-gray-200 pt-4">
                        <button type="button" @click="showAudienceSection = !showAudienceSection" class="flex items-center justify-between w-full text-start">
                            <h4 class="text-sm font-medium text-gray-900">{{ __('profiles.audience_targeting') }}</h4>
                            <i :class="showAudienceSection ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-gray-400 text-sm"></i>
                        </button>

                        <div x-show="showAudienceSection" x-cloak class="mt-4 space-y-4">
                            {{-- Custom Audiences --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.custom_audiences') }}</label>
                                <select multiple
                                        x-model="form.custom_audiences"
                                        :disabled="!form.ad_account_id || loadingAudiences"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-24">
                                    <template x-if="loadingAudiences">
                                        <option disabled>{{ __('profiles.loading_audiences') }}...</option>
                                    </template>
                                    <template x-for="audience in customAudiences" :key="audience.id">
                                        <option :value="audience.id" x-text="audience.name + (audience.approximate_count ? ' (' + audience.approximate_count.toLocaleString() + ')' : '')"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ __('profiles.custom_audiences_hint') }}</p>
                            </div>

                            {{-- Lookalike Audiences --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.lookalike_audiences') }}</label>
                                <select multiple
                                        x-model="form.lookalike_audiences"
                                        :disabled="!form.ad_account_id || loadingAudiences"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-24">
                                    <template x-for="audience in lookalikeAudiences" :key="audience.id">
                                        <option :value="audience.id" x-text="audience.name + (audience.lookalike_ratio ? ' (' + audience.lookalike_ratio + '%)' : '')"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ __('profiles.lookalike_audiences_hint') }}</p>
                            </div>

                            {{-- Excluded Audiences --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.excluded_audiences') }}</label>
                                <select multiple
                                        x-model="form.excluded_audiences"
                                        :disabled="!form.ad_account_id || loadingAudiences"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-20">
                                    <template x-for="audience in allAudiences" :key="audience.id">
                                        <option :value="audience.id" x-text="audience.name"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Detailed Targeting Section (collapsed by default) --}}
                    <div class="border-t border-gray-200 pt-4">
                        <button type="button" @click="showDetailedTargeting = !showDetailedTargeting" class="flex items-center justify-between w-full text-start">
                            <h4 class="text-sm font-medium text-gray-900">{{ __('profiles.detailed_targeting') }}</h4>
                            <i :class="showDetailedTargeting ? 'fa-chevron-up' : 'fa-chevron-down'" class="fas text-gray-400 text-sm"></i>
                        </button>

                        <div x-show="showDetailedTargeting" x-cloak class="mt-4 space-y-4">
                            {{-- Interests --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.interests') }}</label>
                                <input type="text"
                                       x-model="form.interests"
                                       placeholder="{{ __('profiles.search_interests') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Work Positions --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.work_positions') }}</label>
                                <input type="text"
                                       x-model="form.work_positions"
                                       placeholder="{{ __('profiles.search_work_positions') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Countries --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.countries') }}</label>
                                <input type="text"
                                       x-model="form.countries"
                                       placeholder="{{ __('profiles.search_countries') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Cities --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.cities') }}</label>
                                <input type="text"
                                       x-model="form.cities"
                                       placeholder="{{ __('profiles.search_cities') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Genders --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.genders') }}</label>
                                <select x-model="form.genders"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">{{ __('profiles.all_genders') }}</option>
                                    <option value="male">{{ __('profiles.male') }}</option>
                                    <option value="female">{{ __('profiles.female') }}</option>
                                </select>
                            </div>

                            {{-- Age Range --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.min_age') }}</label>
                                    <input type="number"
                                           x-model="form.min_age"
                                           min="13"
                                           max="65"
                                           placeholder="18"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.max_age') }}</label>
                                    <input type="number"
                                           x-model="form.max_age"
                                           min="13"
                                           max="65"
                                           placeholder="65"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        @click="saveBoost()"
                        :disabled="loading || !budgetValidation.valid"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="loading" class="me-2">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                    {{ __('profiles.save_boost') }}
                </button>
                <button type="button"
                        @click="showBoostModal = false"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                    {{ __('profiles.close') }}
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function boostForm() {
    return {
        loading: false,
        loadingAudiences: false,
        loadingPlatformConfig: false,
        validatingBudget: false,
        showAudienceSection: false,
        showDetailedTargeting: false,
        showPlacementsSection: false,
        showB2BSection: false,

        // Platform configuration from API
        platformConfig: null,

        // Form data
        form: {
            name: '',
            delay_value: 1,
            delay_unit: 'hours',
            ad_account_id: '',
            objective: '',
            budget_amount: '',
            duration_days: 7,
            advantage_plus_enabled: false,
            advantage_plus_settings: {
                audience_expansion: true,
                auto_placements: true,
                dynamic_creative: false,
            },
            spark_ads: false,
            auto_placements: true,
            placements: [],
            bidding_strategy: '',
            targeting: {
                job_titles: '',
                company_size: '',
                seniority: '',
                industries: '',
            },
            custom_audiences: [],
            lookalike_audiences: [],
            excluded_audiences: [],
            interests: '',
            work_positions: '',
            countries: '',
            cities: '',
            genders: '',
            min_age: '',
            max_age: ''
        },

        // Default objectives (fallback when no platform config)
        defaultObjectives: [
            { id: 'OUTCOME_AWARENESS', name: '{{ __("profiles.objective_awareness") }}', description: '{{ __("profiles.objective_awareness_desc") }}' },
            { id: 'OUTCOME_ENGAGEMENT', name: '{{ __("profiles.objective_engagement") }}', description: '{{ __("profiles.objective_engagement_desc") }}' },
            { id: 'OUTCOME_TRAFFIC', name: '{{ __("profiles.objective_traffic") }}', description: '{{ __("profiles.objective_traffic_desc") }}' },
            { id: 'OUTCOME_LEADS', name: '{{ __("profiles.objective_leads") }}', description: '{{ __("profiles.objective_leads_desc") }}' },
            { id: 'OUTCOME_SALES', name: '{{ __("profiles.objective_sales") }}', description: '{{ __("profiles.objective_sales_desc") }}' },
            { id: 'OUTCOME_APP_PROMOTION', name: '{{ __("profiles.objective_app") }}', description: '{{ __("profiles.objective_app_desc") }}' },
        ],

        // Computed: objectives from platform config or defaults
        get objectives() {
            return this.platformConfig?.objectives || this.defaultObjectives;
        },

        // Platform-specific feature visibility
        get showAdvantagePlus() {
            return this.platformConfig?.special_features?.advantage_plus === true;
        },

        get showSparkAds() {
            return this.platformConfig?.special_features?.spark_ads === true;
        },

        get showPlacements() {
            return (this.platformConfig?.placements?.length || 0) > 0;
        },

        get showBiddingStrategy() {
            return (this.platformConfig?.bidding_strategies?.length || 0) > 0;
        },

        get showB2BTargeting() {
            return this.platformConfig?.b2b_targeting !== undefined && Object.keys(this.platformConfig.b2b_targeting).length > 0;
        },

        get platformName() {
            return this.platformConfig?.platform_name || '{{ __("profiles.platform") }}';
        },

        get minBudget() {
            return this.platformConfig?.min_budget || 1;
        },

        // Audiences data
        customAudiences: [],
        lookalikeAudiences: [],
        allAudiences: [],

        // Budget validation state
        budgetValidation: {
            valid: true,
            warnings: [],
            errors: []
        },

        // Debounce timer
        _budgetValidateTimer: null,

        debouncedValidateBudget() {
            if (this._budgetValidateTimer) {
                clearTimeout(this._budgetValidateTimer);
            }
            this._budgetValidateTimer = setTimeout(() => this.validateBudget(), 500);
        },

        onAdAccountChange() {
            this.loadPlatformConfig();
            this.loadAudiences();
            this.validateBudget();
        },

        async loadPlatformConfig() {
            if (!this.form.ad_account_id) {
                this.platformConfig = null;
                this.form.objective = 'OUTCOME_ENGAGEMENT'; // Reset to default
                return;
            }

            this.loadingPlatformConfig = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boost-config?ad_account_id=${this.form.ad_account_id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.platformConfig = data.data;
                    // Set first objective as default if available
                    if (this.platformConfig.objectives?.length > 0 && !this.form.objective) {
                        this.form.objective = this.platformConfig.objectives[0].id;
                    }
                } else {
                    this.platformConfig = null;
                }
            } catch (error) {
                console.error('Error loading platform config:', error);
                this.platformConfig = null;
            }
            this.loadingPlatformConfig = false;
        },

        async loadAudiences() {
            if (!this.form.ad_account_id) {
                this.customAudiences = [];
                this.lookalikeAudiences = [];
                this.allAudiences = [];
                return;
            }

            this.loadingAudiences = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/audiences?ad_account_id=${this.form.ad_account_id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.allAudiences = data.data;
                    this.customAudiences = data.data.filter(a => a.type === 'custom' || a.audience_type === 'custom');
                    this.lookalikeAudiences = data.data.filter(a => a.type === 'lookalike' || a.audience_type === 'lookalike');
                }
            } catch (error) {
                console.error('Error loading audiences:', error);
            }
            this.loadingAudiences = false;
        },

        async validateBudget() {
            if (!this.form.ad_account_id || !this.form.budget_amount || !this.form.duration_days) {
                this.budgetValidation = { valid: true, warnings: [], errors: [] };
                return;
            }

            this.validatingBudget = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/validate-budget`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        ad_account_id: this.form.ad_account_id,
                        budget_amount: parseFloat(this.form.budget_amount) || 0,
                        duration_days: parseInt(this.form.duration_days) || 1,
                    })
                });
                const data = await response.json();
                if (data.success) {
                    this.budgetValidation = data.data;
                }
            } catch (error) {
                console.error('Budget validation error:', error);
                // On error, allow submission
                this.budgetValidation = { valid: true, warnings: [], errors: [] };
            }
            this.validatingBudget = false;
        },

        async saveBoost() {
            this.loading = true;
            try {
                const url = this.$root.editingBoostId
                    ? `/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boosts/${this.$root.editingBoostId}`
                    : `/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boosts`;

                const method = this.$root.editingBoostId ? 'PATCH' : 'POST';

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.form.name,
                        trigger_type: 'auto_after_publish',
                        delay_value: this.form.delay_value,
                        delay_unit: this.form.delay_unit,
                        ad_account_id: this.form.ad_account_id,
                        objective: this.form.objective,
                        budget_amount: this.form.budget_amount,
                        duration_hours: this.form.duration_days * 24,
                        // Platform-specific settings
                        platform: this.platformConfig?.platform || null,
                        advantage_plus_enabled: this.form.advantage_plus_enabled,
                        advantage_plus_settings: this.form.advantage_plus_settings,
                        spark_ads: this.form.spark_ads,
                        auto_placements: this.form.auto_placements,
                        placements: this.form.placements,
                        bidding_strategy: this.form.bidding_strategy,
                        targeting_options: {
                            custom_audiences: this.form.custom_audiences,
                            lookalike_audiences: this.form.lookalike_audiences,
                            excluded_audiences: this.form.excluded_audiences,
                            interests: this.form.interests,
                            work_positions: this.form.work_positions,
                            countries: this.form.countries,
                            cities: this.form.cities,
                            genders: this.form.genders,
                            min_age: this.form.min_age,
                            max_age: this.form.max_age,
                            // B2B targeting (LinkedIn)
                            job_titles: this.form.targeting.job_titles,
                            company_size: this.form.targeting.company_size,
                            seniority: this.form.targeting.seniority,
                            industries: this.form.targeting.industries
                        }
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: this.$root.editingBoostId ? '{{ __("profiles.boost_updated") }}' : '{{ __("profiles.boost_created") }}', type: 'success' }
                    }));
                    this.$root.showBoostModal = false;
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
        }
    };
}
</script>
