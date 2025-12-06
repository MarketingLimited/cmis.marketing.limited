@extends('super-admin.layouts.app')

@section('title', __('super_admin.database.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ __('super_admin.database.title') }}</h1>
            <p class="text-slate-400 mt-1">{{ __('super_admin.database.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.system.migrations') }}"
               class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                <i class="fas fa-code-branch me-2"></i>{{ __('super_admin.database.migrations') }}
            </a>
            <a href="{{ route('super-admin.system.active-queries') }}"
               class="px-4 py-2 bg-slate-700 hover:bg-slate-600 text-white rounded-lg transition-colors">
                <i class="fas fa-terminal me-2"></i>{{ __('super_admin.database.active_queries') }}
            </a>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Database Size -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.database.db_size') }}</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ $stats['database_size']['size'] ?? '-' }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-database text-blue-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Connections -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.database.connections') }}</p>
                    <p class="text-2xl font-bold text-white mt-1">
                        {{ $stats['connections']['total'] ?? 0 }}
                        <span class="text-sm text-slate-400 font-normal">
                            ({{ $stats['connections']['active'] ?? 0 }} {{ __('super_admin.database.active') }})
                        </span>
                    </p>
                </div>
                <div class="w-12 h-12 bg-emerald-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-plug text-emerald-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Cache Hit Ratio -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.database.cache_hit_ratio') }}</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ $stats['cache_stats']['cache_hit_ratio'] ?? 0 }}%</p>
                </div>
                <div class="w-12 h-12 bg-amber-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-bolt text-amber-400 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Index Hit Ratio -->
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-slate-400 text-sm">{{ __('super_admin.database.index_hit_ratio') }}</p>
                    <p class="text-2xl font-bold text-white mt-1">{{ $stats['cache_stats']['index_hit_ratio'] ?? 0 }}%</p>
                </div>
                <div class="w-12 h-12 bg-purple-500/20 rounded-xl flex items-center justify-center">
                    <i class="fas fa-list text-purple-400 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Schemas Overview -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.schemas') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.schema_name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.table_count') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.total_size') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($stats['schemas'] as $schema)
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-folder text-blue-400"></i>
                                </div>
                                <span class="text-white font-medium">{{ $schema->schema_name }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-slate-300">{{ number_format($schema->table_count) }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-slate-300">{{ $schema->total_size }}</span>
                        </td>
                        <td class="px-6 py-4 text-end">
                            <a href="{{ route('super-admin.system.schema-tables', $schema->schema_name) }}"
                               class="px-3 py-1 bg-slate-700 hover:bg-slate-600 text-white text-sm rounded-lg transition-colors">
                                <i class="fas fa-eye me-1"></i>{{ __('super_admin.database.view_tables') }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                            {{ __('super_admin.database.no_schemas') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Two Column Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Largest Tables -->
        <div class="bg-slate-800 rounded-xl border border-slate-700">
            <div class="p-6 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.largest_tables') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.table') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.rows') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.size') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse(array_slice($stats['largest_tables'], 0, 10) as $table)
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <a href="{{ route('super-admin.system.table-details', [$table->schema, $table->table_name]) }}"
                                   class="text-blue-400 hover:text-blue-300">
                                    {{ $table->schema }}.{{ $table->table_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format($table->row_count) }}</td>
                            <td class="px-4 py-3 text-slate-300">{{ $table->total_size }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-slate-400">
                                {{ __('super_admin.database.no_tables') }}
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tables Needing Vacuum -->
        <div class="bg-slate-800 rounded-xl border border-slate-700">
            <div class="p-6 border-b border-slate-700">
                <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.tables_need_vacuum') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.table') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.dead_tuples') }}</th>
                            <th class="px-4 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.bloat') }}</th>
                            <th class="px-4 py-3 text-end text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700">
                        @forelse($stats['table_bloat'] as $table)
                        <tr class="hover:bg-slate-700/30">
                            <td class="px-4 py-3">
                                <span class="text-white">{{ $table->schema }}.{{ $table->table_name }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-300">{{ number_format($table->dead_tuples) }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs rounded-lg {{ $table->bloat_ratio > 20 ? 'bg-red-500/20 text-red-400' : 'bg-amber-500/20 text-amber-400' }}">
                                    {{ $table->bloat_ratio }}%
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <form action="{{ route('super-admin.system.vacuum-table', [$table->schema, $table->table_name]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs rounded transition-colors"
                                            onclick="return confirm('{{ __('super_admin.database.confirm_vacuum') }}')">
                                        <i class="fas fa-broom me-1"></i>{{ __('super_admin.database.vacuum') }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-slate-400">
                                <i class="fas fa-check-circle text-emerald-400 text-2xl mb-2"></i>
                                <p>{{ __('super_admin.database.no_bloat') }}</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Index Usage (Unused Indexes) -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.index_usage') }}</h2>
                <span class="text-xs text-slate-400">{{ __('super_admin.database.sorted_by_usage') }}</span>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.index_name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.table') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.size') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.scans') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($stats['index_usage'] as $index)
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-6 py-4">
                            <span class="text-white font-mono text-sm">{{ $index->index_name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-slate-300">{{ $index->schema }}.{{ $index->table_name }}</span>
                        </td>
                        <td class="px-6 py-4 text-slate-300">{{ $index->index_size }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ number_format($index->scans) }}</td>
                        <td class="px-6 py-4">
                            @if($index->status === 'Unused')
                                <span class="px-2 py-1 text-xs bg-red-500/20 text-red-400 rounded-lg">{{ __('super_admin.database.unused') }}</span>
                            @else
                                <span class="px-2 py-1 text-xs bg-emerald-500/20 text-emerald-400 rounded-lg">{{ __('super_admin.database.used') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                            {{ __('super_admin.database.no_indexes') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
