@extends('super-admin.layouts.app')

@section('title', __('super_admin.announcements.create'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.announcements.index') }}"
           class="p-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-lg transition-colors">
            <i class="fas fa-arrow-{{ app()->getLocale() == 'ar' ? 'right' : 'left' }}"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.announcements.create') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.announcements.create_subtitle') }}</p>
        </div>
    </div>

    <!-- Form -->
    <form action="{{ route('super-admin.announcements.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.content_section') }}</h3>

                    <div class="space-y-4">
                        <!-- Title -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.title_label') }} <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="{{ __('super_admin.announcements.title_placeholder') }}">
                            @error('title')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Content -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.content_label') }} <span class="text-red-500">*</span>
                            </label>
                            <textarea name="content" rows="6" required
                                      class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                      placeholder="{{ __('super_admin.announcements.content_placeholder') }}">{{ old('content') }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Action Button -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('super_admin.announcements.action_text_label') }}
                                </label>
                                <input type="text" name="action_text" value="{{ old('action_text') }}"
                                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       placeholder="{{ __('super_admin.announcements.action_text_placeholder') }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                    {{ __('super_admin.announcements.action_url_label') }}
                                </label>
                                <input type="url" name="action_url" value="{{ old('action_url') }}"
                                       class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                       placeholder="https://...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduling -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.scheduling_section') }}</h3>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.starts_at_label') }}
                            </label>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <p class="mt-1 text-xs text-slate-500">{{ __('super_admin.announcements.starts_at_help') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.ends_at_label') }}
                            </label>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                            <p class="mt-1 text-xs text-slate-500">{{ __('super_admin.announcements.ends_at_help') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Settings -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.settings_section') }}</h3>

                    <div class="space-y-4">
                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.type_label') }}
                            </label>
                            <select name="type" required
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                @foreach(\App\Models\Core\Announcement::TYPES as $key => $label)
                                    <option value="{{ $key }}" {{ old('type', 'info') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Priority -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.priority_label') }}
                            </label>
                            <select name="priority" required
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                @foreach(\App\Models\Core\Announcement::PRIORITIES as $key => $label)
                                    <option value="{{ $key }}" {{ old('priority', 'normal') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Checkboxes -->
                        <div class="space-y-3">
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                                       class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('super_admin.announcements.is_active_label') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input type="checkbox" name="is_dismissible" value="1" {{ old('is_dismissible', true) ? 'checked' : '' }}
                                       class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                <span class="text-sm text-slate-700 dark:text-slate-300">{{ __('super_admin.announcements.is_dismissible_label') }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Target Audience -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.audience_section') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.target_audience_label') }}
                            </label>
                            <select name="target_audience" id="target_audience" required
                                    onchange="toggleTargetIds()"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                @foreach(\App\Models\Core\Announcement::TARGET_AUDIENCES as $key => $label)
                                    <option value="{{ $key }}" {{ old('target_audience', 'all') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Specific Plans -->
                        <div id="plans_section" style="display: none;">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.select_plans') }}
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($plans as $plan)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="target_ids[]" value="{{ $plan->plan_id }}"
                                               class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $plan->display_name ?? $plan->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Specific Orgs -->
                        <div id="orgs_section" style="display: none;">
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.select_orgs') }}
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($organizations as $org)
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="target_ids[]" value="{{ $org->org_id }}"
                                               class="rounded border-slate-300 dark:border-slate-600 text-red-600 focus:ring-red-500">
                                        <span class="text-sm text-slate-700 dark:text-slate-300">{{ $org->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Appearance -->
                <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">{{ __('super_admin.announcements.appearance_section') }}</h3>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.icon_label') }}
                            </label>
                            <input type="text" name="icon" value="{{ old('icon') }}"
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500"
                                   placeholder="fas fa-info-circle">
                            <p class="mt-1 text-xs text-slate-500">{{ __('super_admin.announcements.icon_help') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                {{ __('super_admin.announcements.color_label') }}
                            </label>
                            <select name="color"
                                    class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                <option value="">{{ __('super_admin.announcements.auto_color') }}</option>
                                <option value="blue" {{ old('color') == 'blue' ? 'selected' : '' }}>Blue</option>
                                <option value="green" {{ old('color') == 'green' ? 'selected' : '' }}>Green</option>
                                <option value="yellow" {{ old('color') == 'yellow' ? 'selected' : '' }}>Yellow</option>
                                <option value="orange" {{ old('color') == 'orange' ? 'selected' : '' }}>Orange</option>
                                <option value="red" {{ old('color') == 'red' ? 'selected' : '' }}>Red</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-bullhorn me-2"></i>
                        {{ __('super_admin.announcements.publish') }}
                    </button>
                    <a href="{{ route('super-admin.announcements.index') }}"
                       class="px-4 py-2 bg-slate-200 dark:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition-colors">
                        {{ __('super_admin.common.cancel') }}
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function toggleTargetIds() {
    const audience = document.getElementById('target_audience').value;
    document.getElementById('plans_section').style.display = audience === 'specific_plans' ? 'block' : 'none';
    document.getElementById('orgs_section').style.display = audience === 'specific_orgs' ? 'block' : 'none';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleTargetIds);
</script>
@endsection
