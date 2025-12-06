@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.navigation_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.navigation_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.navigation_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.navigation.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fas fa-plus"></i> {{ __('super_admin.website.create_menu') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @forelse($menus ?? [] as $menu)
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ $menu->name }}</h3>
                        <p class="text-sm text-slate-500">{{ __('super_admin.website.location') }}: {{ ucfirst($menu->location) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $menu->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                            {{ $menu->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                        </span>
                        <a href="{{ route('super-admin.website.navigation.edit', $menu->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('super-admin.website.navigation.destroy', $menu->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
                <div class="p-4">
                    @if($menu->items && $menu->items->count() > 0)
                        <ul class="space-y-2">
                            @foreach($menu->items->where('parent_id', null)->sortBy('sort_order') as $item)
                                <li class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg">
                                    <i class="fas fa-grip-vertical text-slate-400 cursor-move"></i>
                                    <span class="flex-1 text-slate-900 dark:text-white">{{ $item->label }}</span>
                                    <code class="text-xs text-slate-500 bg-slate-200 dark:bg-slate-600 px-2 py-1 rounded">{{ $item->url }}</code>
                                </li>
                                @foreach($menu->items->where('parent_id', $item->id)->sortBy('sort_order') as $child)
                                    <li class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-700/50 rounded-lg ms-6">
                                        <i class="fas fa-grip-vertical text-slate-400 cursor-move"></i>
                                        <i class="fas fa-level-up-alt fa-rotate-90 text-slate-400 text-xs"></i>
                                        <span class="flex-1 text-slate-900 dark:text-white">{{ $child->label }}</span>
                                        <code class="text-xs text-slate-500 bg-slate-200 dark:bg-slate-600 px-2 py-1 rounded">{{ $child->url }}</code>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-slate-500 text-center py-4">{{ __('super_admin.website.no_menu_items') }}</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-xl p-8 text-center border border-slate-200 dark:border-slate-700">
                <i class="fas fa-bars text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_menus') }}</p>
                <a href="{{ route('super-admin.website.navigation.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_menu') }}
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection
