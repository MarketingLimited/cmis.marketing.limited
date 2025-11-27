@extends('layouts.admin')

@section('title', __('Members') . ' - ' . $profileGroup->name)

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Profile Groups') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $profileGroup->group_id]) }}" class="hover:text-blue-600 transition">{{ $profileGroup->name }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Members') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Team Members</h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage team members who can access {{ $profileGroup->name }}
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Members List --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Current Members ({{ $members->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($members as $member)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-medium">
                                    {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">{{ $member->user->name ?? 'Unknown User' }}</p>
                                    <p class="text-xs text-gray-500">{{ $member->user->email ?? '' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <form action="{{ route('orgs.settings.profile-groups.members.update', [$currentOrg, $profileGroup->group_id, $member->id]) }}"
                                      method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <select name="role" onchange="this.form.submit()"
                                            class="text-sm border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="admin" {{ $member->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        <option value="editor" {{ $member->role === 'editor' ? 'selected' : '' }}>Editor</option>
                                        <option value="viewer" {{ $member->role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                    </select>
                                </form>
                                <form action="{{ route('orgs.settings.profile-groups.members.remove', [$currentOrg, $profileGroup->group_id, $member->id]) }}"
                                      method="POST" onsubmit="return confirm('Remove this member?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <i class="fas fa-users text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">No members yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Add Member Form --}}
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Add Member</h3>
                @if($availableUsers->count() > 0)
                    <form action="{{ route('orgs.settings.profile-groups.members.add', [$currentOrg, $profileGroup->group_id]) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700">Select User</label>
                            <select name="user_id" id="user_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Choose a user...</option>
                                @foreach($availableUsers as $user)
                                    <option value="{{ $user->user_id }}">{{ $user->name }} ({{ $user->email }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700">Role</label>
                            <select name="role" id="role" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="editor">Editor - Can create and edit content</option>
                                <option value="viewer">Viewer - Can only view content</option>
                                <option value="admin">Admin - Full access to group settings</option>
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Add Member
                        </button>
                    </form>
                @else
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500">All organization members have been added to this group.</p>
                    </div>
                @endif
            </div>

            {{-- Role Descriptions --}}
            <div class="mt-6 bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Role Permissions</h4>
                <div class="space-y-2 text-xs text-gray-600">
                    <div><strong>Admin:</strong> Full access - manage members, settings, content</div>
                    <div><strong>Editor:</strong> Create, edit, and schedule content</div>
                    <div><strong>Viewer:</strong> View-only access to content and analytics</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
