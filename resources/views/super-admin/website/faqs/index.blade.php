@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.faqs_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.faqs_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.faqs_subtitle') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('super-admin.website.faq-categories.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                <i class="fas fa-folder"></i> {{ __('super_admin.website.categories') }}
            </a>
            <a href="{{ route('super-admin.website.faqs.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                <i class="fas fa-plus"></i> {{ __('super_admin.website.create_faq') }}
            </a>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if(($faqs ?? collect())->count() > 0)
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($faqs as $faq)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors" x-data="{ open: false }">
                        <div class="flex items-start gap-4">
                            <div class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-question text-orange-600 text-sm"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <button @click="open = !open" class="text-start font-semibold text-slate-900 dark:text-white hover:text-red-600">
                                        {{ $faq->question }}
                                    </button>
                                    @if($faq->category)
                                        <span class="px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700 rounded-full">{{ $faq->category->name }}</span>
                                    @endif
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $faq->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                                        {{ $faq->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                                    </span>
                                </div>
                                <p x-show="open" x-collapse class="text-sm text-slate-600 dark:text-slate-400 mt-2">{{ Str::limit(strip_tags($faq->answer), 200) }}</p>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs text-slate-500"><i class="fas fa-sort me-1"></i>{{ $faq->sort_order }}</span>
                                <a href="{{ route('super-admin.website.faqs.edit', $faq->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('super-admin.website.faqs.destroy', $faq->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($faqs->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">{{ $faqs->links() }}</div>
            @endif
        @else
            <div class="p-8 text-center">
                <i class="fas fa-question-circle text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_faqs') }}</p>
                <a href="{{ route('super-admin.website.faqs.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_faq') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
