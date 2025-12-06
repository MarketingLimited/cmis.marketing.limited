@extends('marketing.layouts.app')

@section('title', __('marketing.contact.title'))
@section('meta_description', __('marketing.contact.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ __('marketing.contact.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.contact.subheadline') }}</p>
    </div>
</section>

<!-- Contact Section -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-12">
            <!-- Contact Info -->
            <div class="lg:col-span-1 space-y-8">
                <div>
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-4">{{ __('marketing.contact.get_in_touch') }}</h3>
                    <p class="text-slate-600 dark:text-slate-400">{{ __('marketing.contact.contact_text') }}</p>
                </div>

                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('marketing.contact.email') }}</h4>
                            <a href="mailto:{{ $settings['contact_email'] ?? 'hello@cmis.io' }}" class="text-slate-600 dark:text-slate-400 hover:text-red-600 transition">
                                {{ $settings['contact_email'] ?? 'hello@cmis.io' }}
                            </a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('marketing.contact.phone') }}</h4>
                            <a href="tel:{{ $settings['contact_phone'] ?? '+1234567890' }}" class="text-slate-600 dark:text-slate-400 hover:text-red-600 transition">
                                {{ $settings['contact_phone'] ?? '+1 (234) 567-890' }}
                            </a>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center text-red-600">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div>
                            <h4 class="font-semibold text-slate-900 dark:text-white">{{ __('marketing.contact.address') }}</h4>
                            <p class="text-slate-600 dark:text-slate-400">
                                {{ $settings['address'] ?? __('marketing.contact.default_address') }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Social Links -->
                <div>
                    <h4 class="font-semibold text-slate-900 dark:text-white mb-4">{{ __('marketing.contact.follow_us') }}</h4>
                    <div class="flex items-center gap-4">
                        <a href="#" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-red-600 hover:text-white transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-red-600 hover:text-white transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-red-600 hover:text-white transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-slate-100 dark:bg-slate-700 rounded-lg flex items-center justify-center text-slate-600 dark:text-slate-400 hover:bg-red-600 hover:text-white transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="lg:col-span-2">
                <div class="bg-slate-50 dark:bg-slate-800 rounded-2xl p-8">
                    <h3 class="text-xl font-semibold text-slate-900 dark:text-white mb-6">{{ __('marketing.contact.send_message') }}</h3>

                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-lg">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form action="{{ route('marketing.contact.submit') }}" method="POST" class="space-y-6">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.contact.first_name') }} *</label>
                                <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                       class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('first_name') border-red-500 @enderror">
                                @error('first_name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.contact.last_name') }} *</label>
                                <input type="text" name="last_name" value="{{ old('last_name') }}" required
                                       class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('last_name') border-red-500 @enderror">
                                @error('last_name')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.contact.email_field') }} *</label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                       class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('email') border-red-500 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.contact.company') }}</label>
                                <input type="text" name="company" value="{{ old('company') }}"
                                       class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.contact.subject') }} *</label>
                            <select name="subject" required class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('subject') border-red-500 @enderror">
                                <option value="">{{ __('marketing.contact.select_subject') }}</option>
                                <option value="sales" {{ old('subject') === 'sales' ? 'selected' : '' }}>{{ __('marketing.contact.subject_sales') }}</option>
                                <option value="support" {{ old('subject') === 'support' ? 'selected' : '' }}>{{ __('marketing.contact.subject_support') }}</option>
                                <option value="partnership" {{ old('subject') === 'partnership' ? 'selected' : '' }}>{{ __('marketing.contact.subject_partnership') }}</option>
                                <option value="other" {{ old('subject') === 'other' ? 'selected' : '' }}>{{ __('marketing.contact.subject_other') }}</option>
                            </select>
                            @error('subject')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">{{ __('marketing.contact.message') }} *</label>
                            <textarea name="message" rows="5" required
                                      class="w-full px-4 py-3 bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-lg text-slate-900 dark:text-white focus:ring-2 focus:ring-red-500 focus:border-transparent @error('message') border-red-500 @enderror">{{ old('message') }}</textarea>
                            @error('message')
                                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex items-start gap-3">
                            <input type="checkbox" name="privacy" id="privacy" required class="mt-1 w-4 h-4 text-red-600 border-slate-300 rounded focus:ring-red-500">
                            <label for="privacy" class="text-sm text-slate-600 dark:text-slate-400">
                                {{ __('marketing.contact.privacy_agree') }}
                                <a href="{{ route('marketing.privacy') }}" class="text-red-600 hover:underline">{{ __('marketing.contact.privacy_policy') }}</a>
                            </label>
                        </div>

                        <button type="submit" class="w-full px-6 py-4 bg-red-600 text-white font-semibold rounded-lg hover:bg-red-700 transition">
                            {{ __('marketing.contact.send_button') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
