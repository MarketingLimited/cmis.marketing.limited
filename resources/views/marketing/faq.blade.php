@extends('marketing.layouts.app')

@section('title', __('marketing.faq.title'))
@section('meta_description', __('marketing.faq.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ __('marketing.faq.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.faq.subheadline') }}</p>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Category Tabs -->
        @if(isset($categories) && $categories->isNotEmpty())
            <div x-data="{ activeCategory: '{{ $categories->first()->id }}' }" class="mb-12">
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    @foreach($categories as $category)
                        <button @click="activeCategory = '{{ $category->id }}'"
                                :class="activeCategory === '{{ $category->id }}' ? 'bg-red-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300'"
                                class="px-4 py-2 rounded-full font-medium transition">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                @foreach($categories as $category)
                    <div x-show="activeCategory === '{{ $category->id }}'" x-transition class="space-y-4">
                        @foreach($category->faqItems as $faq)
                            <div x-data="{ open: false }" class="bg-slate-50 dark:bg-slate-800 rounded-xl overflow-hidden">
                                <button @click="open = !open" class="w-full flex items-center justify-between p-6 text-start">
                                    <span class="font-semibold text-slate-900 dark:text-white pe-4">{{ $faq->question }}</span>
                                    <i class="fas fa-chevron-down text-slate-400 transition-transform flex-shrink-0" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open" x-collapse class="px-6 pb-6">
                                    <div class="prose prose-slate dark:prose-invert max-w-none">
                                        {!! $faq->answer !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @else
            <!-- Default FAQ without categories -->
            <div class="space-y-4" x-data="{ openFaq: null }">
                @foreach([
                    // General
                    ['q' => __('marketing.faq.general_1_q'), 'a' => __('marketing.faq.general_1_a')],
                    ['q' => __('marketing.faq.general_2_q'), 'a' => __('marketing.faq.general_2_a')],
                    ['q' => __('marketing.faq.general_3_q'), 'a' => __('marketing.faq.general_3_a')],
                    // Features
                    ['q' => __('marketing.faq.features_1_q'), 'a' => __('marketing.faq.features_1_a')],
                    ['q' => __('marketing.faq.features_2_q'), 'a' => __('marketing.faq.features_2_a')],
                    // Pricing
                    ['q' => __('marketing.faq.pricing_1_q'), 'a' => __('marketing.faq.pricing_1_a')],
                    ['q' => __('marketing.faq.pricing_2_q'), 'a' => __('marketing.faq.pricing_2_a')],
                    ['q' => __('marketing.faq.pricing_3_q'), 'a' => __('marketing.faq.pricing_3_a')],
                    // Support
                    ['q' => __('marketing.faq.support_1_q'), 'a' => __('marketing.faq.support_1_a')],
                    ['q' => __('marketing.faq.support_2_q'), 'a' => __('marketing.faq.support_2_a')],
                ] as $index => $faq)
                    <div class="bg-slate-50 dark:bg-slate-800 rounded-xl overflow-hidden">
                        <button @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}" class="w-full flex items-center justify-between p-6 text-start">
                            <span class="font-semibold text-slate-900 dark:text-white pe-4">{{ $faq['q'] }}</span>
                            <i class="fas fa-chevron-down text-slate-400 transition-transform flex-shrink-0" :class="openFaq === {{ $index }} ? 'rotate-180' : ''"></i>
                        </button>
                        <div x-show="openFaq === {{ $index }}" x-collapse class="px-6 pb-6">
                            <p class="text-slate-600 dark:text-slate-400">{{ $faq['a'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

<!-- Still Have Questions -->
<section class="py-20 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="bg-white dark:bg-slate-700 rounded-2xl p-8 md:p-12">
            <div class="w-16 h-16 mx-auto bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-600 mb-6">
                <i class="fas fa-question-circle text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.faq.still_questions') }}</h2>
            <p class="text-slate-600 dark:text-slate-400 mb-6">{{ __('marketing.faq.still_questions_text') }}</p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('marketing.contact') }}" class="px-6 py-3 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                    {{ __('marketing.faq.contact_support') }}
                </a>
                <a href="{{ route('marketing.demo') }}" class="px-6 py-3 bg-slate-200 dark:bg-slate-600 text-slate-900 dark:text-white font-semibold rounded-lg hover:bg-slate-300 dark:hover:bg-slate-500 transition">
                    {{ __('marketing.faq.schedule_demo') }}
                </a>
            </div>
        </div>
    </div>
</section>
@endsection
