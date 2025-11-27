@extends('layouts.admin')

@section('title', $policy->name . ' - ' . __('Brand Safety'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.brand-safety.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Brand Safety') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $policy->name }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <i class="fas fa-shield-alt text-purple-600 text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $policy->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $policy->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $policy->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $policy->severity_level === 'block' ? 'bg-red-100 text-red-700' : ($policy->severity_level === 'warning' ? 'bg-yellow-100 text-yellow-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ ucfirst($policy->severity_level) }}
                    </span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('orgs.settings.brand-safety.edit', [$currentOrg, $policy->policy_id]) }}"
               class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium">
                <i class="fas fa-edit mr-1"></i>Edit
            </a>
            <form action="{{ route('orgs.settings.brand-safety.destroy', [$currentOrg, $policy->policy_id]) }}" method="POST"
                  onsubmit="return confirm('Delete this brand safety policy?');">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded-lg hover:bg-red-50 transition text-sm font-medium">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </form>
        </div>
    </div>

    @if($policy->description)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600">{{ $policy->description }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Blocked Words --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-ban text-red-500 mr-2"></i>Blocked Words
            </h3>
            @if($policy->blocked_words && count($policy->blocked_words) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($policy->blocked_words as $word)
                        <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm">{{ $word }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No blocked words defined</p>
            @endif
        </div>

        {{-- Required Elements --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>Required Elements
            </h3>
            @if($policy->required_elements && count($policy->required_elements) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($policy->required_elements as $element)
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">{{ $element }}</span>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-500">No required elements defined</p>
            @endif
        </div>
    </div>

    {{-- Content Rules --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-ruler text-blue-500 mr-2"></i>Content Rules
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Block URLs</span>
                    @if($policy->rules['block_urls'] ?? false)
                        <i class="fas fa-check-circle text-green-500"></i>
                    @else
                        <i class="fas fa-times-circle text-gray-400"></i>
                    @endif
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Block Competitors</span>
                    @if($policy->rules['block_competitors'] ?? false)
                        <i class="fas fa-check-circle text-green-500"></i>
                    @else
                        <i class="fas fa-times-circle text-gray-400"></i>
                    @endif
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Require Disclosure</span>
                    @if($policy->rules['require_disclosure'] ?? false)
                        <i class="fas fa-check-circle text-green-500"></i>
                    @else
                        <i class="fas fa-times-circle text-gray-400"></i>
                    @endif
                </div>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600">Check Sentiment</span>
                    @if($policy->rules['check_sentiment'] ?? false)
                        <i class="fas fa-check-circle text-green-500"></i>
                    @else
                        <i class="fas fa-times-circle text-gray-400"></i>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Character Limits --}}
    @if(($policy->rules['min_characters'] ?? 0) > 0 || ($policy->rules['max_characters'] ?? null))
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-text-width text-yellow-500 mr-2"></i>Character Limits
            </h3>

            <div class="flex items-center gap-8">
                @if(($policy->rules['min_characters'] ?? 0) > 0)
                    <div>
                        <span class="text-sm text-gray-500">Minimum:</span>
                        <span class="ml-2 text-lg font-semibold text-gray-900">{{ number_format($policy->rules['min_characters']) }}</span>
                    </div>
                @endif
                @if($policy->rules['max_characters'] ?? null)
                    <div>
                        <span class="text-sm text-gray-500">Maximum:</span>
                        <span class="ml-2 text-lg font-semibold text-gray-900">{{ number_format($policy->rules['max_characters']) }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Associated Profile Group --}}
    @if($policy->profileGroup)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-layer-group text-indigo-500 mr-2"></i>Associated Profile Group
            </h3>
            <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $policy->profileGroup->group_id]) }}"
               class="inline-flex items-center gap-3 p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $policy->profileGroup->name }}</p>
                    <p class="text-xs text-gray-500">{{ $policy->profileGroup->profiles_count ?? 0 }} profiles</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 ml-auto"></i>
            </a>
        </div>
    @endif

    {{-- Test Content --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-vial text-purple-500 mr-2"></i>Test Content
        </h3>
        <p class="text-sm text-gray-500 mb-4">Test content against this policy to see if it would pass.</p>

        <form action="{{ route('orgs.settings.brand-safety.validate', [$currentOrg, $policy->policy_id]) }}" method="POST"
              x-data="{ result: null, testing: false }"
              @submit.prevent="testing = true; fetch($el.action, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, body: JSON.stringify({ content: $refs.testContent.value }) }).then(r => r.json()).then(data => { result = data; testing = false; })">
            <textarea x-ref="testContent" rows="3" placeholder="Enter content to test..."
                      class="w-full rounded-lg border-gray-300 focus:ring-purple-500 focus:border-purple-500 mb-3"></textarea>
            <button type="submit" :disabled="testing"
                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition text-sm font-medium disabled:opacity-50">
                <span x-show="!testing"><i class="fas fa-check-double mr-1"></i>Validate Content</span>
                <span x-show="testing"><i class="fas fa-spinner fa-spin mr-1"></i>Testing...</span>
            </button>

            <div x-show="result" class="mt-4 p-4 rounded-lg" :class="result?.passed ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                <div class="flex items-center gap-2">
                    <i :class="result?.passed ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-triangle text-red-500'"></i>
                    <span class="font-medium" :class="result?.passed ? 'text-green-700' : 'text-red-700'" x-text="result?.passed ? 'Content passes policy' : 'Content violates policy'"></span>
                </div>
                <template x-if="result?.issues && result.issues.length > 0">
                    <ul class="mt-2 text-sm text-red-600 space-y-1">
                        <template x-for="issue in result.issues" :key="issue">
                            <li class="flex items-start gap-1">
                                <i class="fas fa-times mt-0.5"></i>
                                <span x-text="issue"></span>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>
        </form>
    </div>

    {{-- Metadata --}}
    <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-500">
        <div class="flex flex-wrap gap-6">
            <div>
                <span class="text-gray-400">Created:</span>
                <span class="ml-1">{{ $policy->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Last Updated:</span>
                <span class="ml-1">{{ $policy->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Policy ID:</span>
                <span class="ml-1 font-mono">{{ $policy->policy_id }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
