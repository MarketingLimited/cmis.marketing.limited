@extends('super-admin.layouts.app')

@section('title', __('super_admin.database.table_details', ['table' => $table]))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('super-admin.system.schema-tables', $schema) }}" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-white">{{ $schema }}.{{ $table }}</h1>
            </div>
            <p class="text-slate-400">{{ __('super_admin.database.table_details_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <form action="{{ route('super-admin.system.vacuum-table', [$schema, $table]) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors"
                        onclick="return confirm('{{ __('super_admin.database.confirm_vacuum') }}')">
                    <i class="fas fa-broom me-2"></i>{{ __('super_admin.database.vacuum') }}
                </button>
            </form>
            <form action="{{ route('super-admin.system.analyze-table', [$schema, $table]) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg transition-colors"
                        onclick="return confirm('{{ __('super_admin.database.confirm_analyze') }}')">
                    <i class="fas fa-chart-bar me-2"></i>{{ __('super_admin.database.analyze') }}
                </button>
            </form>
            <form action="{{ route('super-admin.system.reindex-table', [$schema, $table]) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="px-4 py-2 bg-amber-600 hover:bg-amber-500 text-white rounded-lg transition-colors"
                        onclick="return confirm('{{ __('super_admin.database.confirm_reindex') }}')">
                    <i class="fas fa-sync me-2"></i>{{ __('super_admin.database.reindex') }}
                </button>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-slate-400 text-xs uppercase">{{ __('super_admin.database.total_size') }}</p>
            <p class="text-lg font-bold text-white mt-1">{{ $stats->total_size ?? '-' }}</p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-slate-400 text-xs uppercase">{{ __('super_admin.database.table_size') }}</p>
            <p class="text-lg font-bold text-white mt-1">{{ $stats->table_size ?? '-' }}</p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-slate-400 text-xs uppercase">{{ __('super_admin.database.indexes_size') }}</p>
            <p class="text-lg font-bold text-white mt-1">{{ $stats->indexes_size ?? '-' }}</p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-slate-400 text-xs uppercase">{{ __('super_admin.database.rows') }}</p>
            <p class="text-lg font-bold text-white mt-1">{{ number_format($stats->row_count ?? 0) }}</p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-slate-400 text-xs uppercase">{{ __('super_admin.database.dead_tuples') }}</p>
            <p class="text-lg font-bold {{ ($stats->dead_tuples ?? 0) > 1000 ? 'text-amber-400' : 'text-white' }} mt-1">
                {{ number_format($stats->dead_tuples ?? 0) }}
            </p>
        </div>
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-4">
            <p class="text-slate-400 text-xs uppercase">{{ __('super_admin.database.rls') }}</p>
            <p class="text-lg font-bold mt-1">
                @if($rls_enabled)
                    <span class="text-emerald-400"><i class="fas fa-shield-alt me-1"></i>{{ __('super_admin.database.enabled') }}</span>
                @else
                    <span class="text-red-400"><i class="fas fa-exclamation-triangle me-1"></i>{{ __('super_admin.database.disabled') }}</span>
                @endif
            </p>
        </div>
    </div>

    <!-- Activity Stats -->
    @if($stats)
    <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
        <h2 class="text-lg font-semibold text-white mb-4">{{ __('super_admin.database.activity_stats') }}</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.seq_scans') }}</p>
                <p class="text-xl font-semibold text-white">{{ number_format($stats->seq_scan ?? 0) }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.idx_scans') }}</p>
                <p class="text-xl font-semibold text-white">{{ number_format($stats->idx_scan ?? 0) }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.inserts') }}</p>
                <p class="text-xl font-semibold text-emerald-400">{{ number_format($stats->n_tup_ins ?? 0) }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.updates') }}</p>
                <p class="text-xl font-semibold text-blue-400">{{ number_format($stats->n_tup_upd ?? 0) }}</p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.deletes') }}</p>
                <p class="text-xl font-semibold text-red-400">{{ number_format($stats->n_tup_del ?? 0) }}</p>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mt-6 pt-6 border-t border-slate-700">
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.last_vacuum') }}</p>
                <p class="text-white">
                    {{ $stats->last_vacuum ? \Carbon\Carbon::parse($stats->last_vacuum)->diffForHumans() : '-' }}
                </p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.last_autovacuum') }}</p>
                <p class="text-white">
                    {{ $stats->last_autovacuum ? \Carbon\Carbon::parse($stats->last_autovacuum)->diffForHumans() : '-' }}
                </p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.last_analyze') }}</p>
                <p class="text-white">
                    {{ $stats->last_analyze ? \Carbon\Carbon::parse($stats->last_analyze)->diffForHumans() : '-' }}
                </p>
            </div>
            <div>
                <p class="text-slate-400 text-sm">{{ __('super_admin.database.last_autoanalyze') }}</p>
                <p class="text-white">
                    {{ $stats->last_autoanalyze ? \Carbon\Carbon::parse($stats->last_autoanalyze)->diffForHumans() : '-' }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <!-- Columns and Indexes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Columns -->
        <div class="bg-slate-800 rounded-xl border border-slate-700">
            <div class="p-6 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.columns') }} ({{ count($columns) }})</h2>
            </div>
            <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.column_name') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.data_type') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.nullable') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @foreach($columns as $column)
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-2 text-white font-mono text-sm">{{ $column->column_name }}</td>
                            <td class="px-4 py-2 text-slate-300 text-sm">
                                {{ $column->data_type }}
                                @if($column->character_maximum_length)
                                    ({{ $column->character_maximum_length }})
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if($column->is_nullable === 'YES')
                                    <span class="text-slate-400 text-xs">{{ __('super_admin.database.yes') }}</span>
                                @else
                                    <span class="text-amber-400 text-xs">{{ __('super_admin.database.no') }}</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Indexes -->
        <div class="bg-slate-800 rounded-xl border border-slate-700">
            <div class="p-6 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.indexes') }} ({{ count($indexes) }})</h2>
            </div>
            <div class="overflow-x-auto max-h-[400px] overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.index_name') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.size') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.scans') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.type') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($indexes as $index)
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-2 text-white font-mono text-sm">{{ $index->index_name }}</td>
                            <td class="px-4 py-2 text-slate-300 text-sm">{{ $index->index_size }}</td>
                            <td class="px-4 py-2 text-slate-300 text-sm">{{ number_format($index->scans) }}</td>
                            <td class="px-4 py-2">
                                @if($index->is_primary)
                                    <span class="px-2 py-1 text-xs bg-blue-500/20 text-blue-400 rounded">PK</span>
                                @elseif($index->is_unique)
                                    <span class="px-2 py-1 text-xs bg-purple-500/20 text-purple-400 rounded">UQ</span>
                                @else
                                    <span class="px-2 py-1 text-xs bg-slate-500/20 text-slate-400 rounded">IDX</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                {{ __('super_admin.database.no_indexes') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- RLS Policies -->
    @if(count($policies) > 0)
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.rls_policies') }} ({{ count($policies) }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.policy_name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.command') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.using_expr') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @foreach($policies as $policy)
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-6 py-4 text-white font-mono text-sm">{{ $policy->name }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded">{{ $policy->command }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-300 font-mono text-xs max-w-md truncate" title="{{ $policy->using_expr }}">
                            {{ $policy->using_expr }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
