@extends('layouts.app')

@section('title', __('onboarding.welcome'))

@section('content')
@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp
<div class="max-w-4xl mx-auto py-8 px-4" x-data="onboardingDashboard()">
    {{-- Welcome Header --}}
    <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg shadow-lg p-8 text-white mb-8">
        <h1 class="text-3xl font-bold mb-2">
            {{ __('onboarding.welcome_title', ['name' => auth()->user()->name]) }}
        </h1>
        <p class="text-lg opacity-90">
            {{ __('onboarding.welcome_message') }}
        </p>
    </div>

    {{-- Progress Overview --}}
    <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold text-gray-900">
                {{ __('onboarding.your_progress') }}
            </h2>
            <span class="text-2xl font-bold text-blue-600">
                {{ $progress['percentage'] }}%
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="w-full bg-gray-200 rounded-full h-4 mb-6">
            <div class="bg-blue-600 h-4 rounded-full transition-all duration-500"
                 style="width: {{ $progress['percentage'] }}%"></div>
        </div>

        <p class="text-sm text-gray-600">
            {{ __('onboarding.steps_completed', [
                'completed' => $progress['completed_steps'],
                'total' => $progress['total_steps']
            ]) }}
        </p>
    </div>

    {{-- Onboarding Steps --}}
    <div class="space-y-4">
        @foreach($steps as $step)
            <div class="bg-white rounded-lg shadow-sm overflow-hidden
                {{ in_array($step['number'], $progress['completed_steps_list']) ? 'opacity-75' : '' }}">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex items-start space-x-4 flex-1">
                            {{-- Step Icon --}}
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 rounded-full flex items-center justify-center
                                    {{ in_array($step['number'], $progress['completed_steps_list'])
                                        ? 'bg-green-100 text-green-600'
                                        : ($step['number'] === $progress['current_step']
                                            ? 'bg-blue-100 text-blue-600'
                                            : 'bg-gray-100 text-gray-400') }}">
                                    @if(in_array($step['number'], $progress['completed_steps_list']))
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <span class="text-lg font-semibold">{{ $step['number'] }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Step Content --}}
                            <div class="flex-1">
                                <div class="flex items-center space-x-2 mb-1">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ $step['title'] }}
                                    </h3>
                                    @if(in_array($step['number'], $progress['completed_steps_list']))
                                        <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded">
                                            {{ __('onboarding.completed') }}
                                        </span>
                                    @elseif($step['number'] === $progress['current_step'])
                                        <span class="px-2 py-1 text-xs font-medium text-blue-800 bg-blue-100 rounded">
                                            {{ __('onboarding.in_progress') }}
                                        </span>
                                    @endif
                                </div>

                                <p class="text-gray-600 mb-3">{{ $step['description'] }}</p>

                                <ul class="space-y-1">
                                    @foreach($step['tasks'] as $task)
                                        <li class="flex items-center text-sm text-gray-600">
                                            <svg class="w-4 h-4 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                            </svg>
                                            {{ $task }}
                                        </li>
                                    @endforeach
                                </ul>

                                <div class="mt-3 flex items-center text-sm text-gray-500">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $step['estimated_time'] }}
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="ml-4 flex-shrink-0">
                            @if(!in_array($step['number'], $progress['completed_steps_list']))
                                <a href="{{ route('onboarding.step', $step['number']) }}"
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md
                                          {{ $step['number'] === $progress['current_step']
                                              ? 'text-white bg-blue-600 hover:bg-blue-700'
                                              : 'text-blue-600 bg-blue-50 hover:bg-blue-100' }}">
                                    {{ $step['number'] === $progress['current_step'] ? __('onboarding.continue') : __('onboarding.start') }}
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Contextual Tips --}}
    @if(!empty($tips))
        <div class="mt-8 bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-medium text-blue-800">{{ __('onboarding.helpful_tips') }}</h3>
                    <div class="mt-2 text-sm text-blue-700 space-y-1">
                        @foreach($tips as $tip)
                            <p>• {{ $tip->tip_text }}</p>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Skip All Button --}}
    <div class="mt-8 text-center">
        <button @click="dismissOnboarding"
                class="text-sm text-gray-500 hover:text-gray-700 underline">
            {{ __('onboarding.skip_for_now') }}
        </button>
    </div>
</div>

@push('scripts')
<script>
function onboardingDashboard() {
    return {
        dismissOnboarding() {
            if (confirm('{{ __('onboarding.confirm_dismiss') }}')) {
                fetch('{{ route('onboarding.dismiss') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = '{{ route('dashboard') }}';
                    }
                });
            }
        }
    }
}
</script>
@endpush
@endsection
