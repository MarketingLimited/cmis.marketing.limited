@extends('layouts.admin')

@section('title', __('Profiles') . ' - ' . $profileGroup->name)

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
            <span class="text-gray-900 font-medium">{{ __('Social Profiles') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Social Profiles</h1>
            <p class="mt-1 text-sm text-gray-500">
                Manage social media profiles attached to {{ $profileGroup->name }}
            </p>
        </div>
        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
           class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <i class="fas fa-plug mr-2"></i>
            Connect New Profile
        </a>
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
        {{-- Attached Profiles --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-base font-medium text-gray-900">Attached Profiles ({{ $profiles->count() }})</h3>
                </div>
                <div class="divide-y divide-gray-200">
                    @forelse($profiles as $profile)
                        <div class="px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center">
                                @php
                                    $platform = strtolower($profile->platform ?? '');
                                    $platformConfig = [
                                        'facebook' => ['icon' => 'fab fa-facebook', 'color' => 'text-blue-600', 'bg' => 'bg-blue-50', 'label' => 'Facebook'],
                                        'instagram' => ['icon' => 'fab fa-instagram', 'color' => 'text-pink-600', 'bg' => 'bg-pink-50', 'label' => 'Instagram'],
                                        'threads' => ['icon' => 'fab fa-threads', 'color' => 'text-gray-900', 'bg' => 'bg-gray-100', 'label' => 'Threads'],
                                        'twitter' => ['icon' => 'fab fa-twitter', 'color' => 'text-sky-500', 'bg' => 'bg-sky-50', 'label' => 'Twitter'],
                                        'linkedin' => ['icon' => 'fab fa-linkedin', 'color' => 'text-blue-700', 'bg' => 'bg-blue-50', 'label' => 'LinkedIn'],
                                        'tiktok' => ['icon' => 'fab fa-tiktok', 'color' => 'text-gray-900', 'bg' => 'bg-gray-100', 'label' => 'TikTok'],
                                        'youtube' => ['icon' => 'fab fa-youtube', 'color' => 'text-red-600', 'bg' => 'bg-red-50', 'label' => 'YouTube'],
                                        'google_business' => ['icon' => 'fab fa-google', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'label' => 'Google Business'],
                                        'google' => ['icon' => 'fab fa-google', 'color' => 'text-blue-500', 'bg' => 'bg-blue-50', 'label' => 'Google'],
                                        'pinterest' => ['icon' => 'fab fa-pinterest', 'color' => 'text-red-500', 'bg' => 'bg-red-50', 'label' => 'Pinterest'],
                                    ];
                                    $config = $platformConfig[$platform] ?? ['icon' => 'fas fa-globe', 'color' => 'text-gray-500', 'bg' => 'bg-gray-100', 'label' => ucfirst($platform)];
                                @endphp
                                <div class="w-10 h-10 rounded-full {{ $config['bg'] }} flex items-center justify-center">
                                    <i class="{{ $config['icon'] }} {{ $config['color'] }} text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">
                                        {{ $profile->account_name ?? $profile->account_username ?? 'Unknown Profile' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $config['label'] }}
                                        @if($profile->account_username && $profile->account_name !== $profile->account_username)
                                            <span class="text-gray-400">• @{{ $profile->account_username }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="px-2 py-1 text-xs rounded-full {{ ($profile->status ?? 'active') === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($profile->status ?? 'Active') }}
                                </span>
                                <form action="{{ route('orgs.settings.profile-groups.profiles.detach', [$currentOrg, $profileGroup->group_id, $profile->integration_id]) }}"
                                      method="POST" onsubmit="return confirm('Remove this profile from the group?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700" title="Remove from group">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-6 py-12 text-center">
                            <i class="fas fa-share-alt text-gray-300 text-3xl mb-2"></i>
                            <p class="text-sm text-gray-500">No profiles attached to this group</p>
                            <p class="text-xs text-gray-400 mt-1">Add profiles from the panel on the right</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Add Profile --}}
        <div>
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Add Profile</h3>
                @if($availableProfiles->count() > 0)
                    <form action="{{ route('orgs.settings.profile-groups.profiles.attach', [$currentOrg, $profileGroup->group_id]) }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label for="integration_id" class="block text-sm font-medium text-gray-700">Available Profiles</label>
                            <select name="integration_id" id="integration_id" required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Choose a profile...</option>
                                @foreach($availableProfiles->groupBy('platform') as $platform => $platformProfiles)
                                    <optgroup label="{{ ucfirst($platform) }}">
                                        @foreach($platformProfiles as $profile)
                                            <option value="{{ $profile->integration_id }}">
                                                {{ $profile->account_name ?? $profile->account_username ?? 'Unknown' }}
                                                @if($profile->account_username)
                                                    (@{{ $profile->account_username }})
                                                @endif
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit"
                                class="w-full px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                            Add to Group
                        </button>
                    </form>
                @else
                    <div class="text-center py-4">
                        <p class="text-sm text-gray-500 mb-3">No available profiles to add.</p>
                        <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
                           class="text-sm text-blue-600 hover:text-blue-700">
                            <i class="fas fa-plug mr-1"></i>Connect new profiles
                        </a>
                    </div>
                @endif
            </div>

            <div class="mt-6 bg-blue-50 rounded-lg p-4">
                <h4 class="text-sm font-medium text-blue-900 mb-2">
                    <i class="fas fa-info-circle mr-1"></i>About Profile Groups
                </h4>
                <p class="text-xs text-blue-700">
                    Profile groups help you organize social accounts by client, brand, or campaign.
                    Content can be published to all profiles in a group simultaneously.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
