@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.features_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.features_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.features_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.website.feature-categories.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                <i class="fas fa-folder"></i> {{ __('super_admin.website.categories') }}
            </a>
            <a href="{{ route('super-admin.website.features.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-plus"></i> {{ __('super_admin.website.create_feature') }}
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if(($features ?? collect())->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 dark:bg-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 uppercase">{{ __('super_admin.website.feature') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 uppercase">{{ __('super_admin.website.category') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 uppercase">{{ __('super_admin.website.icon') }}</th>
                            <th class="px-6 py-3 text-start text-xs font-semibold text-slate-500 uppercase">{{ __('super_admin.website.status') }}</th>
                            <th class="px-6 py-3 text-end text-xs font-semibold text-slate-500 uppercase">{{ __('super_admin.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700">
                        @foreach($features as $feature)
                            <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-slate-900 dark:text-white">{{ $feature->title }}</div>
                                    <div class="text-sm text-slate-500 line-clamp-1">{{ Str::limit($feature->description, 60) }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 dark:text-slate-400">{{ $feature->category->name ?? '-' }}</td>
                                <td class="px-6 py-4"><i class="{{ $feature->icon ?? 'fas fa-star' }} text-purple-600"></i></td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $feature->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $feature->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('super-admin.website.features.edit', $feature->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                                        <form action="{{ route('super-admin.website.features.destroy', $feature->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($features->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">{{ $features->links() }}</div>
            @endif
        @else
            <div class="p-8 text-center">
                <i class="fas fa-star text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_features') }}</p>
                <a href="{{ route('super-admin.website.features.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_feature') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
