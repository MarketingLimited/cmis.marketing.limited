@extends('layouts.admin')

@section('title', __('team.title') . ' - ' . $orgModel->name)

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
@endphp

@section('content')
<div class="space-y-6" x-data="teamManagement()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('team.title') }}</span>
        </nav>
        <div class="flex justify-between items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-2xl font-bold text-gray-900">{{ __('team.title') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('team.subtitle') }} {{ $orgModel->name }}</p>
            </div>
            <button @click="showInviteModal = true"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition duration-200 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>{{ __('team.invite_member') }}</span>
            </button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div class="{{ $isRtl ? 'me-4 text-right' : 'ms-4' }}">
                    <p class="text-sm text-gray-600">{{ __('team.total_members') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_members'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="{{ $isRtl ? 'me-4 text-right' : 'ms-4' }}">
                    <p class="text-sm text-gray-600">{{ __('team.active_members') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['active_members'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="{{ $isRtl ? 'me-4 text-right' : 'ms-4' }}">
                    <p class="text-sm text-gray-600">{{ __('team.pending_invitations') }}</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_invitations'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">{{ __('common.success') }}!</strong>
            <span class="block sm:inline">{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <strong class="font-bold">{{ __('common.error') }}!</strong>
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Team Members Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-900">{{ __('team.team_members') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('team.member') }}</th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('team.role') }}</th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('common.status') }}</th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('team.joined') }}</th>
                        <th class="px-6 py-3 {{ $isRtl ? 'text-left' : 'text-right' }} text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($members as $member)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-blue-400 to-blue-600 flex items-center justify-center text-white font-bold">
                                            {{ strtoupper(substr($member->user?->name ?? $member->user?->email ?? 'UN', 0, 2)) }}
                                        </div>
                                    </div>
                                    <div class="{{ $isRtl ? 'me-4 text-right' : 'ms-4' }}">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $member->user?->name ?? __('common.unknown') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $member->user?->email ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ ($member->role?->role_code ?? '') === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $member->role?->role_name ?? __('team.member') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                    {{ $member->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($member->status ?? 'active') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $member->joined_at ? $member->joined_at->diffForHumans() : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap {{ $isRtl ? 'text-left' : 'text-right' }} text-sm font-medium">
                                @if($member->user_id !== auth()->id())
                                    <button @click="editRole('{{ $member->user_id }}', '{{ $member->role?->role_id ?? '' }}', '{{ $member->user?->name ?? $member->user?->email ?? '' }}')"
                                            class="text-blue-600 hover:text-blue-900 {{ $isRtl ? 'ms-3' : 'me-3' }}">{{ __('team.edit_role') }}</button>
                                    <button @click="removeMember('{{ $member->user_id }}', '{{ $member->user?->name ?? $member->user?->email ?? '' }}')"
                                            class="text-red-600 hover:text-red-900">{{ __('team.remove') }}</button>
                                @else
                                    <span class="text-gray-400">{{ __('team.you') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <p class="mt-2">{{ __('team.no_members') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($members->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    {{-- Pending Invitations --}}
    @if($pendingInvitations->isNotEmpty())
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">{{ __('team.pending_invitations') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('team.email') }}</th>
                            <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('team.role') }}</th>
                            <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('team.sent') }}</th>
                            <th class="px-6 py-3 {{ $isRtl ? 'text-right' : 'text-left' }} text-xs font-medium text-gray-500 uppercase">{{ __('team.expires') }}</th>
                            <th class="px-6 py-3 {{ $isRtl ? 'text-left' : 'text-right' }} text-xs font-medium text-gray-500 uppercase">{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($pendingInvitations as $invitation)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $invitation->invited_email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $roles->firstWhere('role_id', $invitation->role_id)->role_name ?? __('common.unknown') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $invitation->sent_at ? \Carbon\Carbon::parse($invitation->sent_at)->diffForHumans() : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $invitation->expires_at ? \Carbon\Carbon::parse($invitation->expires_at)->diffForHumans() : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap {{ $isRtl ? 'text-left' : 'text-right' }} text-sm font-medium">
                                    <button @click="cancelInvitation('{{ $invitation->invited_email }}')"
                                            class="text-red-600 hover:text-red-900">{{ __('team.cancel') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Invite Modal --}}
    <div x-show="showInviteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showInviteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showInviteModal = false"></div>

            <div x-show="showInviteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <form method="POST" action="{{ route('orgs.team.invite', $orgModel->org_id) }}">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 {{ $isRtl ? 'sm:text-right' : 'sm:text-left' }} w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    {{ __('team.invite_title') }}
                                </h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label for="email" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('team.email_address') }}</label>
                                        <input type="email"
                                               name="email"
                                               id="email"
                                               required
                                               class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="role_id" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('team.role') }}</label>
                                        <select name="role_id"
                                                id="role_id"
                                                required
                                                class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="message" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('team.personal_message') }}</label>
                                        <textarea name="message"
                                                  id="message"
                                                  rows="3"
                                                  class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="{{ __('team.message_placeholder') }}"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $isRtl ? 'sm:me-3' : 'sm:ms-3' }} sm:w-auto sm:text-sm">
                            {{ __('team.send_invitation') }}
                        </button>
                        <button type="button"
                                @click="showInviteModal = false"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 {{ $isRtl ? 'sm:me-3' : 'sm:ms-3' }} sm:w-auto sm:text-sm">
                            {{ __('common.cancel') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Edit Role Modal --}}
    <div x-show="showEditRoleModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="edit-role-modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showEditRoleModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                 @click="showEditRoleModal = false"></div>

            <div x-show="showEditRoleModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 {{ $isRtl ? 'sm:text-right' : 'sm:text-left' }} w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="edit-role-modal-title">
                                {{ __('team.edit_role_title') }}
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <p class="text-sm text-gray-600 mb-4">
                                        {{ __('team.change_role_for') }} <span class="font-semibold" x-text="editingUser.name"></span>
                                    </p>
                                    <label for="edit_role_id" class="block text-sm font-medium text-gray-700 {{ $isRtl ? 'text-right' : '' }}">{{ __('team.new_role') }}</label>
                                    <select x-model="newRoleId"
                                            id="edit_role_id"
                                            class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->role_id }}">{{ $role->role_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="updateRole()"
                            type="button"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 {{ $isRtl ? 'sm:me-3' : 'sm:ms-3' }} sm:w-auto sm:text-sm">
                        {{ __('team.update_role') }}
                    </button>
                    <button @click="showEditRoleModal = false"
                            type="button"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 {{ $isRtl ? 'sm:me-3' : 'sm:ms-3' }} sm:w-auto sm:text-sm">
                        {{ __('common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function teamManagement() {
    return {
        showInviteModal: false,
        showEditRoleModal: false,
        editingUser: {
            id: null,
            name: '',
            currentRoleId: null
        },
        newRoleId: null,

        /**
         * Open edit role modal
         */
        editRole(userId, currentRoleId, userName) {
            this.editingUser = {
                id: userId,
                name: userName,
                currentRoleId: currentRoleId
            };
            this.newRoleId = currentRoleId;
            this.showEditRoleModal = true;
        },

        /**
         * Update member role
         */
        async updateRole() {
            if (!this.newRoleId || this.newRoleId === this.editingUser.currentRoleId) {
                alert('{{ __('team.select_different_role') }}');
                return;
            }

            if (!confirm('{{ __('team.confirm_change_role') }}'.replace(':name', this.editingUser.name))) {
                return;
            }

            try {
                const orgId = '{{ $orgModel->org_id }}';
                const response = await fetch(`/api/orgs/${orgId}/members/${this.editingUser.id}/role`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        role_id: this.newRoleId
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    alert('{{ __('team.role_updated') }}');
                    this.showEditRoleModal = false;
                    window.location.reload();
                } else {
                    alert('{{ __('team.failed_update_role') }}: ' + (data.error || data.message || '{{ __('common.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Error updating role:', error);
                alert('{{ __('team.failed_update_role') }}. {{ __('common.please_try_again') }}');
            }
        },

        /**
         * Remove team member
         */
        async removeMember(userId, userName) {
            if (!confirm('{{ __('team.confirm_remove') }}'.replace(':name', userName))) {
                return;
            }

            try {
                const orgId = '{{ $orgModel->org_id }}';
                const response = await fetch(`/api/orgs/${orgId}/members/${userId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    alert('{{ __('team.member_removed') }}');
                    window.location.reload();
                } else {
                    alert('{{ __('team.failed_remove_member') }}: ' + (data.error || data.message || '{{ __('common.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Error removing member:', error);
                alert('{{ __('team.failed_remove_member') }}. {{ __('common.please_try_again') }}');
            }
        },

        /**
         * Cancel pending invitation
         */
        async cancelInvitation(email) {
            if (!confirm('{{ __('team.confirm_cancel_invitation') }}'.replace(':email', email))) {
                return;
            }

            try {
                const orgId = '{{ $orgModel->org_id }}';
                const response = await fetch(`/api/orgs/${orgId}/invitations/${encodeURIComponent(email)}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    alert('{{ __('team.invitation_cancelled') }}');
                    window.location.reload();
                } else {
                    alert('{{ __('team.failed_cancel_invitation') }}: ' + (data.error || data.message || '{{ __('common.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Error cancelling invitation:', error);
                alert('{{ __('team.failed_cancel_invitation') }}. {{ __('common.please_try_again') }}');
            }
        }
    }
}
</script>
@endpush
@endsection
