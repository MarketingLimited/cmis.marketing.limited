@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('page-title', __('briefs.create_page_title'))
@section('page-subtitle', __('briefs.create_page_subtitle'))

@section('content')
<div class="max-w-5xl mx-auto">
    <form method="POST" action="{{ route('orgs.creative.briefs.store', ['org' => $currentOrg]) }}" x-data="briefForm()" @submit="validateAndSubmit">
        @csrf

        <div class="space-y-6">
            <!-- Basic Information -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-info-circle text-indigo-600 ms-2"></i>
                    {{ __('briefs.section_basic_info') }}
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.brief_title') }} *</label>
                        <input type="text" name="brief_title" x-model="form.brief_title" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('brief_title')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.brief_type') }} *</label>
                        <select name="brief_type" x-model="form.brief_type" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('briefs.select_type') }}</option>
                            <option value="campaign">{{ __('briefs.campaign_type') }}</option>
                            <option value="content">{{ __('briefs.content_type') }}</option>
                            <option value="design">{{ __('briefs.design_type') }}</option>
                            <option value="video">{{ __('briefs.video_type') }}</option>
                            <option value="social">{{ __('briefs.social_type') }}</option>
                        </select>
                        @error('brief_type')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.campaign_linked') }}</label>
                        <select name="campaign_id" x-model="form.campaign_id"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                            <option value="">{{ __('briefs.no_campaign') }}</option>
                            @foreach($campaigns ?? [] as $campaign)
                                <option value="{{ $campaign->campaign_id }}">{{ $campaign->campaign_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.target_date') }}</label>
                        <input type="date" name="target_date" x-model="form.target_date"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.budget') }} ({{ __('common.sar') ?? 'ر.س' }})</label>
                        <input type="number" name="budget" x-model="form.budget" step="0.01" min="0"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Objectives & Strategy -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-bullseye text-indigo-600 ms-2"></i>
                    {{ __('briefs.section_objectives') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.objectives') }} *</label>
                        <textarea name="objectives" x-model="form.objectives" rows="4" required
                                  placeholder="{{ __('briefs.objectives_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                        @error('objectives')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.key_message') }}</label>
                        <textarea name="key_message" x-model="form.key_message" rows="3"
                                  placeholder="{{ __('briefs.key_message_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.creative_strategy') }}</label>
                        <textarea name="creative_strategy" x-model="form.creative_strategy" rows="4"
                                  placeholder="{{ __('briefs.creative_strategy_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Target Audience -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-users text-indigo-600 ms-2"></i>
                    {{ __('briefs.section_target_audience') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.target_audience_description') }} *</label>
                        <textarea name="target_audience" x-model="form.target_audience" rows="3" required
                                  placeholder="{{ __('briefs.target_audience_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                        @error('target_audience')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.persona') }}</label>
                        <textarea name="persona" x-model="form.persona" rows="3"
                                  placeholder="{{ __('briefs.persona_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Deliverables & Specifications -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-clipboard-list text-indigo-600 ms-2"></i>
                    {{ __('briefs.section_deliverables') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.deliverables') }}</label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="logo" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="ms-2 text-sm text-gray-700">{{ __('briefs.deliverable_logo') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="social_posts" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="ms-2 text-sm text-gray-700">{{ __('briefs.deliverable_social_posts') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="banner" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="ms-2 text-sm text-gray-700">{{ __('briefs.deliverable_banner') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="video" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="ms-2 text-sm text-gray-700">{{ __('briefs.deliverable_video') }}</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="deliverables[]" value="content" class="w-4 h-4 text-indigo-600 rounded">
                                <span class="ms-2 text-sm text-gray-700">{{ __('briefs.deliverable_content') }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.technical_specs') }}</label>
                        <textarea name="technical_specs" x-model="form.technical_specs" rows="3"
                                  placeholder="{{ __('briefs.technical_specs_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.brand_guidelines') }}</label>
                        <textarea name="brand_guidelines" x-model="form.brand_guidelines" rows="3"
                                  placeholder="{{ __('briefs.brand_guidelines_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- References & Inspiration -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-lightbulb text-indigo-600 ms-2"></i>
                    {{ __('briefs.section_references') }}
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.references') }}</label>
                        <textarea name="references" x-model="form.references" rows="3"
                                  placeholder="{{ __('briefs.references_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('briefs.avoid') }}</label>
                        <textarea name="avoid" x-model="form.avoid" rows="2"
                                  placeholder="{{ __('briefs.avoid_placeholder') }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex gap-3">
                    <button type="submit" name="status" value="review"
                            class="flex-1 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-3 rounded-lg font-medium hover:shadow-lg transition">
                        <i class="fas fa-paper-plane ms-2"></i>
                        {{ __('briefs.send_for_review') }}
                    </button>
                    <button type="submit" name="status" value="draft"
                            class="flex-1 bg-gray-100 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-200 transition">
                        <i class="fas fa-save ms-2"></i>
                        {{ __('briefs.save_as_draft') }}
                    </button>
                    <a href="{{ route('orgs.creative.briefs.index', ['org' => $currentOrg]) }}"
                       class="bg-white border border-gray-300 text-gray-700 px-8 py-3 rounded-lg font-medium hover:bg-gray-50 transition">
                        {{ __('common.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function briefForm() {
    return {
        form: {
            brief_title: '',
            brief_type: '',
            campaign_id: '',
            target_date: '',
            budget: '',
            objectives: '',
            key_message: '',
            creative_strategy: '',
            target_audience: '',
            persona: '',
            technical_specs: '',
            brand_guidelines: '',
            references: '',
            avoid: ''
        },

        validateAndSubmit(e) {
            // Basic validation
            if (!this.form.brief_title || !this.form.brief_type || !this.form.objectives || !this.form.target_audience) {
                alert('{{ __('briefs.required_fields') }}');
                e.preventDefault();
                return false;
            }

            // Structure validation will be done by backend using validate_brief_structure()
            return true;
        }
    };
}
</script>
@endpush
