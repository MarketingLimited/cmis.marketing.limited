@extends('marketing.layouts.app')

@section('title', __('marketing.cookies.title'))
@section('meta_description', __('marketing.cookies.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">{{ __('marketing.cookies.headline') }}</h1>
        <p class="text-slate-400">{{ __('marketing.cookies.last_updated') }}: {{ $page->updated_at?->format('F d, Y') ?? now()->format('F d, Y') }}</p>
    </div>
</section>

<!-- Content -->
<section class="py-16 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(isset($page) && $page->content)
            <div class="prose prose-lg dark:prose-invert max-w-none prose-headings:font-bold prose-headings:text-slate-900 dark:prose-headings:text-white prose-a:text-red-600">
                {!! $page->content !!}
            </div>
        @else
            <div class="prose prose-lg dark:prose-invert max-w-none">
                <h2>{{ __('marketing.cookies.section_1_title') }}</h2>
                <p>{{ __('marketing.cookies.section_1_content') }}</p>

                <h2>{{ __('marketing.cookies.section_2_title') }}</h2>
                <p>{{ __('marketing.cookies.section_2_content') }}</p>

                <h3>{{ __('marketing.cookies.essential_title') }}</h3>
                <p>{{ __('marketing.cookies.essential_content') }}</p>

                <h3>{{ __('marketing.cookies.analytics_title') }}</h3>
                <p>{{ __('marketing.cookies.analytics_content') }}</p>

                <h3>{{ __('marketing.cookies.marketing_title') }}</h3>
                <p>{{ __('marketing.cookies.marketing_content') }}</p>

                <h2>{{ __('marketing.cookies.section_3_title') }}</h2>
                <p>{{ __('marketing.cookies.section_3_content') }}</p>

                <h2>{{ __('marketing.cookies.section_4_title') }}</h2>
                <p>{{ __('marketing.cookies.section_4_content') }}</p>
            </div>
        @endif
    </div>
</section>

<!-- Cookie Table -->
<section class="py-12 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-6 text-center">{{ __('marketing.cookies.table_title') }}</h2>
        <div class="bg-white dark:bg-slate-700 rounded-xl overflow-hidden shadow">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 dark:bg-slate-600">
                        <tr>
                            <th class="px-6 py-3 text-start text-slate-700 dark:text-slate-300 font-semibold">{{ __('marketing.cookies.cookie_name') }}</th>
                            <th class="px-6 py-3 text-start text-slate-700 dark:text-slate-300 font-semibold">{{ __('marketing.cookies.cookie_type') }}</th>
                            <th class="px-6 py-3 text-start text-slate-700 dark:text-slate-300 font-semibold">{{ __('marketing.cookies.cookie_duration') }}</th>
                            <th class="px-6 py-3 text-start text-slate-700 dark:text-slate-300 font-semibold">{{ __('marketing.cookies.cookie_purpose') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-600">
                        @foreach([
                            ['name' => 'session_id', 'type' => __('marketing.cookies.type_essential'), 'duration' => __('marketing.cookies.session'), 'purpose' => __('marketing.cookies.purpose_session')],
                            ['name' => 'csrf_token', 'type' => __('marketing.cookies.type_essential'), 'duration' => __('marketing.cookies.session'), 'purpose' => __('marketing.cookies.purpose_csrf')],
                            ['name' => 'app_locale', 'type' => __('marketing.cookies.type_essential'), 'duration' => '1 ' . __('marketing.cookies.year'), 'purpose' => __('marketing.cookies.purpose_locale')],
                            ['name' => '_ga', 'type' => __('marketing.cookies.type_analytics'), 'duration' => '2 ' . __('marketing.cookies.years'), 'purpose' => __('marketing.cookies.purpose_ga')],
                            ['name' => '_gid', 'type' => __('marketing.cookies.type_analytics'), 'duration' => '24 ' . __('marketing.cookies.hours'), 'purpose' => __('marketing.cookies.purpose_gid')],
                        ] as $cookie)
                            <tr>
                                <td class="px-6 py-4 text-slate-900 dark:text-white font-mono">{{ $cookie['name'] }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ $cookie['type'] }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ $cookie['duration'] }}</td>
                                <td class="px-6 py-4 text-slate-600 dark:text-slate-400">{{ $cookie['purpose'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Related Links -->
<section class="py-8 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
            <span class="text-slate-500">{{ __('marketing.cookies.related') }}:</span>
            <a href="{{ route('marketing.terms') }}" class="text-red-600 hover:underline">{{ __('marketing.nav.terms') }}</a>
            <a href="{{ route('marketing.privacy') }}" class="text-red-600 hover:underline">{{ __('marketing.nav.privacy') }}</a>
        </div>
    </div>
</section>
@endsection
