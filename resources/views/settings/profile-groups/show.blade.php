@extends('layouts.admin')

@section('title', $profileGroup->name . ' - ' . __('Profile Groups'))

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.profile-groups.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Profile Groups') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $profileGroup->name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center">
            <div class="w-14 h-14 rounded-lg flex items-center justify-center text-white font-bold text-2xl"
                 style="background-color: {{ $profileGroup->color ?? '#3B82F6' }}">
                {{ strtoupper(substr($profileGroup->name, 0, 1)) }}
            </div>
            <div class="ml-4">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $profileGroup->name }}</h1>
                <p class="text-sm text-gray-500">{{ $profileGroup->timezone }} &bull; {{ $profileGroup->language }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('orgs.settings.profile-groups.edit', [$currentOrg, $profileGroup->group_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <form action="{{ route('orgs.settings.profile-groups.destroy', [$currentOrg, $profileGroup->group_id]) }}"
                  method="POST" onsubmit="return confirm('Are you sure you want to delete this profile group?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50">
                    <i class="fas fa-trash mr-2"></i>Delete
                </button>
            </form>
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
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Description --}}
            @if($profileGroup->description)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Description</h3>
                    <p class="text-sm text-gray-600">{{ $profileGroup->description }}</p>
                </div>
            @endif

            {{-- Social Profiles --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900">Social Profiles</h3>
                    <a href="{{ route('orgs.settings.profile-groups.profiles', [$currentOrg, $profileGroup->group_id]) }}"
                       class="text-sm text-blue-600 hover:text-blue-700">
                        Manage Profiles
                    </a>
                </div>
                <div class="p-6">
                    @if($profileGroup->socialIntegrations->count() > 0)
                        <div class="space-y-3">
                            @foreach($profileGroup->socialIntegrations as $integration)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fab fa-{{ strtolower($integration->platform ?? 'globe') }} text-xl text-gray-600 w-8"></i>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $integration->account_name ?? 'Unknown' }}</p>
                                            <p class="text-xs text-gray-500">{{ ucfirst($integration->platform ?? 'Unknown') }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full {{ ($integration->status ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                        {{ ucfirst($integration->status ?? 'Active') }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-share-alt text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">No social profiles attached</p>
                            <a href="{{ route('orgs.settings.profile-groups.profiles', [$currentOrg, $profileGroup->group_id]) }}"
                               class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                                <i class="fas fa-plus mr-1"></i>Add Profiles
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Team Members --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-medium text-gray-900">Team Members</h3>
                    <a href="{{ route('orgs.settings.profile-groups.members', [$currentOrg, $profileGroup->group_id]) }}"
                       class="text-sm text-blue-600 hover:text-blue-700">
                        Manage Members
                    </a>
                </div>
                <div class="p-6">
                    @if($profileGroup->members->count() > 0)
                        <div class="space-y-3">
                            @foreach($profileGroup->members as $member)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white text-sm font-medium">
                                            {{ strtoupper(substr($member->user->name ?? 'U', 0, 1)) }}
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-gray-900">{{ $member->user->name ?? 'Unknown User' }}</p>
                                            <p class="text-xs text-gray-500">{{ $member->user->email ?? '' }}</p>
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">
                                        {{ ucfirst($member->role) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-6">
                            <i class="fas fa-users text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">No team members assigned</p>
                            <a href="{{ route('orgs.settings.profile-groups.members', [$currentOrg, $profileGroup->group_id]) }}"
                               class="mt-2 inline-flex items-center text-sm text-blue-600 hover:text-blue-700">
                                <i class="fas fa-plus mr-1"></i>Add Members
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Configuration Cards --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Configuration</h3>

                {{-- Brand Voice --}}
                <div class="mb-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Brand Voice</span>
                        @if($profileGroup->brandVoice)
                            <span class="text-sm font-medium text-gray-900">{{ $profileGroup->brandVoice->name }}</span>
                        @else
                            <span class="text-sm text-gray-400">Not set</span>
                        @endif
                    </div>
                </div>

                {{-- Brand Safety --}}
                <div class="mb-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Safety Policy</span>
                        @if($profileGroup->brandSafetyPolicy)
                            <span class="text-sm font-medium text-gray-900">{{ $profileGroup->brandSafetyPolicy->name }}</span>
                        @else
                            <span class="text-sm text-gray-400">Not set</span>
                        @endif
                    </div>
                </div>

                {{-- Link Shortener --}}
                <div class="mb-4 pb-4 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Link Shortener</span>
                        <span class="text-sm font-medium text-gray-900">{{ $profileGroup->default_link_shortener ?? 'None' }}</span>
                    </div>
                </div>

                {{-- Created By --}}
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Created by</span>
                        <span class="text-sm font-medium text-gray-900">{{ $profileGroup->creator->name ?? 'Unknown' }}</span>
                    </div>
                    <p class="text-xs text-gray-400 text-right mt-1">{{ $profileGroup->created_at->format('M d, Y') }}</p>
                </div>
            </div>

            {{-- Related Resources --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Resources</h3>
                <div class="space-y-2">
                    <a href="{{ route('orgs.settings.approval-workflows.index', $currentOrg) }}?profile_group={{ $profileGroup->group_id }}"
                       class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50 text-sm">
                        <span class="text-gray-600"><i class="fas fa-tasks mr-2 text-gray-400"></i>Approval Workflows</span>
                        <span class="text-gray-400">{{ $profileGroup->approvalWorkflows->count() }}</span>
                    </a>
                    <a href="{{ route('orgs.settings.boost-rules.index', $currentOrg) }}?profile_group={{ $profileGroup->group_id }}"
                       class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50 text-sm">
                        <span class="text-gray-600"><i class="fas fa-rocket mr-2 text-gray-400"></i>Boost Rules</span>
                        <span class="text-gray-400">{{ $profileGroup->boostRules->count() }}</span>
                    </a>
                    <a href="{{ route('orgs.settings.ad-accounts.index', $currentOrg) }}?profile_group={{ $profileGroup->group_id }}"
                       class="flex items-center justify-between p-2 rounded-md hover:bg-gray-50 text-sm">
                        <span class="text-gray-600"><i class="fas fa-ad mr-2 text-gray-400"></i>Ad Accounts</span>
                        <span class="text-gray-400">{{ $profileGroup->adAccounts->count() }}</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
