@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.partners_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.partners_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.partners_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.partners.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fas fa-plus"></i> {{ __('super_admin.website.create_partner') }}
        </a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
        @forelse($partners ?? [] as $partner)
            <div class="bg-white dark:bg-slate-800 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-center group relative">
                @if($partner->is_featured)
                    <span class="absolute top-2 {{ app()->getLocale() == 'ar' ? 'left-2' : 'right-2' }} px-2 py-0.5 text-xs font-medium bg-yellow-100 text-yellow-700 rounded-full">
                        <i class="fas fa-star"></i>
                    </span>
                @endif
                @if($partner->logo_url)
                    <img src="{{ $partner->logo_url }}" alt="{{ $partner->name }}" class="w-full h-16 object-contain mb-3">
                @else
                    <div class="w-full h-16 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center mb-3">
                        <i class="fas fa-building text-slate-400 text-2xl"></i>
                    </div>
                @endif
                <h3 class="font-medium text-slate-900 dark:text-white text-sm">{{ $partner->name }}</h3>
                @if($partner->website_url)
                    <a href="{{ $partner->website_url }}" target="_blank" class="text-xs text-blue-600 hover:underline">{{ __('super_admin.website.visit_website') }}</a>
                @endif
                <div class="flex items-center justify-center gap-1 mt-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                    <span class="px-2 py-0.5 text-xs rounded-full {{ $partner->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $partner->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                    </span>
                    <a href="{{ route('super-admin.website.partners.edit', $partner->id) }}" class="p-1 text-blue-600 hover:bg-blue-100 rounded"><i class="fas fa-edit text-xs"></i></a>
                    <form action="{{ route('super-admin.website.partners.destroy', $partner->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-1 text-red-600 hover:bg-red-100 rounded"><i class="fas fa-trash text-xs"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-xl p-8 text-center border border-slate-200 dark:border-slate-700">
                <i class="fas fa-handshake text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_partners') }}</p>
                <a href="{{ route('super-admin.website.partners.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_partner') }}
                </a>
            </div>
        @endforelse
    </div>

    @if(($partners ?? collect())->hasPages())
        <div class="mt-6">{{ $partners->links() }}</div>
    @endif
</div>
@endsection
