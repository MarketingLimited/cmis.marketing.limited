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
             class="inline-block align-bottom bg-white rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full max-h-[90vh] overflow-y-auto"
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
                    </div>

                    {{-- Ad Account --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.ad_account') }}</label>
                        <select x-model="form.ad_account_id"
                                required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">{{ __('Select ad account') }}</option>
                            {{-- Ad accounts would be loaded dynamically --}}
                        </select>
                    </div>

                    {{-- Budget and Duration --}}
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.budget') }}</label>
                            <input type="number"
                                   x-model="form.budget_amount"
                                   min="0"
                                   step="0.01"
                                   placeholder="Budget"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.campaign_days') }}</label>
                            <input type="number"
                                   x-model="form.duration_days"
                                   min="1"
                                   placeholder="{{ __('profiles.number_of_days') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">{{ __('profiles.budget_note') }}</p>

                    {{-- Audiences --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.included_audiences') }}</label>
                        <select multiple
                                x-model="form.included_audiences"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-20">
                            {{-- Audiences would be loaded from Meta API --}}
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.excluded_audiences') }}</label>
                        <select multiple
                                x-model="form.excluded_audiences"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm h-20">
                            {{-- Audiences would be loaded from Meta API --}}
                        </select>
                    </div>

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
                                   placeholder="{{ __('profiles.min_age') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('profiles.max_age') }}</label>
                            <input type="number"
                                   x-model="form.max_age"
                                   min="13"
                                   max="65"
                                   placeholder="{{ __('profiles.max_age') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        </div>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button"
                        @click="saveBoost()"
                        :disabled="loading"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm disabled:opacity-50">
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
        form: {
            name: '',
            delay_value: 1,
            delay_unit: 'hours',
            ad_account_id: '',
            budget_amount: '',
            duration_days: 7,
            included_audiences: [],
            excluded_audiences: [],
            interests: '',
            work_positions: '',
            countries: '',
            cities: '',
            genders: '',
            min_age: '',
            max_age: ''
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
                        budget_amount: this.form.budget_amount,
                        duration_hours: this.form.duration_days * 24,
                        targeting_options: {
                            interests: this.form.interests,
                            work_positions: this.form.work_positions,
                            countries: this.form.countries,
                            cities: this.form.cities,
                            genders: this.form.genders,
                            min_age: this.form.min_age,
                            max_age: this.form.max_age
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
                        detail: { message: data.message || 'Error', type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error:', error);
            }
            this.loading = false;
        }
    };
}
</script>
