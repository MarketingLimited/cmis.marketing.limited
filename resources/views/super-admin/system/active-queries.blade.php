@extends('super-admin.layouts.app')

@section('title', __('super_admin.database.active_queries'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('super-admin.system.database') }}" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-white">{{ __('super_admin.database.active_queries') }}</h1>
            </div>
            <p class="text-slate-400">{{ __('super_admin.database.active_queries_subtitle') }}</p>
        </div>
        <div>
            <a href="{{ route('super-admin.system.active-queries') }}"
               class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                <i class="fas fa-sync me-2"></i>{{ __('super_admin.database.refresh') }}
            </a>
        </div>
    </div>

    <!-- Active Queries List -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="p-6 border-b border-slate-700 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.running_queries') }}</h2>
            <span class="px-3 py-1 bg-blue-500/20 text-blue-400 rounded-lg text-sm">
                {{ count($queries) }} {{ __('super_admin.database.queries_active') }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">PID</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.user') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.client') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.state') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.duration') }}</th>
                        <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.query') }}</th>
                        <th class="px-4 py-3 text-end text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($queries as $query)
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-4 py-4 text-white font-mono">{{ $query->pid }}</td>
                        <td class="px-4 py-4 text-slate-300">{{ $query->user }}</td>
                        <td class="px-4 py-4 text-slate-400 text-sm">
                            {{ $query->client_addr ?? '-' }}
                            @if($query->application_name)
                                <br><span class="text-xs text-slate-500">{{ $query->application_name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @if($query->state === 'active')
                                <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded">{{ $query->state }}</span>
                            @elseif($query->state === 'idle in transaction')
                                <span class="px-2 py-1 text-xs bg-amber-500/20 text-amber-400 rounded">{{ $query->state }}</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-slate-500/20 text-slate-400 rounded">{{ $query->state }}</span>
                            @endif
                            @if($query->wait_event_type)
                                <br><span class="text-xs text-slate-500">{{ $query->wait_event_type }}: {{ $query->wait_event }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-4">
                            @php
                                $duration = $query->duration_seconds;
                                $class = 'text-slate-300';
                                if ($duration > 60) $class = 'text-red-400 font-semibold';
                                elseif ($duration > 10) $class = 'text-amber-400';
                            @endphp
                            <span class="{{ $class }}">{{ number_format($duration, 2) }}s</span>
                        </td>
                        <td class="px-4 py-4">
                            <div class="max-w-md">
                                <code class="text-xs text-slate-300 bg-slate-900/50 px-2 py-1 rounded block overflow-hidden text-ellipsis whitespace-nowrap" title="{{ $query->query }}">
                                    {{ Str::limit($query->query, 100) }}
                                </code>
                            </div>
                        </td>
                        <td class="px-4 py-4 text-end">
                            <div class="flex items-center justify-end gap-2">
                                <form action="{{ route('super-admin.system.cancel-query', $query->pid) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 bg-amber-600 hover:bg-amber-500 text-white text-xs rounded transition-colors"
                                            title="{{ __('super_admin.database.cancel_query') }}"
                                            onclick="return confirm('{{ __('super_admin.database.confirm_cancel_query') }}')">
                                        <i class="fas fa-stop"></i>
                                    </button>
                                </form>
                                <form action="{{ route('super-admin.system.terminate-connection', $query->pid) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 bg-red-600 hover:bg-red-500 text-white text-xs rounded transition-colors"
                                            title="{{ __('super_admin.database.terminate_connection') }}"
                                            onclick="return confirm('{{ __('super_admin.database.confirm_terminate') }}')">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mb-4">
                                    <i class="fas fa-check text-emerald-400 text-2xl"></i>
                                </div>
                                <p class="text-slate-300 font-medium">{{ __('super_admin.database.no_active_queries') }}</p>
                                <p class="text-slate-400 text-sm mt-1">{{ __('super_admin.database.no_active_queries_desc') }}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Box -->
    <div class="bg-slate-800/50 rounded-xl border border-slate-700 p-6">
        <h3 class="text-white font-semibold mb-3">{{ __('super_admin.database.query_management_info') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-amber-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-stop text-amber-400 text-xs"></i>
                </div>
                <div>
                    <p class="text-white font-medium">{{ __('super_admin.database.cancel_action') }}</p>
                    <p class="text-slate-400">{{ __('super_admin.database.cancel_action_desc') }}</p>
                </div>
            </div>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 bg-red-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-times text-red-400 text-xs"></i>
                </div>
                <div>
                    <p class="text-white font-medium">{{ __('super_admin.database.terminate_action') }}</p>
                    <p class="text-slate-400">{{ __('super_admin.database.terminate_action_desc') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
