@extends('marketing.layouts.app')

@section('title', __('marketing.about.title'))
@section('meta_description', __('marketing.about.meta_description'))

@section('content')
@php
    $isRtl = app()->getLocale() === 'ar';
@endphp

<!-- Hero Section -->
<section class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-6">{{ __('marketing.about.headline') }}</h1>
        <p class="text-xl text-slate-300 max-w-3xl mx-auto">{{ __('marketing.about.subheadline') }}</p>
    </div>
</section>

<!-- Mission Section -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-6">{{ __('marketing.about.mission_title') }}</h2>
                <p class="text-lg text-slate-600 dark:text-slate-400 mb-6">{{ __('marketing.about.mission_text_1') }}</p>
                <p class="text-lg text-slate-600 dark:text-slate-400">{{ __('marketing.about.mission_text_2') }}</p>
            </div>
            <div class="relative">
                <div class="bg-gradient-to-br from-red-600 to-purple-600 rounded-2xl p-1">
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-8">
                        <div class="grid grid-cols-2 gap-6 text-center">
                            <div>
                                <div class="text-4xl font-bold text-red-600 mb-2">500+</div>
                                <div class="text-slate-600 dark:text-slate-400">{{ __('marketing.about.stat_clients') }}</div>
                            </div>
                            <div>
                                <div class="text-4xl font-bold text-red-600 mb-2">30+</div>
                                <div class="text-slate-600 dark:text-slate-400">{{ __('marketing.about.stat_countries') }}</div>
                            </div>
                            <div>
                                <div class="text-4xl font-bold text-red-600 mb-2">10M+</div>
                                <div class="text-slate-600 dark:text-slate-400">{{ __('marketing.about.stat_campaigns') }}</div>
                            </div>
                            <div>
                                <div class="text-4xl font-bold text-red-600 mb-2">50+</div>
                                <div class="text-slate-600 dark:text-slate-400">{{ __('marketing.about.stat_team') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="py-20 bg-slate-100 dark:bg-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.about.values_title') }}</h2>
            <p class="text-slate-600 dark:text-slate-400">{{ __('marketing.about.values_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['icon' => 'fas fa-lightbulb', 'title' => __('marketing.about.value_innovation'), 'desc' => __('marketing.about.value_innovation_desc')],
                ['icon' => 'fas fa-users', 'title' => __('marketing.about.value_customer'), 'desc' => __('marketing.about.value_customer_desc')],
                ['icon' => 'fas fa-shield-alt', 'title' => __('marketing.about.value_integrity'), 'desc' => __('marketing.about.value_integrity_desc')],
                ['icon' => 'fas fa-rocket', 'title' => __('marketing.about.value_excellence'), 'desc' => __('marketing.about.value_excellence_desc')],
            ] as $value)
                <div class="bg-white dark:bg-slate-700 rounded-xl p-6 text-center">
                    <div class="w-14 h-14 mx-auto bg-gradient-to-br from-red-600 to-purple-600 rounded-xl flex items-center justify-center mb-4">
                        <i class="{{ $value['icon'] }} text-2xl text-white"></i>
                    </div>
                    <h3 class="font-semibold text-slate-900 dark:text-white mb-2">{{ $value['title'] }}</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400">{{ $value['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="py-20 bg-white dark:bg-slate-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-4">{{ __('marketing.about.team_title') }}</h2>
            <p class="text-slate-600 dark:text-slate-400">{{ __('marketing.about.team_subtitle') }}</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @forelse($teamMembers ?? [] as $member)
                <div class="text-center">
                    @if($member->image_url)
                        <img src="{{ $member->image_url }}" alt="{{ $member->name }}" class="w-32 h-32 mx-auto rounded-full object-cover mb-4">
                    @else
                        <div class="w-32 h-32 mx-auto rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center mb-4">
                            <span class="text-3xl font-bold text-white">{{ mb_substr($member->name, 0, 1) }}</span>
                        </div>
                    @endif
                    <h3 class="font-semibold text-slate-900 dark:text-white">{{ $member->name }}</h3>
                    <p class="text-sm text-red-600 mb-2">{{ $member->role }}</p>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mb-3">{{ Str::limit($member->bio, 100) }}</p>
                    @if($member->social_links)
                        <div class="flex items-center justify-center gap-3">
                            @foreach($member->social_links as $platform => $url)
                                <a href="{{ $url }}" target="_blank" class="text-slate-400 hover:text-red-600 transition">
                                    <i class="fab fa-{{ $platform }}"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @empty
                @foreach([
                    ['name' => __('marketing.about.team_1_name'), 'role' => __('marketing.about.team_1_role')],
                    ['name' => __('marketing.about.team_2_name'), 'role' => __('marketing.about.team_2_role')],
                    ['name' => __('marketing.about.team_3_name'), 'role' => __('marketing.about.team_3_role')],
                    ['name' => __('marketing.about.team_4_name'), 'role' => __('marketing.about.team_4_role')],
                ] as $member)
                    <div class="text-center">
                        <div class="w-32 h-32 mx-auto rounded-full bg-gradient-to-br from-red-600 to-purple-600 flex items-center justify-center mb-4">
                            <span class="text-3xl font-bold text-white">{{ mb_substr($member['name'], 0, 1) }}</span>
                        </div>
                        <h3 class="font-semibold text-slate-900 dark:text-white">{{ $member['name'] }}</h3>
                        <p class="text-sm text-red-600">{{ $member['role'] }}</p>
                    </div>
                @endforeach
            @endforelse
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-20 bg-gradient-to-br from-red-600 to-purple-700 text-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold mb-6">{{ __('marketing.about.cta_title') }}</h2>
        <p class="text-xl text-white/80 mb-8">{{ __('marketing.about.cta_subtitle') }}</p>
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
            <a href="{{ route('marketing.contact') }}" class="px-8 py-4 bg-white text-red-600 font-semibold rounded-lg hover:bg-slate-100 transition">
                {{ __('marketing.about.cta_contact') }}
            </a>
            <a href="{{ route('marketing.demo') }}" class="px-8 py-4 bg-white/10 text-white font-semibold rounded-lg hover:bg-white/20 transition">
                {{ __('marketing.nav.demo') }}
            </a>
        </div>
    </div>
</section>
@endsection
