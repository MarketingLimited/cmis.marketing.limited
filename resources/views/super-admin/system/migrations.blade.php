@extends('super-admin.layouts.app')

@section('title', __('super_admin.database.migrations'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-3 mb-2">
                <a href="{{ route('super-admin.system.database') }}" class="text-slate-400 hover:text-white transition-colors">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="text-2xl font-bold text-white">{{ __('super_admin.database.migrations') }}</h1>
            </div>
            <p class="text-slate-400">{{ __('super_admin.database.migrations_subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-lg text-sm">
                {{ __('super_admin.database.run_migrations', ['count' => count($migrations)]) }}
            </span>
            @if(count($pendingMigrations) > 0)
                <span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-lg text-sm">
                    {{ __('super_admin.database.pending_migrations', ['count' => count($pendingMigrations)]) }}
                </span>
            @endif
        </div>
    </div>

    <!-- Pending Migrations Warning -->
    @if(count($pendingMigrations) > 0)
    <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-10 h-10 bg-amber-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-amber-400"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-amber-400 mb-2">{{ __('super_admin.database.pending_migrations_warning') }}</h3>
                <p class="text-slate-300 mb-4">{{ __('super_admin.database.pending_migrations_desc') }}</p>
                <ul class="space-y-1">
                    @foreach($pendingMigrations as $pending)
                    <li class="text-slate-400 font-mono text-sm">
                        <i class="fas fa-circle text-amber-400 text-xs me-2"></i>{{ $pending }}
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    @endif

    <!-- Migrations List -->
    <div class="bg-slate-800 rounded-xl border border-slate-700">
        <div class="p-6 border-b border-slate-700">
            <h2 class="text-lg font-semibold text-white">{{ __('super_admin.database.run_migrations_list') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-900/50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">#</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.migration_name') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-slate-400 uppercase">{{ __('super_admin.database.batch') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($migrations as $migration)
                    <tr class="hover:bg-slate-700/30">
                        <td class="px-6 py-4 text-slate-400">{{ $migration['id'] }}</td>
                        <td class="px-6 py-4">
                            <span class="text-white font-mono text-sm">{{ $migration['migration'] }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs bg-slate-700 text-slate-300 rounded">
                                {{ __('super_admin.database.batch_number', ['number' => $migration['batch']]) }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-6 py-8 text-center text-slate-400">
                            {{ __('super_admin.database.no_migrations') }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
