@extends('layouts.admin')

@section('title', __('campaigns.wizard.title'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="space-y-6" x-data="campaignWizard()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Create Campaign') }}</span>
        </nav>
    </div>
    {{-- Progress Bar --}}
    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold text-gray-900">
                {{ __('campaigns.wizard.create_campaign') }}
            </h1>
            <span class="text-sm text-gray-600">
                {{ __('campaigns.wizard.step_x_of_y', ['current' => $current_step, 'total' => count($all_steps)]) }}
            </span>
        </div>

        {{-- Progress Steps --}}
        <div class="flex items-center justify-between">
            @foreach($all_steps as $stepNum => $step)
                <div class="flex-1 {{ $stepNum < count($all_steps) ? 'mr-2' : '' }}">
                    <div class="flex items-center">
                        {{-- Step Circle --}}
                        <div class="flex items-center justify-center w-10 h-10 rounded-full
                            @if(in_array($stepNum, $session['completed_steps']))
                                bg-green-500 text-white
                            @elseif($stepNum === $current_step)
                                bg-blue-500 text-white
                            @else
                                bg-gray-300 text-gray-600
                            @endif
                        ">
                            @if(in_array($stepNum, $session['completed_steps']))
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                {{ $stepNum }}
                            @endif
                        </div>

                        {{-- Progress Line --}}
                        @if($stepNum < count($all_steps))
                            <div class="flex-1 h-1 mx-2
                                @if(in_array($stepNum, $session['completed_steps']))
                                    bg-green-500
                                @else
                                    bg-gray-300
                                @endif
                            "></div>
                        @endif
                    </div>
                    <div class="mt-2 text-xs text-center text-gray-600">
                        {{ $step['key'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">{{ __('common.errors_occurred') }}</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Wizard Form --}}
    <form method="POST" action="{{ route('campaign.wizard.update', ['session_id' => $session_id, 'step' => $current_step]) }}" class="bg-white shadow-sm rounded-lg p-6">
        @csrf

        <h2 class="text-xl font-semibold text-gray-900 mb-6">
            {{ __('campaigns.wizard.step_' . $current_step . '_title') }}
        </h2>

        {{-- Step-specific content --}}
        @includeIf('campaigns.wizard.steps.step' . $current_step, ['session' => $session, 'step_data' => $step_data])

        {{-- Navigation Buttons --}}
        <div class="mt-8 flex items-center justify-between border-t pt-6">
            <div>
                @if($current_step > 1)
                    <button type="submit" name="action" value="previous"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('common.previous') }}
                    </button>
                @endif
            </div>

            <div class="flex items-center space-x-3">
                {{-- Save Draft --}}
                <a href="{{ route('campaign.wizard.save-draft', $session_id) }}"
                   class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    {{ __('campaigns.save_draft') }}
                </a>

                {{-- Cancel --}}
                <a href="{{ route('campaign.wizard.cancel', $session_id) }}"
                   class="px-4 py-2 text-gray-600 hover:text-gray-900">
                    {{ __('common.cancel') }}
                </a>

                @if($current_step < count($all_steps))
                    {{-- Next Button --}}
                    <button type="submit" name="action" value="next"
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        {{ __('common.next') }}
                    </button>
                @else
                    {{-- Complete Button --}}
                    <button type="button" @click="completeCampaign"
                            class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500">
                        {{ __('campaigns.wizard.complete') }}
                    </button>
                @endif
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function campaignWizard() {
    return {
        completeCampaign() {
            if (confirm('{{ __('campaigns.wizard.confirm_complete') }}')) {
                window.location.href = '{{ route('campaign.wizard.complete', $session_id) }}';
            }
        }
    }
}
</script>
@endpush
@endsection
