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
                    <div x-data="{ adAccountDropdownOpen: false }" class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.ad_account') }}</label>
                        @if($adAccounts && $adAccounts->isNotEmpty())
                            {{-- Custom dropdown for better display --}}
                            <button type="button"
                                    @click="adAccountDropdownOpen = !adAccountDropdownOpen"
                                    @click.away="adAccountDropdownOpen = false"
                                    class="w-full rounded-md border border-gray-300 bg-white shadow-sm px-3 py-2 text-start focus:border-blue-500 focus:ring-1 focus:ring-blue-500 text-sm flex items-center justify-between min-h-[52px]">
                                <span x-show="!form.ad_account_id" class="text-gray-500">{{ __('profiles.select_ad_account') }}</span>
                                <template x-if="form.ad_account_id">
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-gray-900 truncate" x-text="adAccountsData.find(a => a.id === form.ad_account_id)?.account_name || 'Ad Account'"></div>
                                        <div class="text-xs text-gray-500 font-mono" x-text="form.ad_account_id.replace('act_', '')"></div>
                                    </div>
                                </template>
                                <i class="fas fa-chevron-down text-gray-400 ms-2 text-xs flex-shrink-0"></i>
                            </button>
                            {{-- Dropdown list --}}
                            <div x-show="adAccountDropdownOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute z-50 mt-1 w-full bg-white rounded-md shadow-lg border border-gray-200 max-h-60 overflow-auto">
                                @foreach($adAccounts as $account)
                                    <button type="button"
                                            @click="form.ad_account_id = '{{ $account->id }}'; adAccountDropdownOpen = false; onAdAccountChange();"
                                            :class="form.ad_account_id === '{{ $account->id }}' ? 'bg-blue-50 border-s-2 border-blue-500' : 'hover:bg-gray-50'"
                                            class="w-full px-3 py-2 text-start border-b border-gray-100 last:border-b-0">
                                        <div class="font-medium text-gray-900 text-sm">{{ $account->account_name }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">
                                            <span class="font-mono">{{ str_replace('act_', '', $account->id) }}</span>
                                            <span class="mx-1">•</span>
                                            <span>{{ ucfirst($account->platform) }}</span>
                                            <span class="mx-1">•</span>
                                            <span>{{ $account->currency ?? 'USD' }}</span>
                                        </div>
                                    </button>
                                @endforeach
                            </div>
                            {{-- Hidden input for form validation --}}
                            <input type="hidden" x-model="form.ad_account_id" required>
                        @else
                            {{-- Empty state --}}
                            <div class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-500">
                                {{ __('profiles.no_ad_accounts_available') }}
                            </div>
                            <p class="mt-1 text-xs text-yellow-600">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="underline hover:text-yellow-700">
                                    {{ __('profiles.connect_ad_account_first') }}
                                </a>
                            </p>
                        @endif
                    </div>

                    {{-- Campaign Objective --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.campaign_objective') }}</label>
                        <select x-model="form.objective"
                                @change="onObjectiveChange()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('profiles.select_objective') }}</option>
                            <template x-for="obj in objectives" :key="obj.id">
                                <option :value="obj.id" x-text="obj.name"></option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-gray-500" x-text="objectives.find(o => o.id === form.objective)?.description || ''"></p>
                    </div>

                    {{-- Destination Type Selection (Shows after objective is selected) --}}
                    <div x-show="requiresDestination && form.objective" x-transition x-cloak class="border-t border-gray-200 pt-4 mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            {{ __('profiles.destination_type') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-gray-500 mb-3">{{ __('profiles.destination_type_description') }}</p>

                        {{-- Loading state --}}
                        <div x-show="loadingMessagingAccounts" class="text-center py-4">
                            <i class="fas fa-spinner fa-spin text-blue-500 me-2"></i>
                            <span class="text-sm text-gray-600">{{ __('profiles.loading_messaging_accounts') }}...</span>
                        </div>

                        {{-- Non-messaging destination type cards (single-select) --}}
                        <div x-show="!loadingMessagingAccounts && nonMessagingDestinationTypes.length > 0" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            <template x-for="destType in nonMessagingDestinationTypes" :key="destType.id">
                                <button type="button"
                                        @click="selectDestinationType(destType)"
                                        :class="form.destination_type === destType.id ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-500' : 'border-gray-200 hover:border-blue-300 hover:bg-gray-50'"
                                        class="relative flex flex-col items-center p-4 border-2 rounded-lg transition-all duration-200 focus:outline-none">
                                    <div :class="form.destination_type === destType.id ? 'text-blue-600' : 'text-gray-400'"
                                         class="text-2xl mb-2">
                                        <i :class="'fab ' + destType.icon" x-show="destType.icon?.startsWith('fa-')"></i>
                                        <i :class="'fas ' + destType.icon" x-show="!destType.icon?.startsWith('fa-')"></i>
                                    </div>
                                    <span :class="form.destination_type === destType.id ? 'text-blue-700 font-medium' : 'text-gray-700'"
                                          class="text-sm text-center" x-text="destType.name"></span>
                                    {{-- Selected checkmark --}}
                                    <div x-show="form.destination_type === destType.id"
                                         class="absolute top-1 end-1 text-blue-500">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </button>
                            </template>
                        </div>

                        {{-- Messaging destination type cards (multi-select) --}}
                        <div x-show="!loadingMessagingAccounts && messagingDestinationTypes.length > 0" class="mt-4" x-cloak>
                            <p class="text-xs text-gray-500 mb-2">{{ __('profiles.messaging_apps_multiselect') }}</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <template x-for="destType in messagingDestinationTypes" :key="destType.id">
                                    <button type="button"
                                            @click="toggleMessagingDestination(destType.id)"
                                            :class="isMessagingDestinationSelected(destType.id) ? 'border-green-500 bg-green-50 ring-2 ring-green-500' : 'border-gray-200 hover:border-green-300 hover:bg-gray-50'"
                                            class="relative flex flex-col items-center p-4 border-2 rounded-lg transition-all duration-200 focus:outline-none">
                                        <div :class="isMessagingDestinationSelected(destType.id) ? 'text-green-600' : 'text-gray-400'"
                                             class="text-2xl mb-2">
                                            <i :class="'fab ' + destType.icon" x-show="destType.icon?.startsWith('fa-')"></i>
                                            <i :class="'fas ' + destType.icon" x-show="!destType.icon?.startsWith('fa-')"></i>
                                        </div>
                                        <span :class="isMessagingDestinationSelected(destType.id) ? 'text-green-700 font-medium' : 'text-gray-700'"
                                              class="text-sm text-center" x-text="destType.name"></span>
                                        {{-- Selected checkmark (shows checkbox style for multi-select) --}}
                                        <div class="absolute top-1 end-1"
                                             :class="isMessagingDestinationSelected(destType.id) ? 'text-green-500' : 'text-gray-300'">
                                            <i :class="isMessagingDestinationSelected(destType.id) ? 'fas fa-check-square' : 'far fa-square'"></i>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        {{-- Destination-specific fields for non-messaging destinations --}}
                        <div x-show="form.destination_type" x-transition class="mt-4 space-y-3">

                            {{-- Website URL field --}}
                            <div x-show="selectedDestinationTypeDetails?.requires?.includes('url')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('profiles.destination_url') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="url"
                                       x-model="form.destination_url"
                                       placeholder="https://example.com"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Phone Number field --}}
                            <div x-show="selectedDestinationTypeDetails?.requires?.includes('phone_number')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('profiles.phone_number') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="tel"
                                       x-model="form.phone_number"
                                       placeholder="+1234567890"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- App ID field --}}
                            <div x-show="selectedDestinationTypeDetails?.requires?.includes('app_id')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('profiles.app_id') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       x-model="form.app_id"
                                       placeholder="{{ __('profiles.enter_app_id') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            </div>

                            {{-- Form ID field (Lead Gen Forms) --}}
                            <div x-show="selectedDestinationTypeDetails?.requires?.includes('form_id')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ __('profiles.lead_form') }}
                                </label>
                                <input type="text"
                                       x-model="form.form_id"
                                       placeholder="{{ __('profiles.enter_form_id') }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                <p class="mt-1 text-xs text-gray-500">{{ __('profiles.form_id_hint') }}</p>
                            </div>
                        </div>

                        {{-- Messaging destination fields (shown when any messaging destination is selected) --}}
                        <div x-show="form.messaging_destinations.length > 0" x-transition class="mt-4 space-y-3 p-3 bg-green-50 rounded-lg border border-green-200">
                            <p class="text-xs font-medium text-green-700 mb-2">
                                <i class="fas fa-comments me-1"></i>
                                {{ __('profiles.selected_messaging_apps') }}: <span x-text="form.messaging_destinations.length"></span>
                            </p>

                            {{-- WhatsApp Number field (shown when WHATSAPP is selected) --}}
                            <div x-show="isMessagingDestinationSelected('WHATSAPP')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fab fa-whatsapp text-green-500 me-1"></i>
                                    {{ __('profiles.whatsapp_number') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <select x-model="form.whatsapp_number_id"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                    <option value="">{{ __('profiles.select_whatsapp_number') }}</option>
                                    <template x-for="account in messagingAccounts.whatsapp" :key="account.id">
                                        <option :value="account.id" x-text="account.name + (account.phone_number ? ' (' + account.phone_number + ')' : '')"></option>
                                    </template>
                                </select>
                                <p x-show="messagingAccounts.whatsapp.length === 0" class="mt-1 text-xs text-yellow-600">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ __('profiles.no_whatsapp_numbers_found') }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="text-blue-600 hover:underline">
                                        <i class="fas fa-plus-circle me-1"></i>{{ __('profiles.connect_new_whatsapp') }}
                                    </a>
                                </p>
                            </div>

                            {{-- Messenger / Page ID field (shown when MESSENGER is selected) --}}
                            <div x-show="isMessagingDestinationSelected('MESSENGER')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fab fa-facebook-messenger text-blue-500 me-1"></i>
                                    {{ __('profiles.facebook_page') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <select x-model="form.page_id"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                    <option value="">{{ __('profiles.select_facebook_page') }}</option>
                                    <template x-for="page in messagingAccounts.messenger" :key="page.id">
                                        <option :value="page.id" x-text="page.name || ('Page ' + page.id)"></option>
                                    </template>
                                </select>
                                <p x-show="messagingAccounts.messenger.length === 0" class="mt-1 text-xs text-yellow-600">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ __('profiles.no_messenger_pages_found') }}
                                </p>
                            </div>

                            {{-- Instagram Direct Account field (shown when INSTAGRAM_DIRECT is selected) --}}
                            <div x-show="isMessagingDestinationSelected('INSTAGRAM_DIRECT')">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    <i class="fab fa-instagram text-pink-500 me-1"></i>
                                    {{ __('profiles.instagram_account') }}
                                    <span class="text-red-500">*</span>
                                </label>
                                <select x-model="form.instagram_account_id"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 text-sm">
                                    <option value="">{{ __('profiles.select_instagram_account') }}</option>
                                    <template x-for="account in messagingAccounts.instagram_dm" :key="account.id">
                                        <option :value="account.id" x-text="account.name || account.username || ('Account ' + account.id)"></option>
                                    </template>
                                </select>
                                <p x-show="messagingAccounts.instagram_dm.length === 0" class="mt-1 text-xs text-yellow-600">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    {{ __('profiles.no_instagram_accounts_found') }}
                                </p>
                            </div>
                        </div>
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

                        {{-- Connection Error Alert --}}
                        <div x-show="connectionError" x-cloak class="mt-3 rounded-md bg-yellow-50 p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ms-3">
                                    <p class="text-sm text-yellow-700" x-text="connectionError"></p>
                                    <p class="text-xs text-yellow-600 mt-1">
                                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="underline hover:text-yellow-800">
                                            {{ __('profiles.connect_ad_account_first') }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div x-show="showAudienceSection" x-cloak class="mt-4 space-y-4">
                            {{-- Custom Audiences --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.custom_audiences') }}</label>
                                <div class="text-xs text-gray-500 mb-1" x-show="customAudiences.length > 0">
                                    <span x-text="customAudiences.length"></span> {{ __('profiles.audiences_available') }}
                                </div>
                                <select multiple
                                        x-model="form.custom_audiences"
                                        :disabled="!form.ad_account_id || loadingAudiences"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-32">
                                    <template x-if="loadingAudiences">
                                        <option disabled>{{ __('profiles.loading_audiences') }}...</option>
                                    </template>
                                    <template x-if="!loadingAudiences && customAudiences.length === 0">
                                        <option disabled>{{ __('profiles.no_custom_audiences') }}</option>
                                    </template>
                                    <template x-for="audience in customAudiences" :key="audience.id">
                                        <option :value="audience.id" x-text="audience.name + (audience.size ? ' (' + Number(audience.size).toLocaleString() + ')' : '')"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ __('profiles.custom_audiences_hint') }}</p>
                            </div>

                            {{-- Lookalike Audiences --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.lookalike_audiences') }}</label>
                                <div class="text-xs text-gray-500 mb-1" x-show="lookalikeAudiences.length > 0">
                                    <span x-text="lookalikeAudiences.length"></span> {{ __('profiles.audiences_available') }}
                                </div>
                                <select multiple
                                        x-model="form.lookalike_audiences"
                                        :disabled="!form.ad_account_id || loadingAudiences"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-24">
                                    <template x-if="!loadingAudiences && lookalikeAudiences.length === 0">
                                        <option disabled>{{ __('profiles.no_lookalike_audiences') }}</option>
                                    </template>
                                    <template x-for="audience in lookalikeAudiences" :key="audience.id">
                                        <option :value="audience.id" x-text="audience.name + (audience.ratio ? ' (' + audience.ratio + '%)' : '')"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ __('profiles.lookalike_audiences_hint') }}</p>
                            </div>

                            {{-- Excluded Audiences --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.excluded_audiences') }}</label>
                                <div class="text-xs text-gray-500 mb-1" x-show="allAudiences.length > 0">
                                    <span x-text="allAudiences.length"></span> {{ __('profiles.audiences_available') }}
                                </div>
                                <select multiple
                                        x-model="form.excluded_audiences"
                                        :disabled="!form.ad_account_id || loadingAudiences"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-24">
                                    <template x-if="!loadingAudiences && allAudiences.length === 0">
                                        <option disabled>{{ __('profiles.no_audiences_to_exclude') }}</option>
                                    </template>
                                    <template x-for="audience in allAudiences" :key="audience.id">
                                        <option :value="audience.id" x-text="audience.name + (audience.size ? ' (' + Number(audience.size).toLocaleString() + ')' : '')"></option>
                                    </template>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">{{ __('profiles.excluded_audiences_hint') }}</p>
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
                            {{-- Interests Autocomplete --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.interests') }}</label>

                                {{-- Selected Interests Tags --}}
                                <div class="flex flex-wrap gap-2 mb-2" x-show="selectedInterests.length > 0">
                                    <template x-for="interest in selectedInterests" :key="interest.id">
                                        <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">
                                            <span x-text="interest.name"></span>
                                            <span class="ms-1 text-blue-600" x-text="'(' + formatAudienceSize(interest.audience_size_lower_bound) + ')'"></span>
                                            <button type="button" @click="removeInterest(interest.id)" class="ms-1 text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                {{-- Search Input --}}
                                <div class="relative">
                                    <input type="text"
                                           x-model="interestSearch"
                                           @input="searchInterests()"
                                           @focus="searchInterests()"
                                           @blur="setTimeout(() => showInterestDropdown = false, 200)"
                                           :disabled="!form.ad_account_id"
                                           placeholder="{{ __('profiles.search_interests') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <span x-show="searchingInterests" class="absolute end-3 top-1/2 -translate-y-1/2">
                                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                    </span>
                                </div>

                                {{-- Results Dropdown --}}
                                <div x-show="showInterestDropdown && interestResults.length > 0"
                                     x-transition
                                     class="absolute z-20 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <template x-for="interest in interestResults" :key="interest.id">
                                        <button type="button"
                                                @mousedown.prevent="selectInterest(interest)"
                                                class="w-full text-start px-3 py-2 hover:bg-blue-50 flex justify-between items-center border-b border-gray-100 last:border-0">
                                            <div>
                                                <span class="text-sm" x-text="interest.name"></span>
                                                <span class="text-xs text-gray-400 block" x-text="interest.path?.join(' > ') || ''"></span>
                                            </div>
                                            <span class="text-xs text-gray-500 font-medium" x-text="formatAudienceSize(interest.audience_size_lower_bound)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Behaviors Selection --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.behaviors') }}</label>
                                <div x-show="loadingBehaviors" class="text-sm text-gray-500">
                                    <i class="fas fa-spinner fa-spin me-1"></i>{{ __('profiles.loading') }}...
                                </div>
                                <div x-show="!loadingBehaviors && behaviorResults.length > 0" class="max-h-40 overflow-y-auto border rounded-lg p-2 space-y-1">
                                    <template x-for="behavior in behaviorResults" :key="behavior.id">
                                        <label class="flex items-center p-1 hover:bg-gray-50 rounded cursor-pointer text-sm">
                                            <input type="checkbox"
                                                   :checked="isBehaviorSelected(behavior.id)"
                                                   @change="toggleBehavior(behavior)"
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ms-2" x-text="behavior.name"></span>
                                            <span class="ms-auto text-xs text-gray-400" x-text="formatAudienceSize(behavior.audience_size_lower_bound)"></span>
                                        </label>
                                    </template>
                                </div>
                                <p x-show="!loadingBehaviors && behaviorResults.length === 0 && form.ad_account_id" class="text-xs text-gray-500">
                                    {{ __('profiles.no_behaviors_available') }}
                                </p>
                            </div>

                            {{-- Locations Autocomplete (replaces Countries/Cities) --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.locations') }}</label>

                                {{-- Selected Locations Tags --}}
                                <div class="flex flex-wrap gap-2 mb-2" x-show="selectedLocations.length > 0">
                                    <template x-for="location in selectedLocations" :key="location.key">
                                        <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <span x-text="location.name"></span>
                                            <span class="ms-1 text-green-600" x-text="location.country_code ? '(' + location.country_code + ')' : ''"></span>
                                            <button type="button" @click="removeLocation(location.key)" class="ms-1 text-green-600 hover:text-green-800">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                {{-- Search Input --}}
                                <div class="relative">
                                    <input type="text"
                                           x-model="locationSearch"
                                           @input="searchLocations()"
                                           @focus="searchLocations()"
                                           @blur="setTimeout(() => showLocationDropdown = false, 200)"
                                           :disabled="!form.ad_account_id"
                                           placeholder="{{ __('profiles.search_locations') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <span x-show="searchingLocations" class="absolute end-3 top-1/2 -translate-y-1/2">
                                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                    </span>
                                </div>

                                {{-- Results Dropdown --}}
                                <div x-show="showLocationDropdown && locationResults.length > 0"
                                     x-transition
                                     class="absolute z-20 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <template x-for="location in locationResults" :key="location.key">
                                        <button type="button"
                                                @mousedown.prevent="selectLocation(location)"
                                                class="w-full text-start px-3 py-2 hover:bg-green-50 flex items-center border-b border-gray-100 last:border-0">
                                            <i class="fas fa-map-marker-alt text-gray-400 me-2"></i>
                                            <div>
                                                <span class="text-sm" x-text="location.name"></span>
                                                <span class="text-xs text-gray-400 block"
                                                      x-text="[location.region, location.country_name].filter(Boolean).join(', ')"></span>
                                            </div>
                                            <span class="ms-auto text-xs text-gray-500 capitalize bg-gray-100 px-2 py-0.5 rounded" x-text="location.type"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            {{-- Work Positions Autocomplete --}}
                            <div class="relative">
                                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.work_positions') }}</label>

                                {{-- Selected Work Positions Tags --}}
                                <div class="flex flex-wrap gap-2 mb-2" x-show="selectedWorkPositions.length > 0">
                                    <template x-for="position in selectedWorkPositions" :key="position.id">
                                        <span class="inline-flex items-center px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded-full">
                                            <i class="fas fa-briefcase me-1"></i>
                                            <span x-text="position.name"></span>
                                            <button type="button" @click="removeWorkPosition(position.id)" class="ms-1 text-purple-600 hover:text-purple-800">
                                                <i class="fas fa-times text-xs"></i>
                                            </button>
                                        </span>
                                    </template>
                                </div>

                                {{-- Search Input --}}
                                <div class="relative">
                                    <input type="text"
                                           x-model="workPositionSearch"
                                           @input="searchWorkPositions()"
                                           @focus="searchWorkPositions()"
                                           @blur="setTimeout(() => showWorkPositionDropdown = false, 200)"
                                           :disabled="!form.ad_account_id"
                                           placeholder="{{ __('profiles.search_work_positions') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <span x-show="searchingWorkPositions" class="absolute end-3 top-1/2 -translate-y-1/2">
                                        <i class="fas fa-spinner fa-spin text-gray-400"></i>
                                    </span>
                                </div>

                                {{-- Results Dropdown --}}
                                <div x-show="showWorkPositionDropdown && workPositionResults.length > 0"
                                     x-transition
                                     class="absolute z-20 w-full mt-1 bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                    <template x-for="position in workPositionResults" :key="position.id">
                                        <button type="button"
                                                @mousedown.prevent="selectWorkPosition(position)"
                                                class="w-full text-start px-3 py-2 hover:bg-purple-50 flex items-center border-b border-gray-100 last:border-0">
                                            <i class="fas fa-briefcase text-gray-400 me-2"></i>
                                            <span class="text-sm" x-text="position.name"></span>
                                        </button>
                                    </template>
                                </div>
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
        loadingMessagingAccounts: false,
        validatingBudget: false,
        showAudienceSection: false,
        showDetailedTargeting: false,
        showPlacementsSection: false,
        showB2BSection: false,

        // Platform configuration from API
        platformConfig: null,

        // Destination types for selected objective
        selectedDestinationType: null,
        messagingAccounts: { whatsapp: [], messenger: [], instagram_dm: [] },

        // Messaging destination IDs that support multi-select
        messagingDestinationIds: ['MESSENGER', 'WHATSAPP', 'INSTAGRAM_DIRECT'],

        // Ad accounts data for display in custom dropdown
        adAccountsData: @json($adAccounts ?? collect()),

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
            genders: '',
            min_age: '',
            max_age: '',
            // Destination type fields
            destination_type: '',
            destination_url: '',
            whatsapp_number_id: '',
            page_id: '',
            instagram_account_id: '',
            phone_number: '',
            form_id: '',
            app_id: '',
            // Multi-select messaging destinations (Messenger, WhatsApp, Instagram Direct)
            messaging_destinations: []
        },

        // Autocomplete selected items (arrays of objects)
        selectedInterests: [],
        selectedBehaviors: [],
        selectedLocations: [],
        selectedWorkPositions: [],

        // Autocomplete search state
        interestSearch: '',
        interestResults: [],
        searchingInterests: false,
        showInterestDropdown: false,

        behaviorResults: [],
        loadingBehaviors: false,
        showBehaviorDropdown: false,

        locationSearch: '',
        locationResults: [],
        searchingLocations: false,
        showLocationDropdown: false,

        workPositionSearch: '',
        workPositionResults: [],
        searchingWorkPositions: false,
        showWorkPositionDropdown: false,

        // Connection error message
        connectionError: null,

        // Debounce timers
        _interestSearchTimer: null,
        _locationSearchTimer: null,
        _workPositionSearchTimer: null,

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

        // Destination type computed properties
        get currentDestinationTypes() {
            if (!this.form.objective || !this.platformConfig?.objectives) {
                return [];
            }
            const objective = this.platformConfig.objectives.find(o => o.id === this.form.objective);
            return objective?.destination_types || [];
        },

        get requiresDestination() {
            return this.currentDestinationTypes.length > 0;
        },

        get selectedDestinationTypeDetails() {
            if (!this.form.destination_type) return null;
            return this.currentDestinationTypes.find(dt => dt.id === this.form.destination_type);
        },

        // Check if destination type is a messaging type (supports multi-select)
        isMessagingDestination(destTypeId) {
            return this.messagingDestinationIds.includes(destTypeId);
        },

        // Get non-messaging destination types
        get nonMessagingDestinationTypes() {
            return this.currentDestinationTypes.filter(dt => !this.isMessagingDestination(dt.id));
        },

        // Get messaging destination types
        get messagingDestinationTypes() {
            return this.currentDestinationTypes.filter(dt => this.isMessagingDestination(dt.id));
        },

        // Check if we have any messaging destination options
        get hasMessagingOptions() {
            return this.messagingDestinationTypes.length > 0;
        },

        // Check if a specific messaging destination is selected
        isMessagingDestinationSelected(destId) {
            return this.form.messaging_destinations.includes(destId);
        },

        // Check if any messaging destination requires a specific field
        anyMessagingRequires(fieldName) {
            return this.form.messaging_destinations.some(destId => {
                const destType = this.messagingDestinationTypes.find(dt => dt.id === destId);
                return destType?.requires?.includes(fieldName);
            });
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

        onObjectiveChange() {
            // Reset destination type when objective changes
            this.form.destination_type = '';
            this.form.destination_url = '';
            this.form.whatsapp_number_id = '';
            this.form.page_id = '';
            this.form.instagram_account_id = '';
            this.form.phone_number = '';
            this.form.form_id = '';
            this.form.app_id = '';
            this.form.messaging_destinations = [];
            this.selectedDestinationType = null;

            // Load messaging accounts if needed for the new objective
            if (this.requiresDestination) {
                const hasMessagingOptions = this.currentDestinationTypes.some(dt =>
                    ['MESSENGER', 'WHATSAPP', 'INSTAGRAM_DIRECT'].includes(dt.id)
                );
                if (hasMessagingOptions) {
                    this.loadMessagingAccounts();
                }
            }
        },

        selectDestinationType(destinationType) {
            // For messaging destinations, use multi-select toggle instead
            if (this.isMessagingDestination(destinationType.id)) {
                this.toggleMessagingDestination(destinationType.id);
                return;
            }

            // For non-messaging destinations, single-select behavior
            this.form.destination_type = destinationType.id;
            this.selectedDestinationType = destinationType;
            // Clear messaging destinations when selecting a non-messaging destination
            this.form.messaging_destinations = [];

            // Clear fields not relevant to this destination type
            const requires = destinationType.requires || [];
            if (!requires.includes('url')) this.form.destination_url = '';
            if (!requires.includes('whatsapp_number')) this.form.whatsapp_number_id = '';
            if (!requires.includes('page_id')) this.form.page_id = '';
            if (!requires.includes('instagram_account')) this.form.instagram_account_id = '';
            if (!requires.includes('phone_number')) this.form.phone_number = '';
            if (!requires.includes('form_id')) this.form.form_id = '';
            if (!requires.includes('app_id')) this.form.app_id = '';
        },

        // Toggle messaging destination (multi-select)
        toggleMessagingDestination(destId) {
            const idx = this.form.messaging_destinations.indexOf(destId);
            if (idx >= 0) {
                // Remove it
                this.form.messaging_destinations.splice(idx, 1);
                // Clear related fields if removing
                if (destId === 'WHATSAPP') this.form.whatsapp_number_id = '';
                if (destId === 'MESSENGER') this.form.page_id = '';
                if (destId === 'INSTAGRAM_DIRECT') this.form.instagram_account_id = '';
            } else {
                // Add it
                this.form.messaging_destinations.push(destId);
            }

            // Clear single destination type when using multi-select messaging
            if (this.form.messaging_destinations.length > 0) {
                this.form.destination_type = '';
                this.selectedDestinationType = null;
            }
        },

        async loadMessagingAccounts() {
            this.loadingMessagingAccounts = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/messaging-accounts`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.messagingAccounts = data.data.accounts || { whatsapp: [], messenger: [], instagram_dm: [] };
                }
            } catch (error) {
                console.error('Error loading messaging accounts:', error);
            }
            this.loadingMessagingAccounts = false;
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
                this.connectionError = null;
                return;
            }

            this.loadingAudiences = true;
            this.connectionError = null;
            try {
                // Use Meta API endpoint instead of database
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/meta-audiences?ad_account_id=${this.form.ad_account_id}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    }
                });
                const data = await response.json();
                if (data.success && data.data) {
                    this.customAudiences = data.data.custom || [];
                    this.lookalikeAudiences = data.data.lookalike || [];
                    this.allAudiences = [...this.customAudiences, ...this.lookalikeAudiences];
                    this.connectionError = null;
                } else if (!data.success) {
                    // Show connection error message
                    this.connectionError = data.message || '{{ __("profiles.ad_account_not_connected") }}';
                    this.customAudiences = [];
                    this.lookalikeAudiences = [];
                    this.allAudiences = [];
                }
            } catch (error) {
                console.error('Error loading audiences:', error);
                this.connectionError = '{{ __("profiles.audiences_fetch_failed") }}';
            }
            this.loadingAudiences = false;

            // Also load behaviors when ad account changes
            this.loadBehaviors();
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

        // Format audience size for display (e.g., "467M", "1.2B")
        formatAudienceSize(size) {
            if (!size) return '';
            if (size >= 1000000000) return (size / 1000000000).toFixed(1) + 'B';
            if (size >= 1000000) return (size / 1000000).toFixed(0) + 'M';
            if (size >= 1000) return (size / 1000).toFixed(0) + 'K';
            return size.toString();
        },

        // Interest search with debounce
        searchInterests() {
            if (this.interestSearch.length < 1) {
                this.interestResults = [];
                this.showInterestDropdown = false;
                return;
            }

            if (this._interestSearchTimer) clearTimeout(this._interestSearchTimer);
            this._interestSearchTimer = setTimeout(async () => {
                this.searchingInterests = true;
                try {
                    const response = await fetch(
                        `/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/search-interests?q=${encodeURIComponent(this.interestSearch)}&ad_account_id=${this.form.ad_account_id}`,
                        {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            }
                        }
                    );
                    const data = await response.json();
                    if (data.success) {
                        this.interestResults = (data.data || []).filter(
                            i => !this.selectedInterests.find(s => s.id === i.id)
                        );
                        this.showInterestDropdown = this.interestResults.length > 0;
                    }
                } catch (error) {
                    console.error('Interest search error:', error);
                }
                this.searchingInterests = false;
            }, 300);
        },

        selectInterest(interest) {
            if (!this.selectedInterests.find(i => i.id === interest.id)) {
                this.selectedInterests.push(interest);
            }
            this.interestSearch = '';
            this.interestResults = [];
            this.showInterestDropdown = false;
        },

        removeInterest(interestId) {
            this.selectedInterests = this.selectedInterests.filter(i => i.id !== interestId);
        },

        // Location search with debounce
        searchLocations() {
            if (this.locationSearch.length < 1) {
                this.locationResults = [];
                this.showLocationDropdown = false;
                return;
            }

            if (this._locationSearchTimer) clearTimeout(this._locationSearchTimer);
            this._locationSearchTimer = setTimeout(async () => {
                this.searchingLocations = true;
                try {
                    const response = await fetch(
                        `/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/search-locations?q=${encodeURIComponent(this.locationSearch)}&ad_account_id=${this.form.ad_account_id}`,
                        {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            }
                        }
                    );
                    const data = await response.json();
                    if (data.success) {
                        this.locationResults = (data.data || []).filter(
                            loc => !this.selectedLocations.find(s => s.key === loc.key)
                        );
                        this.showLocationDropdown = this.locationResults.length > 0;
                    }
                } catch (error) {
                    console.error('Location search error:', error);
                }
                this.searchingLocations = false;
            }, 300);
        },

        selectLocation(location) {
            if (!this.selectedLocations.find(l => l.key === location.key)) {
                this.selectedLocations.push(location);
            }
            this.locationSearch = '';
            this.locationResults = [];
            this.showLocationDropdown = false;
        },

        removeLocation(locationKey) {
            this.selectedLocations = this.selectedLocations.filter(l => l.key !== locationKey);
        },

        // Work position search with debounce
        searchWorkPositions() {
            if (this.workPositionSearch.length < 1) {
                this.workPositionResults = [];
                this.showWorkPositionDropdown = false;
                return;
            }

            if (this._workPositionSearchTimer) clearTimeout(this._workPositionSearchTimer);
            this._workPositionSearchTimer = setTimeout(async () => {
                this.searchingWorkPositions = true;
                try {
                    const response = await fetch(
                        `/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/search-work-positions?q=${encodeURIComponent(this.workPositionSearch)}&ad_account_id=${this.form.ad_account_id}`,
                        {
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            }
                        }
                    );
                    const data = await response.json();
                    if (data.success) {
                        this.workPositionResults = (data.data || []).filter(
                            pos => !this.selectedWorkPositions.find(s => s.id === pos.id)
                        );
                        this.showWorkPositionDropdown = this.workPositionResults.length > 0;
                    }
                } catch (error) {
                    console.error('Work position search error:', error);
                }
                this.searchingWorkPositions = false;
            }, 300);
        },

        selectWorkPosition(position) {
            if (!this.selectedWorkPositions.find(p => p.id === position.id)) {
                this.selectedWorkPositions.push(position);
            }
            this.workPositionSearch = '';
            this.workPositionResults = [];
            this.showWorkPositionDropdown = false;
        },

        removeWorkPosition(positionId) {
            this.selectedWorkPositions = this.selectedWorkPositions.filter(p => p.id !== positionId);
        },

        // Load behaviors list (no search, just get all)
        async loadBehaviors() {
            if (!this.form.ad_account_id) {
                this.behaviorResults = [];
                return;
            }

            this.loadingBehaviors = true;
            try {
                const response = await fetch(
                    `/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/search-behaviors?ad_account_id=${this.form.ad_account_id}`,
                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        }
                    }
                );
                const data = await response.json();
                if (data.success) {
                    this.behaviorResults = data.data || [];
                }
            } catch (error) {
                console.error('Behaviors load error:', error);
            }
            this.loadingBehaviors = false;
        },

        toggleBehavior(behavior) {
            const idx = this.selectedBehaviors.findIndex(b => b.id === behavior.id);
            if (idx >= 0) {
                this.selectedBehaviors.splice(idx, 1);
            } else {
                this.selectedBehaviors.push(behavior);
            }
        },

        isBehaviorSelected(behaviorId) {
            return this.selectedBehaviors.some(b => b.id === behaviorId);
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
                        // Destination type settings
                        destination_type: this.form.destination_type,
                        destination_url: this.form.destination_url,
                        whatsapp_number_id: this.form.whatsapp_number_id,
                        page_id: this.form.page_id,
                        instagram_account_id: this.form.instagram_account_id,
                        phone_number: this.form.phone_number,
                        form_id: this.form.form_id,
                        app_id: this.form.app_id,
                        // Multi-select messaging destinations
                        messaging_destinations: this.form.messaging_destinations,
                        targeting_options: {
                            custom_audiences: this.form.custom_audiences,
                            lookalike_audiences: this.form.lookalike_audiences,
                            excluded_audiences: this.form.excluded_audiences,
                            // New API-backed targeting options
                            interests: this.selectedInterests.map(i => ({ id: i.id, name: i.name })),
                            behaviors: this.selectedBehaviors.map(b => ({ id: b.id, name: b.name })),
                            locations: this.selectedLocations.map(l => ({ key: l.key, name: l.name, type: l.type })),
                            work_positions: this.selectedWorkPositions.map(p => ({ id: p.id, name: p.name })),
                            // Demographics
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
