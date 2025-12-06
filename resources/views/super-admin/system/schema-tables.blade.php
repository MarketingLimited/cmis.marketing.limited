@extends('super-admin.layouts.app')

@section('title', __('super_admin.database.schema_tables', ['schema' => $schema]))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('super-admin.system.database') }}" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-white">{{ __('super_admin.database.schema') }}: {{ $schema }}</h1>
            </div>
            <p class="text-slate-400">{{ __('super_admin.database.schema_subtitle', ['count' => count($tables)]) }}</p>
        </div>
    </div>

    <!-- Tables List -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.table_name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.rows') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.size') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.dead_tuples') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.last_vacuum') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.last_analyze') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($tables as $table)
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-6 py-4">
                            <a href="{{ route('super-admin.system.table-details', [$schema, $table->table_name]) }}"
                               class="text-blue-400 hover:text-blue-300 font-medium">
                                {{ $table->table_name }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-slate-300">{{ number_format($table->row_count) }}</td>
                        <td class="px-6 py-4 text-slate-300">{{ $table->total_size }}</td>
                        <td class="px-6 py-4">
                            @if($table->dead_tuples > 1000)
                                <span class="text-amber-400">{{ number_format($table->dead_tuples) }}</span>
                            @else
                                <span class="text-slate-300">{{ number_format($table->dead_tuples) }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-400 text-sm">
                            @if($table->last_vacuum || $table->last_autovacuum)
                                {{ $table->last_vacuum ? \Carbon\Carbon::parse($table->last_vacuum)->diffForHumans() : \Carbon\Carbon::parse($table->last_autovacuum)->diffForHumans() }}
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-slate-400 text-sm">
                            @if($table->last_analyze || $table->last_autoanalyze)
                                {{ $table->last_analyze ? \Carbon\Carbon::parse($table->last_analyze)->diffForHumans() : \Carbon\Carbon::parse($table->last_autoanalyze)->diffForHumans() }}
                            @else
                                <span class="text-slate-500">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-end">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('super-admin.system.table-details', [$schema, $table->table_name]) }}"
                                   class="px-2 py-1 bg-slate-700 hover:bg-slate-600 text-white text-xs rounded transition-colors"
                                   title="{{ __('super_admin.database.view_details') }}">
                                    <i class="fas fa-info-circle"></i>
                                </a>
                                <form action="{{ route('super-admin.system.vacuum-table', [$schema, $table->table_name]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 bg-blue-600 hover:bg-blue-500 text-white text-xs rounded transition-colors"
                                            title="{{ __('super_admin.database.vacuum') }}"
                                            onclick="return confirm('{{ __('super_admin.database.confirm_vacuum') }}')">
                                        <i class="fas fa-broom"></i>
                                    </button>
                                </form>
                                <form action="{{ route('super-admin.system.analyze-table', [$schema, $table->table_name]) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 bg-purple-600 hover:bg-purple-500 text-white text-xs rounded transition-colors"
                                            title="{{ __('super_admin.database.analyze') }}"
                                            onclick="return confirm('{{ __('super_admin.database.confirm_analyze') }}')">
                                        <i class="fas fa-chart-bar"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                            {{ __('super_admin.database.no_tables_in_schema') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
