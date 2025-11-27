@extends('layouts.admin')

@section('title', __('Profile Groups') . ' - ' . __('Settings'))

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
            <span class="text-gray-900 font-medium">{{ __('Profile Groups') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Profile Groups</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Organize your social media profiles into groups for easier management and publishing.
            </p>
        </div>
        <a href="{{ route('orgs.settings.profile-groups.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
            <i class="fas fa-plus mr-2"></i>
            Create Group
        </a>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex">
                <i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Profile Groups Grid --}}
    @if($profileGroups->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($profileGroups as $group)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="p-5">
                        {{-- Group Header --}}
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white font-bold text-lg"
                                     style="background-color: {{ $group->color ?? '#3B82F6' }}">
                                    {{ strtoupper(substr($group->name, 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-base font-semibold text-gray-900">{{ $group->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ $group->timezone }}</p>
                                </div>
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" @click.away="open = false"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="py-1">
                                        <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $group->group_id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-eye w-4 mr-2"></i>View Details
                                        </a>
                                        <a href="{{ route('orgs.settings.profile-groups.edit', [$currentOrg, $group->group_id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit w-4 mr-2"></i>Edit
                                        </a>
                                        <a href="{{ route('orgs.settings.profile-groups.members', [$currentOrg, $group->group_id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-users w-4 mr-2"></i>Manage Members
                                        </a>
                                        <a href="{{ route('orgs.settings.profile-groups.profiles', [$currentOrg, $group->group_id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-share-alt w-4 mr-2"></i>Manage Profiles
                                        </a>
                                        <form action="{{ route('orgs.settings.profile-groups.destroy', [$currentOrg, $group->group_id]) }}"
                                              method="POST" onsubmit="return confirm('Are you sure you want to delete this profile group?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <i class="fas fa-trash w-4 mr-2"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Description --}}
                        @if($group->description)
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $group->description }}</p>
                        @endif

                        {{-- Stats --}}
                        <div class="flex items-center gap-4 mb-4 text-xs text-gray-500">
                            <span class="flex items-center">
                                <i class="fas fa-share-alt mr-1"></i>
                                {{ $group->social_integrations_count ?? 0 }} profiles
                            </span>
                            <span class="flex items-center">
                                <i class="fas fa-users mr-1"></i>
                                {{ $group->members_count ?? 0 }} members
                            </span>
                        </div>

                        {{-- Features --}}
                        <div class="flex flex-wrap gap-2 mb-4">
                            @if($group->brandVoice)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-purple-100 text-purple-700">
                                    <i class="fas fa-microphone mr-1"></i>Brand Voice
                                </span>
                            @endif
                            @if($group->brandSafetyPolicy)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-700">
                                    <i class="fas fa-shield-alt mr-1"></i>Safety Policy
                                </span>
                            @endif
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $group->group_id]) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Details
                            </a>
                            <a href="{{ route('orgs.settings.profile-groups.edit', [$currentOrg, $group->group_id]) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-blue-600 hover:bg-blue-700">
                                Edit Group
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-layer-group text-gray-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Profile Groups Yet</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                Create profile groups to organize your social media profiles by client, brand, or team.
            </p>
            <a href="{{ route('orgs.settings.profile-groups.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>
                Create Your First Group
            </a>
        </div>
    @endif
</div>
@endsection
