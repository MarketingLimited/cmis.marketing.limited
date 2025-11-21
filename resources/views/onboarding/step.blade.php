@extends('layouts.app')

@section('title', __('onboarding.step_x', ['number' => $step]))

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4" x-data="onboardingStep()">
    {{-- Progress Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-2">
            <a href="{{ route('onboarding.index') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ __('onboarding.back_to_overview') }}
            </a>
            <span class="text-sm text-gray-600">
                {{ __('onboarding.step_x_of_y', ['current' => $step, 'total' => count($all_steps)]) }}
            </span>
        </div>

        {{-- Progress Bar --}}
        <div class="w-full bg-gray-200 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all duration-500"
                 style="width: {{ ($step / count($all_steps)) * 100 }}%"></div>
        </div>
    </div>

    {{-- Step Content Card --}}
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8 text-white">
            <div class="flex items-center mb-3">
                <div class="flex items-center justify-center w-12 h-12 rounded-full bg-white bg-opacity-20 mr-4">
                    <span class="text-2xl font-bold">{{ $step }}</span>
                </div>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold mb-1">{{ $step_definition['title'] }}</h1>
                    <p class="text-blue-100">{{ $step_definition['description'] }}</p>
                </div>
            </div>
            <div class="flex items-center text-sm text-blue-100">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('onboarding.estimated_time') }}: {{ $step_definition['estimated_time'] }}
            </div>
        </div>

        {{-- Tasks Checklist --}}
        <div class="px-6 py-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">{{ __('onboarding.tasks_to_complete') }}</h2>
            <div class="space-y-3">
                @foreach($step_definition['tasks'] as $index => $task)
                    <label class="flex items-start p-4 border-2 border-gray-200 rounded-lg hover:border-blue-300 cursor-pointer transition-colors"
                           :class="{'border-green-500 bg-green-50': completedTasks.includes({{ $index }})}">
                        <input type="checkbox" value="{{ $index }}"
                               @change="toggleTask({{ $index }})"
                               :checked="completedTasks.includes({{ $index }})"
                               class="mt-1 rounded text-green-600 focus:ring-green-500">
                        <div class="ml-3 flex-1">
                            <span class="block text-sm font-medium text-gray-900">{{ $task }}</span>
                        </div>
                        <svg x-show="completedTasks.includes({{ $index }})" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </label>
                @endforeach
            </div>
        </div>

        {{-- Helpful Tips --}}
        @if(!empty($tips))
            <div class="px-6 pb-6">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">{{ __('onboarding.helpful_tips') }}</h3>
                            <div class="mt-2 text-sm text-blue-700 space-y-1">
                                @foreach($tips as $tip)
                                    <p>• {{ $tip->tip_text }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Step-Specific Content --}}
        <div class="px-6 pb-6">
            <div class="bg-gray-50 rounded-lg p-6">
                @if($step === 1)
                    {{-- Profile Setup --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('onboarding.profile_details') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('onboarding.profile_setup_description') }}</p>
                        <a href="{{ route('settings.profile') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('onboarding.go_to_profile_settings') }}
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                @elseif($step === 2)
                    {{-- Platform Connection --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('onboarding.connect_platform') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('onboarding.platform_connection_description') }}</p>
                        <a href="{{ route('settings.integrations') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('onboarding.go_to_integrations') }}
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                @elseif($step === 3)
                    {{-- First Campaign --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('onboarding.create_first_campaign') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('onboarding.first_campaign_description') }}</p>
                        <a href="{{ route('campaign.wizard.create') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('onboarding.start_campaign_wizard') }}
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                @elseif($step === 4)
                    {{-- Team Setup --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('onboarding.invite_team') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('onboarding.team_setup_description') }}</p>
                        <a href="{{ route('users.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('onboarding.go_to_team_management') }}
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>

                @elseif($step === 5)
                    {{-- Analytics Tour --}}
                    <div class="space-y-4">
                        <h3 class="text-sm font-semibold text-gray-900">{{ __('onboarding.explore_analytics') }}</h3>
                        <p class="text-sm text-gray-600">{{ __('onboarding.analytics_tour_description') }}</p>
                        <a href="{{ route('analytics.index') }}"
                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            {{ __('onboarding.go_to_analytics') }}
                            <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="px-6 pb-6 flex items-center justify-between border-t pt-6">
            <button type="button" @click="skipStep"
                    class="text-sm text-gray-600 hover:text-gray-900">
                {{ __('onboarding.skip_step') }}
            </button>

            <div class="flex items-center space-x-3">
                @if($step > 1)
                    <a href="{{ route('onboarding.step', $step - 1) }}"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        {{ __('common.previous') }}
                    </a>
                @endif

                <button type="button" @click="completeStep"
                        :disabled="completedTasks.length < {{ count($step_definition['tasks']) }}"
                        :class="completedTasks.length >= {{ count($step_definition['tasks']) }} ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-300 cursor-not-allowed'"
                        class="px-6 py-2 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                    @if($step < count($all_steps))
                        {{ __('onboarding.complete_and_continue') }}
                    @else
                        {{ __('onboarding.finish_onboarding') }}
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- Progress Indicator --}}
    <div class="mt-8 text-center">
        <p class="text-sm text-gray-600">
            {{ __('onboarding.steps_remaining', ['remaining' => count($all_steps) - $step]) }}
        </p>
    </div>
</div>

@push('scripts')
<script>
function onboardingStep() {
    return {
        completedTasks: [],

        toggleTask(index) {
            const taskIndex = this.completedTasks.indexOf(index);
            if (taskIndex > -1) {
                this.completedTasks.splice(taskIndex, 1);
            } else {
                this.completedTasks.push(index);
            }
        },

        completeStep() {
            if (this.completedTasks.length < {{ count($step_definition['tasks']) }}) {
                alert('{{ __('onboarding.complete_all_tasks') }}');
                return;
            }

            fetch('{{ route('onboarding.complete-step', $step) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    metadata: {
                        completed_tasks: this.completedTasks.length
                    }
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    @if($step < count($all_steps))
                        window.location.href = '{{ route('onboarding.step', $step + 1) }}';
                    @else
                        window.location.href = '{{ route('dashboard.index') }}';
                    @endif
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('common.error_occurred') }}');
            });
        },

        skipStep() {
            if (confirm('{{ __('onboarding.confirm_skip') }}')) {
                fetch('{{ route('onboarding.skip-step', $step) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        @if($step < count($all_steps))
                            window.location.href = '{{ route('onboarding.step', $step + 1) }}';
                        @else
                            window.location.href = '{{ route('dashboard.index') }}';
                        @endif
                    }
                });
            }
        }
    }
}
</script>
@endpush
@endsection
