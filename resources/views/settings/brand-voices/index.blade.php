@extends('layouts.admin')

@section('title', __('Brand Voices') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Brand Voices') }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Brand Voices</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Define AI-powered brand voices for consistent content generation across your social profiles.
            </p>
        </div>
        <a href="{{ route('orgs.settings.brand-voices.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
            <i class="fas fa-plus mr-2"></i>Create Voice
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

    @if($brandVoices->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($brandVoices as $voice)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex items-center">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 flex items-center justify-center">
                                    <i class="fas fa-microphone text-purple-600"></i>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-base font-semibold text-gray-900">{{ $voice->name }}</h3>
                                    <p class="text-xs text-gray-500">{{ ucfirst($voice->tone) }}</p>
                                </div>
                            </div>
                            <div class="relative" x-data="{ open: false }">
                                <button @click="open = !open" class="p-1 text-gray-400 hover:text-gray-600 rounded">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <div x-show="open" @click.away="open = false"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10">
                                    <div class="py-1">
                                        <a href="{{ route('orgs.settings.brand-voices.show', [$currentOrg, $voice->voice_id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-eye w-4 mr-2"></i>View
                                        </a>
                                        <a href="{{ route('orgs.settings.brand-voices.edit', [$currentOrg, $voice->voice_id]) }}"
                                           class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit w-4 mr-2"></i>Edit
                                        </a>
                                        <form action="{{ route('orgs.settings.brand-voices.destroy', [$currentOrg, $voice->voice_id]) }}"
                                              method="POST" onsubmit="return confirm('Delete this brand voice?');">
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

                        @if($voice->description)
                            <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $voice->description }}</p>
                        @endif

                        {{-- Traits --}}
                        @if($voice->personality_traits && count($voice->personality_traits) > 0)
                            <div class="flex flex-wrap gap-1 mb-4">
                                @foreach(array_slice($voice->personality_traits, 0, 3) as $trait)
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded-full">{{ $trait }}</span>
                                @endforeach
                                @if(count($voice->personality_traits) > 3)
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded-full">+{{ count($voice->personality_traits) - 3 }}</span>
                                @endif
                            </div>
                        @endif

                        {{-- Stats --}}
                        <div class="flex items-center gap-4 text-xs text-gray-500 mb-4">
                            <span><i class="fas fa-globe mr-1"></i>{{ strtoupper($voice->primary_language) }}</span>
                            <span><i class="fas fa-{{ $voice->emojis_preference === 'none' ? 'ban' : 'smile' }} mr-1"></i>{{ ucfirst($voice->emojis_preference) }}</span>
                            <span><i class="fas fa-hashtag mr-1"></i>{{ ucfirst($voice->hashtag_strategy) }}</span>
                        </div>

                        {{-- Usage --}}
                        <div class="text-xs text-gray-400 mb-4">
                            Used by {{ $voice->profile_groups_count ?? 0 }} profile group(s)
                        </div>

                        <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                            <a href="{{ route('orgs.settings.brand-voices.show', [$currentOrg, $voice->voice_id]) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                                View Details
                            </a>
                            <a href="{{ route('orgs.settings.brand-voices.edit', [$currentOrg, $voice->voice_id]) }}"
                               class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-purple-600 hover:bg-purple-700">
                                Edit Voice
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-purple-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-microphone text-purple-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Brand Voices Yet</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                Create brand voices to ensure consistent tone and style across all AI-generated content.
            </p>
            <a href="{{ route('orgs.settings.brand-voices.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-purple-600 hover:bg-purple-700">
                <i class="fas fa-plus mr-2"></i>Create Your First Voice
            </a>
        </div>
    @endif
</div>
@endsection
