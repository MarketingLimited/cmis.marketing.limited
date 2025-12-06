@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.hero_slides_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.hero_slides_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.hero_slides_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.hero.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
            <i class="fas fa-plus"></i>
            {{ __('super_admin.website.create_hero_slide') }}
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if(($slides ?? collect())->count() > 0)
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($slides as $slide)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-center gap-4">
                            @if($slide->background_image_url)
                                <img src="{{ $slide->background_image_url }}" alt="{{ $slide->headline }}" class="w-32 h-20 rounded-lg object-cover">
                            @else
                                <div class="w-32 h-20 bg-gradient-to-r from-red-600 to-purple-600 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-white text-2xl"></i>
                                </div>
                            @endif
                            <div class="flex-1">
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $slide->headline }}</h3>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 line-clamp-1">{{ $slide->subheadline }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                    <span><i class="fas fa-sort me-1"></i>{{ __('super_admin.website.order') }}: {{ $slide->sort_order }}</span>
                                    <span class="px-2 py-0.5 rounded-full {{ $slide->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $slide->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('super-admin.website.hero.edit', $slide->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('super-admin.website.hero.destroy', $slide->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="p-8 text-center">
                <i class="fas fa-images text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_hero_slides') }}</p>
                <a href="{{ route('super-admin.website.hero.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_slide') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
