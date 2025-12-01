@extends('layouts.admin')

@section('title', $profile->effective_name . ' - ' . __('profiles.title'))

@section('content')
<div class="space-y-6" x-data="profileDetail()">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.profiles.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('profiles.title') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $profile->effective_name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $profile->effective_name }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('profiles.configure_subtitle') }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="showImageUpload = true"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-image me-2"></i>
                {{ __('profiles.update_image') }}
            </button>
            <button @click="confirmRemove()"
                    class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                <i class="fas fa-trash me-2"></i>
                {{ __('profiles.remove') }}
            </button>
        </div>
    </div>

    {{-- Profile Card --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-start gap-6">
            {{-- Avatar and basic info --}}
            <div class="flex items-center gap-4">
                <div class="relative">
                    @if($profile->avatar_url)
                        <img class="h-20 w-20 rounded-full object-cover border-4 border-white shadow-lg"
                             src="{{ $profile->avatar_url }}"
                             alt="{{ $profile->effective_name }}">
                    @else
                        <div class="h-20 w-20 rounded-full bg-gray-200 flex items-center justify-center border-4 border-white shadow-lg">
                            <i class="fas fa-user text-gray-400 text-2xl"></i>
                        </div>
                    @endif
                    {{-- Platform badge --}}
                    <span class="absolute bottom-0 end-0 transform translate-x-1 translate-y-1">
                        @include('components.platform-icon', ['platform' => $profile->platform, 'size' => 'lg'])
                    </span>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $profile->effective_name }}</h2>
                    @if($profile->username)
                        <p class="text-sm text-gray-500">{{ '@' . $profile->username }}</p>
                    @endif
                    @if($profile->bio)
                        <p class="text-sm text-gray-600 mt-1">{{ $profile->bio }}</p>
                    @endif
                </div>
            </div>

            {{-- Stats grid --}}
            <div class="flex-1 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6">
                {{-- Status --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.status') }}</p>
                    @php $statusLabel = $profile->status_label; @endphp
                    <span class="inline-flex items-center mt-1 px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $statusLabel === 'active' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $statusLabel === 'inactive' ? 'bg-gray-100 text-gray-800' : '' }}
                        {{ $statusLabel === 'error' ? 'bg-red-100 text-red-800' : '' }}">
                        {{ __('profiles.status_' . $statusLabel) }}
                    </span>
                </div>

                {{-- Type --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.type') }}</p>
                    <p class="mt-1 text-sm font-medium text-gray-900">
                        {{ __('profiles.type_' . ($profile->profile_type ?? 'business')) }}
                    </p>
                </div>

                {{-- Connected --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.connected') }}</p>
                    <p class="mt-1 text-sm font-medium text-blue-600">
                        {{ $profile->created_at?->format('M d, Y') ?? '—' }}
                    </p>
                </div>

                {{-- Team member --}}
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider">{{ __('profiles.team_member') }}</p>
                    <p class="mt-1 text-sm font-medium text-blue-600">
                        {{ $profile->creator?->name ?? $profile->connectedByUser?->name ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Industry (editable) --}}
        <div class="mt-6 pt-6 border-t border-gray-200">
            <div class="flex items-center gap-2">
                <p class="text-sm text-gray-500">{{ __('profiles.industry') }}</p>
                <button @click="editingIndustry = true" class="text-gray-400 hover:text-blue-600 transition">
                    <i class="fas fa-pencil-alt text-xs"></i>
                </button>
            </div>
            <div x-show="!editingIndustry" class="mt-1">
                <p class="text-sm font-medium {{ $profile->industry ? 'text-blue-600' : 'text-gray-400' }}">
                    {{ $industries[$profile->industry] ?? __('profiles.not_set') }}
                </p>
            </div>
            <div x-show="editingIndustry" x-cloak class="mt-1 flex items-center gap-2">
                <select x-model="industryValue"
                        class="block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">{{ __('profiles.not_set') }}</option>
                    @foreach($industries as $key => $name)
                        <option value="{{ $key }}">{{ $name }}</option>
                    @endforeach
                </select>
                <button @click="saveIndustry()"
                        class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700">
                    {{ __('profiles.save') }}
                </button>
                <button @click="editingIndustry = false; industryValue = '{{ $profile->industry ?? '' }}'"
                        class="px-3 py-1.5 border border-gray-300 text-gray-700 text-sm rounded-md hover:bg-gray-50">
                    {{ __('profiles.cancel') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Profile Groups Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-blue-600"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 uppercase tracking-wider">
                    {{ __('profiles.profile_groups') }}
                </h3>
            </div>
            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}"
               class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                {{ __('profiles.manage_profile_groups') }}
            </a>
        </div>

        @if($profile->profileGroup)
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold"
                         style="background-color: {{ $profile->profileGroup->color ?? '#3B82F6' }}">
                        {{ strtoupper(substr($profile->profileGroup->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $profile->profileGroup->name }}</p>
                        <p class="text-xs text-gray-500">
                            Client · {{ $profile->profileGroup->client_country ?? 'Not set' }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-layer-group text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-3">{{ __('profiles.no_groups_message') }}</p>
                <button @click="showGroupsModal = true"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    {{ __('profiles.manage_groups') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Publishing Queues Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                    <i class="fas fa-clock text-purple-600"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 uppercase tracking-wider">
                    {{ __('profiles.publishing_queues') }}
                </h3>
            </div>
        </div>

        @if($queueSettings && $queueSettings->queue_enabled)
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">{{ __('profiles.queue_enabled') }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ __('profiles.status_active') }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-600 mb-2">{{ __('profiles.posting_times') }}</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($queueSettings->posting_times ?? [] as $time)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ $time }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-clock text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-3">{{ __('profiles.no_queue_message') }}</p>
                <button @click="showQueueModal = true"
                        class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                    {{ __('profiles.queue_settings') }}
                </button>
            </div>
        @endif
    </div>

    {{-- Boost Settings Section --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                    <i class="fas fa-rocket text-orange-600"></i>
                </div>
                <h3 class="text-base font-semibold text-gray-900 uppercase tracking-wider">
                    {{ __('profiles.boost_settings') }}
                </h3>
            </div>
            <button @click="showBoostModal = true; editingBoostId = null"
                    class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-plus me-1"></i>
                {{ __('profiles.add_boost') }}
            </button>
        </div>

        @if($boostRules->count() > 0)
            <div class="space-y-3">
                @foreach($boostRules as $boost)
                    <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $boost->name }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ __('profiles.trigger_' . $boost->trigger_type) }}
                                    @if($boost->delay_after_publish)
                                        · {{ $boost->delay_after_publish['value'] ?? 0 }} {{ $boost->delay_after_publish['unit'] ?? 'hours' }}
                                    @endif
                                    @if($boost->budget_amount)
                                        · {{ number_format($boost->budget_amount, 2) }} {{ $boost->budget_currency ?? 'USD' }}
                                    @endif
                                </p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    {{ $boost->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $boost->is_active ? __('profiles.status_active') : __('profiles.status_inactive') }}
                                </span>
                                <button @click="editBoost('{{ $boost->boost_rule_id }}')"
                                        class="p-1 text-gray-400 hover:text-blue-600">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button @click="toggleBoost('{{ $boost->boost_rule_id }}')"
                                        class="p-1 text-gray-400 hover:text-yellow-600">
                                    <i class="fas fa-power-off"></i>
                                </button>
                                <button @click="deleteBoost('{{ $boost->boost_rule_id }}')"
                                        class="p-1 text-gray-400 hover:text-red-600">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <div class="w-12 h-12 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-3">
                    <i class="fas fa-rocket text-gray-400"></i>
                </div>
                <p class="text-sm text-gray-500 mb-3">{{ __('profiles.no_boosts_message') }}</p>
                @if(!$profile->profile_group_id)
                    <p class="text-xs text-yellow-600 mb-3">
                        <i class="fas fa-exclamation-triangle me-1"></i>
                        {{ __('profiles.profile_must_be_in_group') }}
                    </p>
                @endif
            </div>
        @endif
    </div>

    {{-- Boost Modal --}}
    @include('settings.profiles.partials._boost-modal')

    {{-- Manage Groups Modal --}}
    @include('settings.profiles.partials._groups-modal')
</div>

@push('scripts')
<script>
function profileDetail() {
    return {
        loading: false,
        editingIndustry: false,
        industryValue: '{{ $profile->industry ?? '' }}',
        showBoostModal: false,
        showGroupsModal: false,
        showQueueModal: false,
        showImageUpload: false,
        editingBoostId: null,
        selectedGroupId: '{{ $profile->profile_group_id ?? '' }}',

        async saveIndustry() {
            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ industry: this.industryValue })
                });
                const data = await response.json();
                if (data.success) {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: '{{ __("profiles.profile_updated") }}', type: 'success' }
                    }));
                    this.editingIndustry = false;
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
            this.loading = false;
        },

        async editBoost(boostId) {
            this.editingBoostId = boostId;
            this.showBoostModal = true;
        },

        async toggleBoost(boostId) {
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boosts/${boostId}/toggle`, {
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
                        detail: { message: '{{ __("profiles.boost_toggled") }}', type: 'success' }
                    }));
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async deleteBoost(boostId) {
            if (!confirm('{{ __("profiles.confirm_delete_boost") }}')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/boosts/${boostId}`, {
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
                        detail: { message: '{{ __("profiles.boost_deleted") }}', type: 'success' }
                    }));
                    location.reload();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        },

        async assignToGroup() {
            this.loading = true;
            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}/groups`, {
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
                }
            } catch (error) {
                console.error('Error:', error);
            }
            this.loading = false;
        },

        async confirmRemove() {
            if (!confirm('{{ __("profiles.confirm_remove") }}')) return;

            try {
                const response = await fetch(`/orgs/{{ $currentOrg }}/settings/profiles/{{ $profile->integration_id }}`, {
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
                    window.location.href = '{{ route("orgs.settings.profiles.index", $currentOrg) }}';
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
    };
}
</script>
@endpush
@endsection
