@extends('super-admin.layouts.app')

@section('title', $flag->feature_key . ' - ' . __('super_admin.feature_flags.title'))

@section('content')
<div class="space-y-6" x-data="flagDetails()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('super-admin.feature-flags.browse') }}" class="p-2 text-slate-400 hover:text-white transition-colors">
                <i class="fas fa-arrow-{{ app()->getLocale() === 'ar' ? 'right' : 'left' }} text-xl"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white font-mono">{{ $flag->feature_key }}</h1>
                <p class="text-slate-400 mt-1">{{ $flag->description ?? __('super_admin.feature_flags.no_description') }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('super-admin.feature-flags.edit', $flag->id) }}"
               class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors">
                <i class="fas fa-edit me-2"></i>{{ __('super_admin.common.edit') }}
            </a>
            <button @click="toggleValue"
                    class="px-4 py-2 rounded-lg transition-colors"
                    :class="currentValue ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'">
                <i class="fas me-2" :class="currentValue ? 'fa-toggle-on' : 'fa-toggle-off'"></i>
                <span x-text="currentValue ? '{{ __('super_admin.feature_flags.enabled') }}' : '{{ __('super_admin.feature_flags.disabled') }}'"></span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Flag Info -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">{{ __('super_admin.feature_flags.flag_details') }}</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-slate-400 text-sm">{{ __('super_admin.feature_flags.scope_type') }}</p>
                        <span class="px-2 py-1 text-sm rounded-full mt-1 inline-block
                            @if($flag->scope_type === 'global') bg-purple-500/20 text-purple-400
                            @elseif($flag->scope_type === 'organization') bg-blue-500/20 text-blue-400
                            @else bg-green-500/20 text-green-400 @endif">
                            {{ ucfirst($flag->scope_type) }}
                        </span>
                    </div>
                    @if($flag->org_name)
                        <div>
                            <p class="text-slate-400 text-sm">{{ __('super_admin.feature_flags.organization') }}</p>
                            <p class="text-white mt-1">{{ $flag->org_name }}</p>
                        </div>
                    @endif
                    <div>
                        <p class="text-slate-400 text-sm">{{ __('super_admin.common.created_at') }}</p>
                        <p class="text-white mt-1">{{ \Carbon\Carbon::parse($flag->created_at)->format('Y-m-d H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-slate-400 text-sm">{{ __('super_admin.common.updated_at') }}</p>
                        <p class="text-white mt-1">{{ \Carbon\Carbon::parse($flag->updated_at)->format('Y-m-d H:i') }}</p>
                    </div>
                </div>
            </div>

            <!-- Overrides -->
            <div class="bg-slate-800 rounded-xl border border-slate-700">
                <div class="p-6 border-b border-slate-700 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-white">{{ __('super_admin.feature_flags.overrides') }}</h2>
                    <button @click="showAddOverride = true" class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors">
                        <i class="fas fa-plus me-1"></i>{{ __('super_admin.feature_flags.add_override') }}
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-700/50">
                            <tr>
                                <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-3">{{ __('super_admin.feature_flags.scope') }}</th>
                                <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-3">{{ __('super_admin.feature_flags.target') }}</th>
                                <th class="text-center text-xs font-medium text-slate-400 uppercase px-6 py-3">{{ __('super_admin.feature_flags.value') }}</th>
                                <th class="text-end text-xs font-medium text-slate-400 uppercase px-6 py-3">{{ __('super_admin.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700">
                            @forelse($overrides as $override)
                                <tr class="hover:bg-slate-700/50">
                                    <td class="px-6 py-3 text-white capitalize">{{ $override->scope_type }}</td>
                                    <td class="px-6 py-3 text-slate-300">
                                        {{ $override->org_name ?? $override->user_name ?? $override->scope_id }}
                                    </td>
                                    <td class="px-6 py-3 text-center">
                                        <span class="px-2 py-1 text-xs rounded-full {{ $override->value ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                                            {{ $override->value ? __('super_admin.feature_flags.enabled') : __('super_admin.feature_flags.disabled') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-end">
                                        <button @click="removeOverride('{{ $override->id }}')"
                                                class="text-red-400 hover:text-red-300 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                        {{ __('super_admin.feature_flags.no_overrides') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Audit History -->
            <div class="bg-slate-800 rounded-xl border border-slate-700">
                <div class="p-6 border-b border-slate-700">
                    <h2 class="text-lg font-semibold text-white">{{ __('super_admin.feature_flags.audit_history') }}</h2>
                </div>
                <div class="divide-y divide-slate-700">
                    @forelse($auditHistory as $audit)
                        <div class="p-4 flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center
                                @if($audit->action === 'created') bg-green-500/20 text-green-400
                                @elseif($audit->action === 'deleted') bg-red-500/20 text-red-400
                                @elseif($audit->action === 'toggled') bg-yellow-500/20 text-yellow-400
                                @else bg-blue-500/20 text-blue-400 @endif">
                                @if($audit->action === 'created')
                                    <i class="fas fa-plus"></i>
                                @elseif($audit->action === 'deleted')
                                    <i class="fas fa-trash"></i>
                                @elseif($audit->action === 'toggled')
                                    <i class="fas fa-toggle-on"></i>
                                @else
                                    <i class="fas fa-edit"></i>
                                @endif
                            </div>
                            <div class="flex-1">
                                <p class="text-white">
                                    <span class="font-medium capitalize">{{ $audit->action }}</span>
                                    @if($audit->performer_name)
                                        {{ __('super_admin.feature_flags.by') }}
                                        <span class="text-slate-300">{{ $audit->performer_name }}</span>
                                    @endif
                                </p>
                                <p class="text-slate-400 text-sm mt-1">
                                    {{ \Carbon\Carbon::parse($audit->created_at)->format('Y-m-d H:i:s') }}
                                </p>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-400">
                            {{ __('super_admin.feature_flags.no_audit_history') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Quick Stats -->
            <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">{{ __('super_admin.feature_flags.quick_stats') }}</h2>
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">{{ __('super_admin.feature_flags.current_status') }}</span>
                        <span class="px-2 py-1 text-sm rounded-full"
                              :class="currentValue ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400'"
                              x-text="currentValue ? '{{ __('super_admin.feature_flags.enabled') }}' : '{{ __('super_admin.feature_flags.disabled') }}'">
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">{{ __('super_admin.feature_flags.overrides_count') }}</span>
                        <span class="text-white font-semibold">{{ $overrides->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-slate-400">{{ __('super_admin.feature_flags.changes') }}</span>
                        <span class="text-white font-semibold">{{ $auditHistory->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Metadata -->
            @if($flag->metadata && $flag->metadata !== '{}' && $flag->metadata !== '[]')
                <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
                    <h2 class="text-lg font-semibold text-white mb-4">{{ __('super_admin.feature_flags.metadata') }}</h2>
                    <pre class="text-slate-300 text-sm bg-slate-900 rounded-lg p-4 overflow-x-auto">{{ json_encode(json_decode($flag->metadata), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>
    </div>

    <!-- Add Override Modal -->
    <div x-show="showAddOverride" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md mx-4 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">{{ __('super_admin.feature_flags.add_override') }}</h3>
            <form @submit.prevent="submitOverride">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.scope_type') }}</label>
                        <select x-model="overrideForm.scope_type" class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2">
                            <option value="organization">{{ __('super_admin.feature_flags.scope_organization') }}</option>
                            <option value="user">{{ __('super_admin.feature_flags.scope_user') }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.target_id') }}</label>
                        <input type="text" x-model="overrideForm.scope_id"
                               class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2 font-mono"
                               placeholder="UUID">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.value') }}</label>
                        <div class="flex gap-4">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="overrideForm.value" value="true" class="text-red-600">
                                <span class="text-white">{{ __('super_admin.feature_flags.enabled') }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" x-model="overrideForm.value" value="false" class="text-red-600">
                                <span class="text-white">{{ __('super_admin.feature_flags.disabled') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex gap-3 justify-end mt-6">
                    <button type="button" @click="showAddOverride = false"
                            class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors">
                        {{ __('super_admin.common.cancel') }}
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        {{ __('super_admin.feature_flags.add_override') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function flagDetails() {
    return {
        currentValue: {{ $flag->value ? 'true' : 'false' }},
        showAddOverride: false,
        overrideForm: {
            scope_type: 'organization',
            scope_id: '',
            value: 'true'
        },

        async toggleValue() {
            try {
                const response = await fetch(`/super-admin/feature-flags/{{ $flag->id }}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.currentValue = data.value;
                }
            } catch (error) {
                console.error('Toggle failed:', error);
            }
        },

        async submitOverride() {
            try {
                const response = await fetch(`/super-admin/feature-flags/{{ $flag->id }}/override`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        scope_type: this.overrideForm.scope_type,
                        scope_id: this.overrideForm.scope_id,
                        value: this.overrideForm.value === 'true'
                    })
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Add override failed:', error);
            }
        },

        async removeOverride(overrideId) {
            if (!confirm('{{ __('super_admin.feature_flags.confirm_remove_override') }}')) return;

            try {
                const response = await fetch(`/super-admin/feature-flags/{{ $flag->id }}/override/${overrideId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Remove override failed:', error);
            }
        }
    };
}
</script>
@endpush
