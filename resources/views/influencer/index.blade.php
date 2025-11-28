@extends('layouts.admin')

@section('title', __('influencer.title'))

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('influencer.manage_influencers') }}</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ __('influencer.manage_partnerships') }}</p>
        </div>
        <a href="{{ route('orgs.influencer.create', ['org' => $currentOrg]) }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
            <i class="fas fa-plus me-2"></i>
            {{ __('influencer.add_new') }}
        </a>
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg relative" role="alert">
        <span class="block sm:inline">{{ session('success') }}</span>
    </div>
    @endif

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('influencer.total_influencers') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $influencers->total() }}</p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <i class="fas fa-users text-blue-600 dark:text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('influencer.active') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $influencers->where('status', 'active')->count() }}</p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <i class="fas fa-check-circle text-green-600 dark:text-green-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('influencer.partnerships') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $influencers->sum(fn($i) => $i->partnerships->count()) }}</p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <i class="fas fa-handshake text-purple-600 dark:text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ __('influencer.total_followers') }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($influencers->sum('total_followers')) }}</p>
                </div>
                <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-full">
                    <i class="fas fa-star text-yellow-600 dark:text-yellow-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Influencers Table -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('influencer.influencer') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('influencer.category') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('influencer.followers') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('influencer.engagement_rate') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('common.status') }}
                        </th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            {{ __('common.actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($influencers as $influencer)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($influencer->profile_image)
                                <img class="h-10 w-10 rounded-full object-cover" src="{{ $influencer->profile_image }}" alt="{{ $influencer->full_name }}">
                                @else
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">{{ substr($influencer->full_name, 0, 1) }}</span>
                                </div>
                                @endif
                                <div class="me-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $influencer->full_name }}
                                    </div>
                                    @if($influencer->location)
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <i class="fas fa-map-marker-alt text-xs"></i> {{ $influencer->location }}
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($influencer->tier === 'mega') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @elseif($influencer->tier === 'macro') bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200
                                @elseif($influencer->tier === 'mid') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                @elseif($influencer->tier === 'micro') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                @endif">
                                {{ ucfirst($influencer->tier) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <i class="fas fa-users text-gray-400 me-1"></i>
                            {{ number_format($influencer->total_followers) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            <i class="fas fa-heart text-pink-500 me-1"></i>
                            {{ number_format($influencer->avg_engagement_rate, 2) }}%
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                @if($influencer->status === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                @elseif($influencer->status === 'inactive') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                @else bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                @endif">
                                {{ $influencer->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="{{ route('orgs.influencer.show', ['org' => $currentOrg, 'influencer' => $influencer->influencer_id]) }}"
                               class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 ms-3">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('orgs.influencer.edit', ['org' => $currentOrg, 'influencer' => $influencer->influencer_id]) }}"
                               class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 ms-3">
                                <i class="fas fa-edit"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-users text-gray-400 text-5xl mb-4"></i>
                                <p class="text-gray-500 dark:text-gray-400 text-lg mb-2">{{ __('influencer.no_influencers_yet') }}</p>
                                <p class="text-gray-400 dark:text-gray-500 text-sm mb-4">{{ __('influencer.start_adding_influencers') }}</p>
                                <a href="{{ route('orgs.influencer.create', ['org' => $currentOrg]) }}"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                    <i class="fas fa-plus me-2"></i>
                                    {{ __('influencer.add_new') }}
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($influencers->hasPages())
        <div class="bg-white dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700 sm:px-6">
            {{ $influencers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
