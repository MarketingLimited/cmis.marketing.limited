@extends('layouts.admin')

@section('title', __('Approval Workflows') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Approval Workflows') }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Approval Workflows</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                Configure multi-step approval processes for content before publishing.
            </p>
        </div>
        <a href="{{ route('orgs.settings.approval-workflows.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
            <i class="fas fa-plus mr-2"></i>Create Workflow
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex"><i class="fas fa-check-circle text-green-400 mr-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($workflows->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($workflows as $workflow)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-5">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <i class="fas fa-tasks text-indigo-600"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-base font-semibold text-gray-900">{{ $workflow->name }}</h3>
                                <p class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $workflow->trigger_condition)) }}</p>
                            </div>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full {{ $workflow->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                            {{ $workflow->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    @if($workflow->description)
                        <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $workflow->description }}</p>
                    @endif

                    {{-- Steps Preview --}}
                    @if($workflow->approval_steps && count($workflow->approval_steps) > 0)
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-xs text-gray-500">{{ count($workflow->approval_steps) }} approval step(s)</span>
                            @if($workflow->auto_approve_after_hours)
                                <span class="text-xs text-gray-400">&bull; Auto-approve after {{ $workflow->auto_approve_after_hours }}h</span>
                            @endif
                        </div>
                    @endif

                    @if($workflow->profileGroup)
                        <div class="text-xs text-gray-500 mb-4">
                            <i class="fas fa-layer-group mr-1"></i>{{ $workflow->profileGroup->name }}
                        </div>
                    @endif

                    <div class="flex items-center gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route('orgs.settings.approval-workflows.show', [$currentOrg, $workflow->workflow_id]) }}"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-xs font-medium text-gray-700 bg-white hover:bg-gray-50">
                            View
                        </a>
                        <a href="{{ route('orgs.settings.approval-workflows.edit', [$currentOrg, $workflow->workflow_id]) }}"
                           class="flex-1 inline-flex items-center justify-center px-3 py-2 border border-transparent rounded-md text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                            Edit
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-tasks text-indigo-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Approval Workflows Yet</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                Set up approval workflows to require team review before content is published.
            </p>
            <a href="{{ route('orgs.settings.approval-workflows.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i>Create First Workflow
            </a>
        </div>
    @endif
</div>
@endsection
