@extends('layouts.admin')

@section('title', __('profiles.title') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6" x-data="profileList()">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('profiles.title') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('profiles.title') }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('profiles.subtitle') }}
            </p>
        </div>
        {{-- Stats badges --}}
        <div class="flex items-center gap-3">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                {{ $stats['total'] ?? 0 }} {{ __('profiles.total_profiles') }}
            </span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                {{ $stats['active'] ?? 0 }} {{ __('profiles.status_active') }}
            </span>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="{{ route('orgs.settings.profiles.index', $currentOrg) }}" class="flex flex-wrap items-center gap-4">
            {{-- Search --}}
            <div class="flex-1 min-w-[200px]">
                <div class="relative">
                    <input type="text"
                           name="search"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="{{ __('profiles.search_placeholder') }}"
                           class="w-full ps-10 pe-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                </div>
            </div>

            {{-- Network filter --}}
            <div class="min-w-[150px]">
                <select name="platform"
                        class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('profiles.all_networks') }}</option>
                    @foreach($platforms as $key => $name)
                        <option value="{{ $key }}" {{ ($filters['platform'] ?? '') === $key ? 'selected' : '' }}>
                            {{ $name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Status filter --}}
            <div class="min-w-[130px]">
                <select name="status"
                        class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('profiles.all_statuses') }}</option>
                    <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>
                        {{ __('profiles.status_active') }}
                    </option>
                    <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>
                        {{ __('profiles.status_inactive') }}
                    </option>
                    <option value="error" {{ ($filters['status'] ?? '') === 'error' ? 'selected' : '' }}>
                        {{ __('profiles.status_error') }}
                    </option>
                </select>
            </div>

            {{-- Group filter --}}
            <div class="min-w-[150px]">
                <select name="group_id"
                        class="w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('profiles.all_groups') }}</option>
                    @foreach($profileGroups as $group)
                        <option value="{{ $group->group_id }}" {{ ($filters['group_id'] ?? '') === $group->group_id ? 'selected' : '' }}>
                            {{ $group->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Filter button --}}
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-filter me-1"></i>
                {{ __('common.filter') }}
            </button>

            {{-- Clear filters --}}
            @if(!empty(array_filter($filters)))
                <a href="{{ route('orgs.settings.profiles.index', $currentOrg) }}"
                   class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
                    {{ __('common.clear') }}
                </a>
            @endif
        </form>
    </div>

    {{-- Profiles Table --}}
    @if($profiles->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('profiles.name') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('profiles.profile_group') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('profiles.connected') }}
                            </th>
                            <th scope="col" class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">
                                {{ __('profiles.status') }}
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">{{ __('profiles.actions') }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($profiles as $profile)
                            <tr class="hover:bg-gray-50 transition">
                                {{-- Name with platform icon and avatar --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="relative flex-shrink-0">
                                            @if($profile->avatar_url)
                                                <img class="h-10 w-10 rounded-full object-cover"
                                                     src="{{ $profile->avatar_url }}"
                                                     alt="{{ $profile->effective_name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                                    <i class="fas fa-user text-gray-400"></i>
                                                </div>
                                            @endif
                                            {{-- Platform badge --}}
                                            <span class="absolute -bottom-1 -end-1 h-5 w-5 rounded-full bg-white border-2 border-white flex items-center justify-center">
                                                @include('components.platform-icon', ['platform' => $profile->platform, 'size' => 'xs'])
                                            </span>
                                        </div>
                                        <div class="ms-4">
                                            <a href="{{ route('orgs.settings.profiles.show', [$currentOrg, $profile->integration_id]) }}"
                                               class="text-sm font-medium text-blue-600 hover:text-blue-800">
                                                {{ $profile->effective_name }}
                                            </a>
                                            @if($profile->username)
                                                <div class="text-xs text-gray-500">
                                                    {{ '@' . $profile->username }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                {{-- Profile Group --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($profile->profileGroup)
                                        <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $profile->profile_group_id]) }}"
                                           class="text-sm text-blue-600 hover:text-blue-800">
                                            {{ $profile->profileGroup->name }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Connected date --}}
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $profile->created_at?->format('M d, Y') ?? '—' }}
                                </td>

                                {{-- Status --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php $statusLabel = $profile->status_label; @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        {{ $statusLabel === 'active' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $statusLabel === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}
                                        {{ $statusLabel === 'error' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ __('profiles.status_' . $statusLabel) }}
                                    </span>
                                </td>

                                {{-- Actions (3-dot menu) --}}
                                <td class="px-6 py-4 whitespace-nowrap text-end text-sm font-medium">
                                    <div class="relative" x-data="{ open: false }">
                                        <button @click="open = !open"
                                                class="p-2 text-gray-400 hover:text-gray-600 rounded-lg hover:bg-gray-100 transition">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div x-show="open"
                                             @click.away="open = false"
                                             x-cloak
                                             class="absolute end-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10">
                                            <div class="py-1">
                                                {{-- Refresh Connection --}}
                                                <button @click="refreshConnection('{{ $profile->integration_id }}'); open = false"
                                                        class="w-full text-start block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-sync-alt w-4 me-2"></i>
                                                    {{ __('profiles.refresh_connection') }}
                                                </button>

                                                {{-- View Profile --}}
                                                <a href="{{ route('orgs.settings.profiles.show', [$currentOrg, $profile->integration_id]) }}"
                                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-eye w-4 me-2"></i>
                                                    {{ __('profiles.view_profile') }}
                                                </a>

                                                {{-- Manage Groups --}}
                                                <button @click="showManageGroups('{{ $profile->integration_id }}', '{{ $profile->profile_group_id ?? '' }}'); open = false"
                                                        class="w-full text-start block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    <i class="fas fa-layer-group w-4 me-2"></i>
                                                    {{ __('profiles.manage_groups') }}
                                                </button>

                                                {{-- Remove Profile --}}
                                                <button @click="confirmRemove('{{ $profile->integration_id }}', '{{ $profile->effective_name }}'); open = false"
                                                        class="w-full text-start block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                    <i class="fas fa-trash w-4 me-2"></i>
                                                    {{ __('profiles.remove_profile') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($profiles->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $profiles->withQueryString()->links() }}
                </div>
            @endif
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-user-circle text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('profiles.no_profiles') }}</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                {{ __('profiles.no_profiles_message') }}
            </p>
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-plug me-2"></i>
                {{ __('Platform Connections') }}
            </a>
        </div>
    @endif

    {{-- Manage Groups Modal --}}
    <div x-show="showGroupsModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showGroupsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showGroupsModal = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showGroupsModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('profiles.manage_groups') }}</h3>
                    <div class="space-y-3">
                        @foreach($profileGroups as $group)
                            <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                   :class="{ 'border-blue-500 bg-blue-50': selectedGroupId === '{{ $group->group_id }}' }">
                                <input type="radio"
                                       name="group_id"
                                       value="{{ $group->group_id }}"
                                       x-model="selectedGroupId"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                <div class="ms-3 flex items-center">
                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white font-bold text-sm"
                                         style="background-color: {{ $group->color ?? '#3B82F6' }}">
                                        {{ strtoupper(substr($group->name, 0, 1)) }}
                                    </div>
                                    <div class="ms-3">
                                        <span class="text-sm font-medium text-gray-900">{{ $group->name }}</span>
                                        <span class="text-xs text-gray-500 ms-2">{{ $group->timezone }}</span>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button"
                            @click="assignToGroup()"
                            :disabled="loading"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm disabled:opacity-50">
                        <span x-show="loading" class="me-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                        {{ __('profiles.save') }}
                    </button>
                    <button type="button"
                            @click="showGroupsModal = false"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ms-3 sm:w-auto sm:text-sm">
                        {{ __('profiles.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function profileList() {
    return {
        loading: false,
        showGroupsModal: false,
        selectedProfileId: null,
        selectedGroupId: '',

        async refreshConnection(integrationId) {
            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/${integrationId}/refresh`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.connection_refreshed") }}', type: 'success' }
                    }));
                    location.reload();
                } else {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: data.message || 'Error', type: 'error' }
                    }));
                }
            } catch (error) {
                console.error('Error:', error);
                window.dispatchEvent(new CustomEvent('toast', {
                    detail: { message: 'Failed to refresh connection', type: 'error' }
                }));
            }
            this.loading = false;
        },

        showManageGroups(integrationId, currentGroupId) {
            this.selectedProfileId = integrationId;
            this.selectedGroupId = currentGroupId || '';
            this.showGroupsModal = true;
        },

        async assignToGroup() {
            if (!this.selectedProfileId) return;

            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/${this.selectedProfileId}/groups`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ group_id: this.selectedGroupId })
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.group_assigned") }}', type: 'success' }
                    }));
                    this.showGroupsModal = false;
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
        },

        async confirmRemove(integrationId, name) {
            if (!confirm(`{{ __('profiles.confirm_remove') }}\n\n${name}`)) return;

            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/${integrationId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.profile_removed") }}', type: 'success' }
                    }));
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
@endpush
@endsection
