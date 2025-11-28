@extends('layouts.admin')

@section('title', $workflow->name . ' - ' . __('Approval Workflows'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.approval-workflows.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Approval Workflows') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $workflow->name }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                <i class="fas fa-tasks text-indigo-600 text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ $workflow->name }}</h1>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $workflow->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                        {{ $workflow->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="text-xs text-gray-500">{{ ucwords(str_replace('_', ' ', $workflow->trigger_condition)) }}</span>
                </div>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('orgs.settings.approval-workflows.toggle', [$currentOrg, $workflow->workflow_id]) }}" method="POST">
                @csrf
                <button type="submit" class="px-4 py-2 border {{ $workflow->is_active ? 'border-yellow-300 text-yellow-600 hover:bg-yellow-50' : 'border-green-300 text-green-600 hover:bg-green-50' }} rounded-lg transition text-sm font-medium">
                    <i class="fas {{ $workflow->is_active ? 'fa-pause' : 'fa-play' }} me-1"></i>
                    {{ $workflow->is_active ? __('settings.pause') : __('settings.activate') }}
                </button>
            </form>
            <a href="{{ route('orgs.settings.approval-workflows.edit', [$currentOrg, $workflow->workflow_id]) }}"
               class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm font-medium">
                <i class="fas fa-edit me-1"></i>{{ __('common.edit') }}
            </a>
        </div>
    </div>

    @if($workflow->description)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <p class="text-gray-600">{{ $workflow->description }}</p>
        </div>
    @endif

    {{-- Visual Workflow Flow --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-project-diagram text-indigo-500 me-2"></i>{{ __('settings.approval_flow') }}
        </h3>

        <div class="flex items-center justify-center gap-4 py-6 overflow-x-auto">
            {{-- Start Node --}}
            <div class="flex-shrink-0 text-center">
                <div class="w-16 h-16 mx-auto bg-blue-100 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                </div>
                <span class="text-xs text-gray-600">{{ __('settings.content') }}<br>{{ __('settings.created') }}</span>
            </div>

            @if($workflow->approval_steps && count($workflow->approval_steps) > 0)
                @foreach($workflow->approval_steps as $index => $step)
                    {{-- Arrow --}}
                    <div class="flex-shrink-0 flex items-center">
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <i class="fas fa-chevron-right text-gray-300"></i>
                    </div>

                    {{-- Step Node --}}
                    <div class="flex-shrink-0 text-center">
                        <div class="w-16 h-16 mx-auto bg-indigo-100 rounded-full flex items-center justify-center mb-2 relative">
                            <span class="text-indigo-600 font-bold text-lg">{{ $index + 1 }}</span>
                            <div class="absolute -top-1 -right-1 w-5 h-5 bg-indigo-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>
                        </div>
                        <span class="text-xs text-gray-600">
                            {{ ucfirst($step['role'] ?? 'Any') }}<br>
                            <span class="text-gray-400">({{ $step['required_approvals'] ?? 1 }} req.)</span>
                        </span>
                    </div>
                @endforeach
            @endif

            {{-- Arrow --}}
            <div class="flex-shrink-0 flex items-center">
                <div class="w-12 h-0.5 bg-gray-300"></div>
                <i class="fas fa-chevron-right text-gray-300"></i>
            </div>

            {{-- End Node --}}
            <div class="flex-shrink-0 text-center">
                <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-2">
                    <i class="fas fa-check text-green-600 text-xl"></i>
                </div>
                <span class="text-xs text-gray-600">{{ __('settings.published') }}</span>
            </div>
        </div>
    </div>

    {{-- Approval Steps Detail --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-list-ol text-indigo-500 me-2"></i>{{ __('settings.approval_steps') }}
        </h3>

        @if($workflow->approval_steps && count($workflow->approval_steps) > 0)
            <div class="space-y-4">
                @foreach($workflow->approval_steps as $index => $step)
                    <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                        <div class="w-8 h-8 bg-indigo-600 text-white rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0">
                            {{ $index + 1 }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h4 class="text-sm font-medium text-gray-900">{{ __('settings.step') }} {{ $index + 1 }}</h4>
                                <span class="px-2 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded-full">
                                    {{ $step['required_approvals'] ?? 1 }} {{ __('settings.approval_required', ['count' => $step['required_approvals'] ?? 1]) }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">
                                <span class="font-medium">{{ __('settings.role') }}:</span> {{ ucfirst($step['role'] ?? __('settings.any_team_member')) }}
                            </p>
                            @if(!empty($step['users']))
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">{{ __('settings.specific_users') }}:</span> {{ count($step['users']) }} {{ __('settings.assigned') }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500">{{ __('settings.no_approval_steps_configured') }}</p>
        @endif
    </div>

    {{-- Workflow Settings --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            <i class="fas fa-cog text-gray-500 me-2"></i>{{ __('settings.settings') }}
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">{{ __('settings.auto_approve_after') }}</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ $workflow->auto_approve_after_hours ? $workflow->auto_approve_after_hours . ' ' . __('settings.hours') : __('settings.disabled') }}
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">{{ __('settings.reminder_after') }}</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ $workflow->settings['reminder_hours'] ?? 24 }} {{ __('settings.hours') }}
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">{{ __('settings.allow_skip') }}</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ ($workflow->settings['allow_skip'] ?? false) ? __('settings.yes') : __('settings.no') }}
                </p>
            </div>

            <div class="p-4 bg-gray-50 rounded-lg">
                <p class="text-xs text-gray-500 mb-1">{{ __('settings.require_rejection_reason') }}</p>
                <p class="text-sm font-medium text-gray-900">
                    {{ ($workflow->settings['require_rejection_reason'] ?? true) ? __('settings.yes') : __('settings.no') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Associated Profile Group --}}
    @if($workflow->profileGroup)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">
                <i class="fas fa-layer-group text-indigo-500 me-2"></i>{{ __('settings.associated_profile_group') }}
            </h3>
            <a href="{{ route('orgs.settings.profile-groups.show', [$currentOrg, $workflow->profileGroup->group_id]) }}"
               class="inline-flex items-center gap-3 p-4 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center">
                    <i class="fas fa-layer-group text-indigo-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $workflow->profileGroup->name }}</p>
                    <p class="text-xs text-gray-500">{{ $workflow->profileGroup->profiles_count ?? 0 }} {{ __('settings.profiles') }}</p>
                </div>
                <i class="fas fa-chevron-right text-gray-400 ms-auto"></i>
            </a>
        </div>
    @endif

    {{-- Pending Approvals --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-semibold text-gray-900">
                <i class="fas fa-clock text-yellow-500 me-2"></i>{{ __('settings.pending_approvals') }}
            </h3>
            <span class="px-2 py-1 text-xs bg-yellow-100 text-yellow-700 rounded-full">
                {{ $pendingCount ?? 0 }} {{ __('common.pending') }}
            </span>
        </div>

        @if(($pendingApprovals ?? collect())->count() > 0)
            <div class="space-y-3">
                @foreach($pendingApprovals as $approval)
                    <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-file-alt text-yellow-600"></i>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ Str::limit($approval->content ?? __('settings.untitled'), 50) }}</p>
                                <p class="text-xs text-gray-500">{{ __('settings.step') }} {{ $approval->current_step ?? 1 }} &bull; {{ $approval->created_at->diffForHumans() }}</p>
                            </div>
                        </div>
                        <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700">{{ __('settings.review') }}</a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-500 text-center py-4">{{ __('settings.no_pending_approvals') }}</p>
        @endif
    </div>

    {{-- Metadata --}}
    <div class="bg-gray-50 rounded-lg p-4 text-xs text-gray-500">
        <div class="flex flex-wrap gap-6">
            <div>
                <span class="text-gray-400">Created:</span>
                <span class="ml-1">{{ $workflow->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Last Updated:</span>
                <span class="ml-1">{{ $workflow->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-400">Workflow ID:</span>
                <span class="ml-1 font-mono">{{ $workflow->workflow_id }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
