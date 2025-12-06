@extends('super-admin.layouts.app')

@section('title', __('super_admin.feature_flags.browse_title'))

@section('content')
<div class="space-y-6" x-data="featureFlagsManager()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('super_admin.feature_flags.browse_title') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('super_admin.feature_flags.browse_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.feature-flags.create') }}"
           class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors inline-flex items-center">
            <i class="fas fa-plus me-2"></i>{{ __('super_admin.feature_flags.create_new') }}
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
        <form method="GET" action="{{ route('super-admin.feature-flags.browse') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.search') }}</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="{{ __('super_admin.feature_flags.search_placeholder') }}"
                       class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.scope_type') }}</label>
                <select name="scope_type" class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2">
                    <option value="">{{ __('super_admin.common.all') }}</option>
                    @foreach($scopeTypes as $type)
                        <option value="{{ $type }}" {{ request('scope_type') === $type ? 'selected' : '' }}>
                            {{ ucfirst($type) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.status') }}</label>
                <select name="value" class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2">
                    <option value="">{{ __('super_admin.common.all') }}</option>
                    <option value="true" {{ request('value') === 'true' ? 'selected' : '' }}>{{ __('super_admin.feature_flags.enabled') }}</option>
                    <option value="false" {{ request('value') === 'false' ? 'selected' : '' }}>{{ __('super_admin.feature_flags.disabled') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-400 mb-2">{{ __('super_admin.feature_flags.organization') }}</label>
                <select name="org_id" class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2">
                    <option value="">{{ __('super_admin.common.all') }}</option>
                    @foreach($organizations as $org)
                        <option value="{{ $org->org_id }}" {{ request('org_id') === $org->org_id ? 'selected' : '' }}>
                            {{ $org->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-4 flex gap-3">
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    <i class="fas fa-search me-2"></i>{{ __('super_admin.common.filter') }}
                </button>
                <a href="{{ route('super-admin.feature-flags.browse') }}" class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors">
                    {{ __('super_admin.common.clear') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Flags Table -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-700/50">
                    <tr>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.feature_key') }}</th>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.description') }}</th>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.scope') }}</th>
                        <th class="text-center text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.status') }}</th>
                        <th class="text-start text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.feature_flags.updated') }}</th>
                        <th class="text-end text-xs font-medium text-slate-400 uppercase px-6 py-4">{{ __('super_admin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($flags as $flag)
                        <tr class="hover:bg-slate-700/50 transition-colors">
                            <td class="px-6 py-4">
                                <code class="text-sm text-white bg-slate-700 px-2 py-1 rounded">{{ $flag->feature_key }}</code>
                            </td>
                            <td class="px-6 py-4 text-slate-300 text-sm max-w-xs truncate">
                                {{ $flag->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($flag->scope_type === 'global') bg-purple-500/20 text-purple-400
                                    @elseif($flag->scope_type === 'organization') bg-blue-500/20 text-blue-400
                                    @else bg-green-500/20 text-green-400 @endif">
                                    {{ ucfirst($flag->scope_type) }}
                                </span>
                                @if($flag->org_name)
                                    <span class="block text-slate-400 text-xs mt-1">{{ $flag->org_name }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button @click="toggleFlag('{{ $flag->id }}')"
                                        class="w-14 h-7 rounded-full transition-colors relative cursor-pointer
                                               {{ $flag->value ? 'bg-green-500' : 'bg-slate-600' }}"
                                        :class="{ 'bg-green-500': flags['{{ $flag->id }}'] ?? {{ $flag->value ? 'true' : 'false' }}, 'bg-slate-600': !(flags['{{ $flag->id }}'] ?? {{ $flag->value ? 'true' : 'false' }}) }">
                                    <span class="absolute top-1 transition-all duration-200 w-5 h-5 bg-white rounded-full shadow"
                                          :class="{ 'start-8': flags['{{ $flag->id }}'] ?? {{ $flag->value ? 'true' : 'false' }}, 'start-1': !(flags['{{ $flag->id }}'] ?? {{ $flag->value ? 'true' : 'false' }}) }"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 text-slate-400 text-sm">
                                {{ \Carbon\Carbon::parse($flag->updated_at)->diffForHumans() }}
                            </td>
                            <td class="px-6 py-4 text-end">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('super-admin.feature-flags.show', $flag->id) }}"
                                       class="p-2 text-slate-400 hover:text-blue-400 transition-colors"
                                       title="{{ __('super_admin.common.view') }}">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('super-admin.feature-flags.edit', $flag->id) }}"
                                       class="p-2 text-slate-400 hover:text-yellow-400 transition-colors"
                                       title="{{ __('super_admin.common.edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button @click="deleteFlag('{{ $flag->id }}', '{{ $flag->feature_key }}')"
                                            class="p-2 text-slate-400 hover:text-red-400 transition-colors"
                                            title="{{ __('super_admin.common.delete') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                {{ __('super_admin.feature_flags.no_flags') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($flags->hasPages())
            <div class="p-6 border-t border-slate-700">
                {{ $flags->links() }}
            </div>
        @endif
    </div>

    <!-- Delete Modal -->
    <div x-show="showDeleteModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
        <div class="bg-slate-800 rounded-xl p-6 w-full max-w-md mx-4 border border-slate-700">
            <h3 class="text-xl font-semibold text-white mb-4">{{ __('super_admin.feature_flags.confirm_delete') }}</h3>
            <p class="text-slate-400 mb-6">
                {{ __('super_admin.feature_flags.delete_warning') }}
                <span class="font-mono text-white" x-text="deleteKey"></span>
            </p>
            <div class="flex gap-3 justify-end">
                <button @click="showDeleteModal = false"
                        class="px-4 py-2 bg-slate-700 text-white rounded-lg hover:bg-slate-600 transition-colors">
                    {{ __('super_admin.common.cancel') }}
                </button>
                <button @click="confirmDelete"
                        :disabled="deleting"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors disabled:opacity-50">
                    <span x-show="!deleting">{{ __('super_admin.common.delete') }}</span>
                    <span x-show="deleting"><i class="fas fa-spinner fa-spin me-2"></i>{{ __('super_admin.common.deleting') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function featureFlagsManager() {
    return {
        flags: {},
        showDeleteModal: false,
        deleteId: null,
        deleteKey: '',
        deleting: false,

        async toggleFlag(id) {
            try {
                const response = await fetch(`/super-admin/feature-flags/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                const data = await response.json();
                if (data.success) {
                    this.flags[id] = data.value;
                }
            } catch (error) {
                console.error('Toggle failed:', error);
            }
        },

        deleteFlag(id, key) {
            this.deleteId = id;
            this.deleteKey = key;
            this.showDeleteModal = true;
        },

        async confirmDelete() {
            this.deleting = true;
            try {
                const response = await fetch(`/super-admin/feature-flags/${this.deleteId}`, {
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
                console.error('Delete failed:', error);
            } finally {
                this.deleting = false;
                this.showDeleteModal = false;
            }
        }
    };
}
</script>
@endpush
