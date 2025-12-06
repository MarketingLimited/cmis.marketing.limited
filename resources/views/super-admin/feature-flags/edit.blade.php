@extends('super-admin.layouts.app')

@section('title', __('super_admin.feature_flags.edit_title') . ' - ' . $flag->feature_key)

@section('content')
<div class="space-y-6" x-data="editFlagForm()">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="{{ route('super-admin.feature-flags.show', $flag->id) }}" class="p-2 text-slate-400 hover:text-white transition-colors">
            <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xl"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('super_admin.feature_flags.edit_title') }}</h1>
            <p class="text-slate-400 mt-1 font-mono">{{ $flag->feature_key }}</p>
        </div>
    </div>

    <!-- Form -->
    <form method="POST" action="{{ route('super-admin.feature-flags.update', $flag->id) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6 space-y-6">
            <!-- Feature Key (read-only) -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    {{ __('super_admin.feature_flags.feature_key') }}
                </label>
                <div class="w-full bg-slate-900 border border-slate-700 text-slate-400 rounded-lg px-4 py-3 font-mono">
                    {{ $flag->feature_key }}
                </div>
                <p class="text-slate-500 text-sm mt-1">{{ __('super_admin.feature_flags.key_readonly') }}</p>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-slate-300 mb-2">
                    {{ __('super_admin.feature_flags.description') }}
                </label>
                <textarea id="description" name="description" rows="3"
                          class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                          placeholder="{{ __('super_admin.feature_flags.description_placeholder') }}">{{ old('description', $flag->description) }}</textarea>
                @error('description')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Scope Type -->
                <div>
                    <label for="scope_type" class="block text-sm font-medium text-slate-300 mb-2">
                        {{ __('super_admin.feature_flags.scope_type') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="scope_type" name="scope_type" x-model="scopeType"
                            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" required>
                        <option value="global" {{ $flag->scope_type === 'global' ? 'selected' : '' }}>{{ __('super_admin.feature_flags.scope_global') }}</option>
                        <option value="organization" {{ $flag->scope_type === 'organization' ? 'selected' : '' }}>{{ __('super_admin.feature_flags.scope_organization') }}</option>
                        <option value="user" {{ $flag->scope_type === 'user' ? 'selected' : '' }}>{{ __('super_admin.feature_flags.scope_user') }}</option>
                    </select>
                    @error('scope_type')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Organization (conditional) -->
                <div x-show="scopeType === 'organization'" x-cloak>
                    <label for="scope_id" class="block text-sm font-medium text-slate-300 mb-2">
                        {{ __('super_admin.feature_flags.organization') }} <span class="text-red-500">*</span>
                    </label>
                    <select id="scope_id" name="scope_id"
                            class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent"
                            :required="scopeType === 'organization'">
                        <option value="">{{ __('super_admin.feature_flags.select_organization') }}</option>
                        @foreach($organizations as $org)
                            <option value="{{ $org->org_id }}" {{ $flag->scope_id === $org->org_id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('scope_id')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- User ID (conditional) -->
                <div x-show="scopeType === 'user'" x-cloak>
                    <label for="scope_id_user" class="block text-sm font-medium text-slate-300 mb-2">
                        {{ __('super_admin.feature_flags.user_id') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="scope_id_user" name="scope_id"
                           value="{{ $flag->scope_type === 'user' ? $flag->scope_id : '' }}"
                           class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent font-mono"
                           placeholder="UUID"
                           :required="scopeType === 'user'">
                    @error('scope_id')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Value -->
            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">
                    {{ __('super_admin.feature_flags.current_value') }} <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-4">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="value" value="1" {{ $flag->value ? 'checked' : '' }}
                               class="w-4 h-4 text-red-600 bg-slate-700 border-slate-600 focus:ring-red-500">
                        <span class="text-white">{{ __('super_admin.feature_flags.enabled') }}</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="radio" name="value" value="0" {{ !$flag->value ? 'checked' : '' }}
                               class="w-4 h-4 text-red-600 bg-slate-700 border-slate-600 focus:ring-red-500">
                        <span class="text-white">{{ __('super_admin.feature_flags.disabled') }}</span>
                    </label>
                </div>
                @error('value')
                    <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-3 justify-end">
            <a href="{{ route('super-admin.feature-flags.show', $flag->id) }}"
               class="px-6 py-3 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors">
                {{ __('super_admin.common.cancel') }}
            </a>
            <button type="submit" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                <i class="fas fa-save me-2"></i>{{ __('super_admin.common.save_changes') }}
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function editFlagForm() {
    return {
        scopeType: '{{ old('scope_type', $flag->scope_type) }}'
    };
}
</script>
@endpush
