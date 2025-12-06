@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.testimonials_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.testimonials_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.testimonials_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.testimonials.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fas fa-plus"></i> {{ __('super_admin.website.create_testimonial') }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($testimonials ?? [] as $testimonial)
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700">
                <div class="flex items-start gap-4 mb-4">
                    @if($testimonial->author_image_url)
                        <img src="{{ $testimonial->author_image_url }}" alt="{{ $testimonial->author_name }}" class="w-12 h-12 rounded-full object-cover">
                    @else
                        <div class="w-12 h-12 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-slate-400"></i>
                        </div>
                    @endif
                    <div class="flex-1">
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ $testimonial->author_name }}</h3>
                        <p class="text-sm text-slate-600 dark:text-slate-400">{{ $testimonial->author_role }}</p>
                        <p class="text-sm text-slate-500">{{ $testimonial->company_name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-1 mb-3">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star {{ $i <= ($testimonial->rating ?? 5) ? 'text-yellow-400' : 'text-slate-300' }} text-sm"></i>
                    @endfor
                </div>
                <p class="text-slate-600 dark:text-slate-400 text-sm line-clamp-3 mb-4">"{{ $testimonial->quote }}"</p>
                <div class="flex items-center justify-between pt-4 border-t border-slate-200 dark:border-slate-700">
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $testimonial->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $testimonial->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                    </span>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('super-admin.website.testimonials.edit', $testimonial->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('super-admin.website.testimonials.destroy', $testimonial->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                            @csrf @method('DELETE')
                            <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-xl p-8 text-center border border-slate-200 dark:border-slate-700">
                <i class="fas fa-quote-right text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_testimonials') }}</p>
                <a href="{{ route('super-admin.website.testimonials.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_testimonial') }}
                </a>
            </div>
        @endforelse
    </div>

    @if(($testimonials ?? collect())->hasPages())
        <div class="mt-6">{{ $testimonials->links() }}</div>
    @endif
</div>
@endsection
