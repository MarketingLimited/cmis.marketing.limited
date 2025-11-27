@extends('layouts.admin')

@section('title', $brandVoice->name . ' - ' . __('Brand Voices'))

@section('content')
<div class="space-y-6">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.brand-voices.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Brand Voices') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $brandVoice->name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center">
            <div class="w-14 h-14 rounded-lg bg-purple-100 flex items-center justify-center">
                <i class="fas fa-microphone text-purple-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $brandVoice->name }}</h1>
                <p class="text-sm text-gray-500">{{ ucfirst($brandVoice->tone) }} tone &bull; {{ strtoupper($brandVoice->primary_language) }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('orgs.settings.brand-voices.edit', [$currentOrg, $brandVoice->voice_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-edit mr-2"></i>Edit
            </a>
            <form action="{{ route('orgs.settings.brand-voices.destroy', [$currentOrg, $brandVoice->voice_id]) }}"
                  method="POST" onsubmit="return confirm('Delete this brand voice?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50">
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
            @if($brandVoice->description)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Description</h3>
                    <p class="text-sm text-gray-600">{{ $brandVoice->description }}</p>
                </div>
            @endif

            {{-- Personality --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Personality Traits</h3>
                @if($brandVoice->personality_traits && count($brandVoice->personality_traits) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($brandVoice->personality_traits as $trait)
                            <span class="px-3 py-1 bg-purple-100 text-purple-700 text-sm rounded-full">{{ $trait }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">No personality traits defined</p>
                @endif
            </div>

            {{-- Keywords --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Content Guidelines</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-green-700 mb-2"><i class="fas fa-check mr-1"></i>Keywords to Use</h4>
                        @if($brandVoice->keywords_to_use && count($brandVoice->keywords_to_use) > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach($brandVoice->keywords_to_use as $keyword)
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded">{{ $keyword }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400">None specified</p>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-red-700 mb-2"><i class="fas fa-times mr-1"></i>Keywords to Avoid</h4>
                        @if($brandVoice->keywords_to_avoid && count($brandVoice->keywords_to_avoid) > 0)
                            <div class="flex flex-wrap gap-1">
                                @foreach($brandVoice->keywords_to_avoid as $keyword)
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs rounded">{{ $keyword }}</span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-gray-400">None specified</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- AI Prompt --}}
            @if($brandVoice->ai_system_prompt)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Custom AI System Prompt</h3>
                    <pre class="text-xs bg-gray-50 p-4 rounded-md overflow-x-auto text-gray-700">{{ $brandVoice->ai_system_prompt }}</pre>
                </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Settings</h3>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Tone</dt>
                        <dd class="font-medium text-gray-900">{{ ucfirst($brandVoice->tone) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Emoji Usage</dt>
                        <dd class="font-medium text-gray-900">{{ ucfirst($brandVoice->emojis_preference) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Hashtags</dt>
                        <dd class="font-medium text-gray-900">{{ ucfirst($brandVoice->hashtag_strategy) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Language</dt>
                        <dd class="font-medium text-gray-900">{{ strtoupper($brandVoice->primary_language) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Creativity</dt>
                        <dd class="font-medium text-gray-900">{{ $brandVoice->temperature ?? 0.7 }}</dd>
                    </div>
                </dl>
            </div>

            @if($brandVoice->target_audience)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h3 class="text-base font-medium text-gray-900 mb-2">Target Audience</h3>
                    <p class="text-sm text-gray-600">{{ $brandVoice->target_audience }}</p>
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h3 class="text-base font-medium text-gray-900 mb-4">Usage</h3>
                <p class="text-sm text-gray-600">
                    Used by <strong>{{ $brandVoice->profileGroups->count() }}</strong> profile group(s)
                </p>
                @if($brandVoice->profileGroups->count() > 0)
                    <ul class="mt-2 space-y-1">
                        @foreach($brandVoice->profileGroups as $group)
                            <li class="text-sm">
                                <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $group->group_id]) }}"
                                   class="text-purple-600 hover:text-purple-700">{{ $group->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
