@extends('layouts.admin')

@section('title', __('campaigns.edit_campaign'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="max-w-5xl mx-auto" x-data="campaignEditor()" dir="{{ $dir }}">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}" aria-label="Breadcrumb">
            <ol class="inline-flex items-center {{ $isRtl ? 'space-x-reverse space-x-1 md:space-x-3' : 'space-x-1 md:space-x-3' }}">
                <li>
                    <a href="{{ route('orgs.dashboard.index', ['org' => $currentOrg]) }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} text-gray-400 mx-2 text-xs"></i>
                    <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}" class="text-gray-500 hover:text-gray-700">
                        {{ __('campaigns.campaigns') }}
                    </a>
                </li>
                <li class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} text-gray-400 mx-2 text-xs"></i>
                    <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}" class="text-gray-500 hover:text-gray-700">
                        {{ Str::limit($campaign->name, 20) }}
                    </a>
                </li>
                <li class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-chevron-{{ $isRtl ? 'left' : 'right' }} text-gray-400 mx-2 text-xs"></i>
                    <span class="text-gray-700 font-medium">{{ __('campaigns.edit_campaign') }}</span>
                </li>
            </ol>
        </nav>
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div class="{{ $isRtl ? 'text-right' : '' }}">
                <h1 class="text-3xl font-bold text-gray-900">{{ __('campaigns.edit_campaign') }}</h1>
                <p class="mt-2 text-gray-600">{{ __('campaigns.update_campaign_settings') }}</p>
            </div>
            <span class="px-3 py-1 text-sm font-medium rounded-full"
                  :class="{
                      'bg-green-100 text-green-800': '{{ $campaign->status }}' === 'active',
                      'bg-yellow-100 text-yellow-800': '{{ $campaign->status }}' === 'paused',
                      'bg-gray-100 text-gray-800': '{{ $campaign->status }}' === 'draft',
                      'bg-blue-100 text-blue-800': '{{ $campaign->status }}' === 'completed'
                  }">
                {{ __('campaigns.status.' . ($campaign->status ?? 'draft')) }}
            </span>
        </div>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
            <div class="flex items-center mb-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-exclamation-circle {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                <strong>{{ __('campaigns.fix_errors') }}</strong>
            </div>
            <ul class="{{ $isRtl ? 'list-disc list-inside mr-5' : 'list-disc list-inside' }} text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
            <i class="fas fa-check-circle {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('orgs.campaigns.update', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Platform & Objective (Read-only) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-lock {{ $isRtl ? 'ml-3' : 'mr-3' }} text-gray-400"></i>
                    {{ __('campaigns.platform_objective') }}
                    <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-xs font-normal text-gray-500">({{ __('campaigns.cannot_change') }})</span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Platform Display -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.advertising_platform') }}</label>
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-200 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <template x-if="form.platform === 'meta'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fab fa-meta text-xl text-blue-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.platforms.meta') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.platforms.facebook') }} & {{ __('campaigns.platforms.instagram') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'google'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fab fa-google text-xl text-red-500"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.platforms.google') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.google_platforms') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'tiktok'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fab fa-tiktok text-xl text-white"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.platforms.tiktok') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.tiktok_formats') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'snapchat'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fab fa-snapchat-ghost text-xl text-white"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.platforms.snapchat') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.snapchat_formats') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'twitter'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fab fa-x-twitter text-xl text-white"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.platforms.twitter') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.twitter_formats') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'linkedin'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-blue-700 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fab fa-linkedin-in text-xl text-white"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.platforms.linkedin') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.linkedin_formats') }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Hidden input to preserve the value -->
                        <input type="hidden" name="platform" :value="form.platform">
                    </div>

                    <!-- Objective Display -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.campaign_objective') }}</label>
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-200 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <template x-if="form.objective === 'awareness'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-eye text-xl text-purple-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.awareness') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.increase_brand_recognition') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'traffic'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-mouse-pointer text-xl text-blue-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.traffic') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.drive_website_visitors') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'engagement'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-heart text-xl text-pink-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.engagement') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.likes_comments_shares') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'leads'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-user-plus text-xl text-green-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.leads') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.collect_leads_contacts') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'conversions'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-shopping-cart text-xl text-orange-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.conversions') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.drive_sales_signups') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'app_installs'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-mobile-alt text-xl text-indigo-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.app_installs') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.get_app_downloads') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'video_views'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-play text-xl text-red-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.video_views') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.maximize_video_engagement') }}</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'reach'">
                                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-3' : 'mr-3' }}">
                                        <i class="fas fa-broadcast-tower text-xl text-teal-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <p class="font-medium text-gray-900">{{ __('campaigns.objectives.reach') }}</p>
                                        <p class="text-sm text-gray-500">{{ __('campaigns.maximum_unique_users') }}</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Hidden input to preserve the value -->
                        <input type="hidden" name="campaign_type" :value="form.objective">
                    </div>
                </div>
            </div>
        </div>

        <!-- Campaign Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                <h2 class="text-lg font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-edit {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                    {{ __('campaigns.campaign_details') }}
                </h2>
            </div>
            <div class="p-6 space-y-6">
                <!-- Campaign Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.campaign_name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" x-model="form.name" required
                           dir="{{ $dir }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $isRtl ? 'text-right' : '' }}">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.description') }}
                    </label>
                    <textarea name="description" id="description" rows="3" x-model="form.description"
                              placeholder="{{ __('campaigns.description_placeholder') }}"
                              dir="{{ $dir }}"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none {{ $isRtl ? 'text-right' : '' }}"></textarea>
                </div>

                <!-- Ad Format -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_format') }}</label>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <template x-for="format in getAdFormats()" :key="format.value">
                            <label class="cursor-pointer">
                                <input type="radio" name="ad_format" :value="format.value" x-model="form.ad_format" class="sr-only">
                                <div class="p-3 border-2 rounded-lg text-center transition-all"
                                     :class="{ 'border-indigo-500 bg-indigo-50': form.ad_format === format.value, 'border-gray-200 hover:border-gray-300': form.ad_format !== format.value }">
                                    <i :class="format.icon + ' text-xl mb-1'"></i>
                                    <p class="text-sm font-medium" x-text="format.label"></p>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Destination URL -->
                <div x-show="['traffic', 'conversions'].includes(form.objective)">
                    <label for="destination_url" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                        {{ __('campaigns.destination_url') }}
                    </label>
                    <input type="url" name="destination_url" id="destination_url" x-model="form.destination_url"
                           placeholder="https://yourwebsite.com/landing-page"
                           dir="ltr"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Call to Action -->
                <div>
                    <label for="cta" class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.call_to_action') }}</label>
                    <select name="cta" id="cta" x-model="form.cta"
                            dir="{{ $dir }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $isRtl ? 'text-right' : '' }}">
                        <option value="">{{ __('campaigns.select_cta') }}</option>
                        <option value="learn_more">{{ __('campaigns.cta.learn_more') }}</option>
                        <option value="shop_now">{{ __('campaigns.cta.shop_now') }}</option>
                        <option value="sign_up">{{ __('campaigns.cta.sign_up') }}</option>
                        <option value="contact_us">{{ __('campaigns.cta.contact_us') }}</option>
                        <option value="download">{{ __('campaigns.cta.download') }}</option>
                        <option value="get_offer">{{ __('campaigns.cta.get_offer') }}</option>
                        <option value="book_now">{{ __('campaigns.cta.book_now') }}</option>
                        <option value="watch_more">{{ __('campaigns.cta.watch_more') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Budget & Schedule -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                <h2 class="text-lg font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-wallet {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                    {{ __('campaigns.budget_schedule') }}
                </h2>
            </div>
            <div class="p-6 space-y-6">
                <!-- Budget Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.budget_type') }}</label>
                    <div class="flex gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="budget_type" value="daily" x-model="form.budget_type" class="sr-only">
                            <div class="p-4 border-2 rounded-lg text-center transition-all"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.budget_type === 'daily', 'border-gray-200': form.budget_type !== 'daily' }">
                                <i class="fas fa-calendar-day text-2xl mb-2" :class="{ 'text-indigo-600': form.budget_type === 'daily', 'text-gray-400': form.budget_type !== 'daily' }"></i>
                                <p class="font-medium">{{ __('campaigns.daily_budget') }}</p>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="budget_type" value="lifetime" x-model="form.budget_type" class="sr-only">
                            <div class="p-4 border-2 rounded-lg text-center transition-all"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.budget_type === 'lifetime', 'border-gray-200': form.budget_type !== 'lifetime' }">
                                <i class="fas fa-calendar-alt text-2xl mb-2" :class="{ 'text-indigo-600': form.budget_type === 'lifetime', 'text-gray-400': form.budget_type !== 'lifetime' }"></i>
                                <p class="font-medium">{{ __('campaigns.lifetime_budget') }}</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Budget & Bid Strategy -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            <span x-text="form.budget_type === 'daily' ? '{{ __('campaigns.daily_budget') }}' : '{{ __('campaigns.total_budget') }}'"></span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 {{ $isRtl ? 'right-0 pr-4' : 'left-0 pl-4' }} flex items-center text-gray-500">{{ $isRtl ? 'ر.س' : '$' }}</span>
                            <input type="number" name="budget" x-model="form.budget" required min="1" step="0.01"
                                   dir="ltr"
                                   class="w-full {{ $isRtl ? 'pr-12 pl-4 text-right' : 'pl-8 pr-4' }} py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        @error('budget')
                            <p class="mt-1 text-sm text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.bid_strategy') }}</label>
                        <select name="bid_strategy" x-model="form.bid_strategy"
                                dir="{{ $dir }}"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $isRtl ? 'text-right' : '' }}">
                            <option value="lowest_cost">{{ __('campaigns.bid_strategies.lowest_cost') }}</option>
                            <option value="cost_cap">{{ __('campaigns.bid_strategies.cost_cap') }}</option>
                            <option value="bid_cap">{{ __('campaigns.bid_strategies.bid_cap') }}</option>
                            <option value="target_cost">{{ __('campaigns.bid_strategies.target_cost') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.start_date') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" name="start_date" x-model="form.start_date" required
                               dir="ltr"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600 {{ $isRtl ? 'text-right' : '' }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.end_date') }}</label>
                        <input type="datetime-local" name="end_date" x-model="form.end_date"
                               dir="ltr"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                <h2 class="text-lg font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-cog {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                    {{ __('campaigns.status_settings') }}
                </h2>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <input type="radio" name="status" value="draft" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">{{ __('campaigns.status.draft') }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <input type="radio" name="status" value="active" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">{{ __('campaigns.status.active') }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <input type="radio" name="status" value="paused" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">{{ __('campaigns.status.paused') }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <input type="radio" name="status" value="completed" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">{{ __('campaigns.status.completed') }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <button type="button" onclick="confirmDelete()" class="px-6 py-3 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-trash {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                {{ __('campaigns.delete_campaign') }}
            </button>

            <div class="flex items-center gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}"
                   class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium">
                    {{ __('campaigns.cancel') }}
                </a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition shadow-sm flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-save {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('campaigns.save_changes') }}
                </button>
            </div>
        </div>
    </form>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" action="{{ route('orgs.campaigns.destroy', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
<script>
function confirmDelete() {
    if (confirm('{{ __("campaigns.confirm_delete") }}')) {
        document.getElementById('deleteForm').submit();
    }
}

function campaignEditor() {
    return {
        form: {
            platform: '{{ $campaign->platform ?? "meta" }}',
            objective: '{{ $campaign->campaign_type ?? $campaign->objective ?? "awareness" }}',
            name: '{{ $campaign->name }}',
            description: `{{ $campaign->description ?? '' }}`,
            ad_format: '{{ $campaign->ad_format ?? "image" }}',
            destination_url: '{{ $campaign->destination_url ?? '' }}',
            cta: '{{ $campaign->cta ?? '' }}',
            budget_type: '{{ $campaign->budget_type ?? "daily" }}',
            budget: '{{ $campaign->budget ?? '' }}',
            bid_strategy: '{{ $campaign->bid_strategy ?? "lowest_cost" }}',
            start_date: '{{ $campaign->start_date ? $campaign->start_date->format("Y-m-d\TH:i") : "" }}',
            end_date: '{{ $campaign->end_date ? $campaign->end_date->format("Y-m-d\TH:i") : "" }}',
            status: '{{ $campaign->status ?? "draft" }}'
        },

        getAdFormats() {
            const formats = {
                meta: [
                    { value: 'image', label: '{{ __("campaigns.formats.single_image") }}', icon: 'fas fa-image' },
                    { value: 'video', label: '{{ __("campaigns.formats.video") }}', icon: 'fas fa-video' },
                    { value: 'carousel', label: '{{ __("campaigns.formats.carousel") }}', icon: 'fas fa-images' },
                    { value: 'stories', label: '{{ __("campaigns.format_stories") }}', icon: 'fas fa-mobile-alt' }
                ],
                google: [
                    { value: 'search', label: '{{ __("campaigns.format_search") }}', icon: 'fas fa-search' },
                    { value: 'display', label: '{{ __("campaigns.format_display") }}', icon: 'fas fa-desktop' },
                    { value: 'video', label: '{{ __("campaigns.formats.video") }}', icon: 'fas fa-video' },
                    { value: 'shopping', label: '{{ __("campaigns.format_shopping") }}', icon: 'fas fa-shopping-bag' }
                ],
                tiktok: [
                    { value: 'in_feed', label: '{{ __("campaigns.format_in_feed") }}', icon: 'fas fa-stream' },
                    { value: 'topview', label: '{{ __("campaigns.format_topview") }}', icon: 'fas fa-star' },
                    { value: 'spark', label: '{{ __("campaigns.format_spark_ads") }}', icon: 'fas fa-bolt' },
                    { value: 'branded', label: '{{ __("campaigns.format_branded") }}', icon: 'fas fa-hashtag' }
                ],
                snapchat: [
                    { value: 'snap_ad', label: '{{ __("campaigns.format_snap_ad") }}', icon: 'fas fa-play' },
                    { value: 'story', label: '{{ __("campaigns.format_story_ad") }}', icon: 'fas fa-book-open' },
                    { value: 'collection', label: '{{ __("campaigns.formats.collection") }}', icon: 'fas fa-th' },
                    { value: 'ar_lens', label: '{{ __("campaigns.format_ar_lens") }}', icon: 'fas fa-magic' }
                ],
twitter: [
                    { value: 'promoted_tweet', label: '{{ __("campaigns.format_promoted") }}', icon: 'fas fa-retweet' },
                    { value: 'follower', label: '{{ __("campaigns.format_follower") }}', icon: 'fas fa-user-plus' },
                    { value: 'video', label: '{{ __("campaigns.formats.video") }}', icon: 'fas fa-video' },
                    { value: 'carousel', label: '{{ __("campaigns.formats.carousel") }}', icon: 'fas fa-images' }
                ],
                linkedin: [
                    { value: 'sponsored_content', label: '{{ __("campaigns.format_sponsored") }}', icon: 'fas fa-newspaper' },
                    { value: 'message', label: '{{ __("campaigns.format_message") }}', icon: 'fas fa-envelope' },
                    { value: 'text', label: '{{ __("campaigns.format_text_ad") }}', icon: 'fas fa-font' },
                    { value: 'dynamic', label: '{{ __("campaigns.format_dynamic") }}', icon: 'fas fa-magic' }
                ]
            };
            return formats[this.form.platform] || formats.meta;
        }
    }
}
</script>
@endpush
