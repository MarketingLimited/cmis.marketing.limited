@extends('marketing.layouts.app')

@section('title', __('marketing.demo.title'))
@section('meta_description', __('marketing.demo.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="relative bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-24 overflow-hidden">
    <!-- Background Decoration -->
    <div class="absolute inset-0 bg-[url('/images/grid-pattern.svg')] opacity-10"></div>
    <div class="absolute top-20 {{ $isRtl ? 'right-20' : 'left-20' }} w-72 h-72 bg-violet-600/10 rounded-full blur-3xl animate-pulse"></div>
    <div class="absolute bottom-20 {{ $isRtl ? 'left-20' : 'right-20' }} w-96 h-96 bg-red-600/10 rounded-full blur-3xl animate-pulse" style="animation-delay: 1s;"></div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <span class="inline-block px-4 py-1 bg-violet-600/20 text-violet-400 rounded-full text-sm font-medium mb-6">
            <i class="fas fa-video me-1"></i> {{ __('marketing.demo.badge') ?? __('marketing.demo.headline') }}
        </span>
        <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight">{{ __('marketing.demo.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.demo.subheadline') }}</p>
    </div>
</section>

<!-- Demo Request Section -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <!-- Benefits -->
            <div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-6">{{ __('marketing.demo.what_you_get') }}</h2>
                <div class="space-y-6">
                    @foreach([
                        ['icon' => 'fas fa-video', 'title' => __('marketing.demo.benefit_1_title'), 'desc' => __('marketing.demo.benefit_1_desc')],
                        ['icon' => 'fas fa-user-tie', 'title' => __('marketing.demo.benefit_2_title'), 'desc' => __('marketing.demo.benefit_2_desc')],
                        ['icon' => 'fas fa-comments', 'title' => __('marketing.demo.benefit_3_title'), 'desc' => __('marketing.demo.benefit_3_desc')],
                        ['icon' => 'fas fa-gift', 'title' => __('marketing.demo.benefit_4_title'), 'desc' => __('marketing.demo.benefit_4_desc')],
                    ] as $benefit)
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600">
                                <i class="{{ $benefit['icon'] }}"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $benefit['title'] }}</h3>
                                <p class="text-slate-600 dark:text-slate-400">{{ $benefit['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Trust Badges -->
                <div class="mt-10 pt-8 border-t border-slate-200 dark:border-slate-700">
                    <p class="text-sm text-slate-500 mb-4">{{ __('marketing.demo.trusted_by') }}</p>
                    <div class="flex items-center gap-8">
                        <div class="text-2xl text-slate-400"><i class="fab fa-facebook"></i></div>
                        <div class="text-2xl text-slate-400"><i class="fab fa-google"></i></div>
                        <div class="text-2xl text-slate-400"><i class="fab fa-tiktok"></i></div>
                        <div class="text-2xl text-slate-400"><i class="fab fa-linkedin"></i></div>
                    </div>
                </div>
            </div>

            <!-- Demo Form -->
            <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8">
                <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">{{ __('marketing.demo.form_title') }}</h3>

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('marketing.demo.submit') }}" method="POST" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.first_name') }} *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('first_name') border-red-500 @enderror">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.last_name') }} *</label>
                            <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                   class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('last_name') border-red-500 @enderror">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.work_email') }} *</label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.phone') }}</label>
                        <input type="tel" name="phone" value="{{ old('phone') }}"
                               class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.company') }} *</label>
                        <input type="text" name="company" value="{{ old('company') }}" required
                               class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('company') border-red-500 @enderror">
                        @error('company')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.company_size') }} *</label>
                        <select name="company_size" required class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('company_size') border-red-500 @enderror">
                            <option value="">{{ __('marketing.demo.select_size') }}</option>
                            <option value="1-10" {{ old('company_size') === '1-10' ? 'selected' : '' }}>1-10</option>
                            <option value="11-50" {{ old('company_size') === '11-50' ? 'selected' : '' }}>11-50</option>
                            <option value="51-200" {{ old('company_size') === '51-200' ? 'selected' : '' }}>51-200</option>
                            <option value="201-500" {{ old('company_size') === '201-500' ? 'selected' : '' }}>201-500</option>
                            <option value="500+" {{ old('company_size') === '500+' ? 'selected' : '' }}>500+</option>
                        </select>
                        @error('company_size')
                            <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.what_interests') }}</label>
                        <div class="space-y-2">
                            @foreach([
                                'campaigns' => __('marketing.demo.interest_campaigns'),
                                'analytics' => __('marketing.demo.interest_analytics'),
                                'social' => __('marketing.demo.interest_social'),
                                'ai' => __('marketing.demo.interest_ai'),
                            ] as $value => $label)
                                <label class="flex items-center gap-3">
                                    <input type="checkbox" name="interests[]" value="{{ $value }}" class="w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500">
                                    <span class="text-slate-700 dark:text-slate-300">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.demo.additional_info') }}</label>
                        <textarea name="message" rows="3"
                                  class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">{{ old('message') }}</textarea>
                    </div>

                    <button type="submit" class="w-full px-6 py-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                        {{ __('marketing.demo.submit_button') }}
                    </button>

                    <p class="text-xs text-slate-500 text-center">
                        {{ __('marketing.demo.privacy_note') }}
                        <a href="{{ route('marketing.privacy') }}" class="text-red-600 hover:underline">{{ __('marketing.contact.privacy_policy') }}</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
