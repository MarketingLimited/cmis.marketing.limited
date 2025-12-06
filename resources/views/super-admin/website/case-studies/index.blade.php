@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.case_studies_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.case_studies_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.case_studies_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.case-studies.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fas fa-plus"></i> {{ __('super_admin.website.create_case_study') }}
        </a>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        @if(($caseStudies ?? collect())->count() > 0)
            <div class="divide-y divide-slate-200 dark:divide-slate-700">
                @foreach($caseStudies as $caseStudy)
                    <div class="p-4 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                        <div class="flex items-start gap-4">
                            @if($caseStudy->featured_image_url)
                                <img src="{{ $caseStudy->featured_image_url }}" alt="{{ $caseStudy->title }}" class="w-24 h-16 rounded-lg object-cover">
                            @else
                                <div class="w-24 h-16 bg-cyan-100 dark:bg-cyan-900/30 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-briefcase text-cyan-600 text-xl"></i>
                                </div>
                            @endif
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('super-admin.website.case-studies.edit', $caseStudy->id) }}" class="font-semibold text-slate-900 dark:text-white hover:text-red-600">{{ $caseStudy->title }}</a>
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $caseStudy->is_published ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                        {{ $caseStudy->is_published ? __('super_admin.website.published') : __('super_admin.website.draft') }}
                                    </span>
                                </div>
                                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ $caseStudy->client_name }} &bull; {{ $caseStudy->industry }}</p>
                                <div class="flex items-center gap-4 mt-2 text-xs text-slate-500">
                                    @if($caseStudy->results_metrics)
                                        @php $metrics = is_array($caseStudy->results_metrics) ? $caseStudy->results_metrics : json_decode($caseStudy->results_metrics, true); @endphp
                                        @foreach(array_slice($metrics ?? [], 0, 3) as $metric)
                                            <span class="bg-slate-100 dark:bg-slate-700 px-2 py-1 rounded">{{ $metric['label'] ?? '' }}: {{ $metric['value'] ?? '' }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="/case-studies/{{ $caseStudy->slug }}" target="_blank" class="p-2 text-slate-600 hover:bg-slate-100 rounded-lg"><i class="fas fa-external-link-alt"></i></a>
                                <a href="{{ route('super-admin.website.case-studies.edit', $caseStudy->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('super-admin.website.case-studies.destroy', $caseStudy->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if($caseStudies->hasPages())
                <div class="p-4 border-t border-slate-200 dark:border-slate-700">{{ $caseStudies->links() }}</div>
            @endif
        @else
            <div class="p-8 text-center">
                <i class="fas fa-briefcase text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_case_studies') }}</p>
                <a href="{{ route('super-admin.website.case-studies.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_case_study') }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
