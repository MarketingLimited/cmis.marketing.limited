@extends('layouts.admin')

@section('page-title', __('campaigns.create_campaign'))
@section('page-subtitle', __('campaigns.create_campaign_subtitle'))

@section('content')
<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('campaigns.store') }}" x-data="campaignForm()" @submit="validateForm">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-indigo-600 ml-2"></i>
                    {{ __('campaigns.basic_information') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.campaign_name_required') }}</label>
                        <input type="text" name="campaign_name" x-model="form.campaign_name" required
                               value="{{ old('campaign_name') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('campaign_name')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.campaign_type_required') }}</label>
                        <select name="campaign_type" x-model="form.campaign_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('campaigns.select_type') }}</option>
                            <option value="awareness">{{ __('campaigns.type_awareness') }}</option>
                            <option value="consideration">{{ __('campaigns.type_consideration') }}</option>
                            <option value="conversion">{{ __('campaigns.type_conversion') }}</option>
                            <option value="retention">{{ __('campaigns.type_retention') }}</option>
                            <option value="engagement">{{ __('campaigns.type_engagement') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.organization_required') }}</label>
                        <select name="org_id" x-model="form.org_id" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('campaigns.select_organization') }}</option>
                            @foreach($organizations ?? [] as $org)
                                <option value="{{ $org->org_id }}">{{ $org->org_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.description') }}</label>
                        <textarea name="description" x-model="form.description" rows="3"
                                  value="{{ old('description') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Goals & KPIs -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bullseye text-indigo-600 ml-2"></i>
                    {{ __('campaigns.goals_kpis') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.campaign_goals_required') }}</label>
                        <textarea name="goals" x-model="form.goals" rows="3" required
                                  placeholder="{{ __('campaigns.campaign_goals_placeholder') }}"
                                  value="{{ old('goals') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('goals') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.primary_kpi') }}</label>
                            <select name="primary_kpi" x-model="form.primary_kpi"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">{{ __('campaigns.select_kpi') }}</option>
                                <option value="impressions">{{ __('campaigns.kpi_impressions') }}</option>
                                <option value="clicks">{{ __('campaigns.kpi_clicks') }}</option>
                                <option value="conversions">{{ __('campaigns.kpi_conversions') }}</option>
                                <option value="engagement">{{ __('campaigns.kpi_engagement') }}</option>
                                <option value="reach">{{ __('campaigns.kpi_reach') }}</option>
                                <option value="roi">{{ __('campaigns.kpi_roi') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.target_value') }}</label>
                            <input type="number" name="target_value" x-model="form.target_value"
                                   value="{{ old('target_value') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.measurement_unit') }}</label>
                            <input type="text" name="measurement_unit" x-model="form.measurement_unit"
                                   placeholder="{{ __('campaigns.measurement_unit_placeholder') }}"
                                   value="{{ old('measurement_unit') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Budget & Timeline -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-wallet text-indigo-600 ml-2"></i>
                    {{ __('campaigns.budget_timeline') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.total_budget_required') }}</label>
                        <input type="number" name="budget" x-model="form.budget" step="0.01" min="0" required
                               value="{{ old('budget') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.currency') }}</label>
                        <select name="currency" x-model="form.currency"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="SAR">ريال سعودي (SAR)</option>
                            <option value="USD">دولار أمريكي (USD)</option>
                            <option value="EUR">يورو (EUR)</option>
                            <option value="AED">درهم إماراتي (AED)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.start_date_required') }}</label>
                        <input type="date" name="start_date" x-model="form.start_date" required
                               value="{{ old('start_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.end_date_required') }}</label>
                        <input type="date" name="end_date" x-model="form.end_date" required
                               value="{{ old('end_date') }}"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div class="md:col-span-2">
                        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-indigo-900">{{ __('campaigns.expected_duration') }}</p>
                                    <p class="text-xs text-indigo-700 mt-1">{{ __('campaigns.duration_auto_calculated') }}</p>
                                </div>
                                <template x-if="form.start_date && form.end_date">
                                    <span class="text-2xl font-bold text-indigo-600" x-text="calculateDuration() + ' {{ __('campaigns.days') }}'"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Target Audience -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-users text-indigo-600 ml-2"></i>
                    {{ __('campaigns.target_audience') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.target_audience_description') }}</label>
                        <textarea name="target_audience" x-model="form.target_audience" rows="3"
                                  placeholder="{{ __('campaigns.target_audience_placeholder') }}"
                                  value="{{ old('target_audience') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">{{ old('target_audience') }}</textarea>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.age_range') }}</label>
                            <select name="age_range" x-model="form.age_range"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">{{ __('campaigns.select_age_range') }}</option>
                                <option value="18-24">18-24</option>
                                <option value="25-34">25-34</option>
                                <option value="35-44">35-44</option>
                                <option value="45-54">45-54</option>
                                <option value="55+">55+</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.gender') }}</label>
                            <select name="gender" x-model="form.gender"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                                <option value="">{{ __('campaigns.gender_all') }}</option>
                                <option value="male">{{ __('campaigns.gender_male') }}</option>
                                <option value="female">{{ __('campaigns.gender_female') }}</option>
                                <option value="other">{{ __('campaigns.gender_other') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('campaigns.location') }}</label>
                            <input type="text" name="location" x-model="form.location"
                                   placeholder="{{ __('campaigns.location_placeholder') }}"
                                   value="{{ old('location') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status & Settings -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-cog text-indigo-600 ml-2"></i>
                    {{ __('campaigns.status_settings') }}
                </h3>

                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" checked
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="mr-2 text-sm text-gray-700">{{ __('campaigns.campaign_active') }}</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="enable_workflow" value="1" checked
                               class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="mr-2 text-sm text-gray-700">{{ __('campaigns.create_auto_workflow') }}</span>
                    </label>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="submit"
                        class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:shadow-lg transition">
                    <i class="fas fa-rocket ml-2"></i>
                    {{ __('campaigns.launch_campaign') }}
                </button>
                <a href="{{ route('campaigns.index') }}"
                   class="bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                    {{ __('campaigns.cancel') }}
                </a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function campaignForm() {
    return {
        form: {
            campaign_name: '',
            campaign_type: '',
            org_id: '',
            description: '',
            goals: '',
            primary_kpi: '',
            target_value: '',
            measurement_unit: '',
            budget: '',
            currency: 'SAR',
            start_date: '',
            end_date: '',
            target_audience: '',
            age_range: '',
            gender: '',
            location: ''
        },

        validateForm(e) {
            // Check dates
            if (this.form.start_date && this.form.end_date) {
                const start = new Date(this.form.start_date);
                const end = new Date(this.form.end_date);

                if (end <= start) {
                    alert('{{ __('campaigns.end_date_after_start') }}');
                    e.preventDefault();
                    return false;
                }
            }

            // Check budget
            if (this.form.budget && parseFloat(this.form.budget) <= 0) {
                alert('{{ __('campaigns.budget_greater_zero') }}');
                e.preventDefault();
                return false;
            }

            return true;
        },

        calculateDuration() {
            if (!this.form.start_date || !this.form.end_date) return 0;

            const start = new Date(this.form.start_date);
            const end = new Date(this.form.end_date);
            const diffTime = Math.abs(end - start);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            return diffDays;
        }
    };
}
</script>
@endpush
