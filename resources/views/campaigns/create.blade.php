@extends('layouts.admin')

@section('title', __('campaigns.create_campaign'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="max-w-5xl mx-auto" x-data="campaignWizard()" dir="{{ $dir }}">
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
                    <span class="text-gray-700 font-medium">{{ __('campaigns.create_campaign') }}</span>
                </li>
            </ol>
        </nav>
        <h1 class="text-3xl font-bold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.create_campaign') }}</h1>
        <p class="mt-2 text-gray-600 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.wizard.create_campaign') }}</p>
    </div>

    <!-- Progress Steps -->
    <div class="mb-8">
        <div class="flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <template x-for="(stepInfo, index) in steps" :key="index">
                <div class="flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}" :class="{ 'flex-1': index < steps.length - 1 }">
                    <!-- Step Circle -->
                    <div class="flex items-center justify-center w-10 h-10 rounded-full border-2 transition-all duration-300"
                         :class="{
                             'bg-indigo-600 border-indigo-600 text-white': currentStep > index + 1,
                             'bg-indigo-600 border-indigo-600 text-white ring-4 ring-indigo-100': currentStep === index + 1,
                             'bg-white border-gray-300 text-gray-500': currentStep < index + 1
                         }">
                        <template x-if="currentStep > index + 1">
                            <i class="fas fa-check text-sm"></i>
                        </template>
                        <template x-if="currentStep <= index + 1">
                            <span x-text="index + 1" class="text-sm font-semibold"></span>
                        </template>
                    </div>
                    <!-- Step Label -->
                    <span class="{{ $isRtl ? 'mr-3' : 'ml-3' }} text-sm font-medium hidden sm:block"
                          :class="{ 'text-indigo-600': currentStep >= index + 1, 'text-gray-500': currentStep < index + 1 }"
                          x-text="stepInfo.title"></span>
                    <!-- Connector Line -->
                    <template x-if="index < steps.length - 1">
                        <div class="flex-1 h-0.5 mx-4 transition-all duration-300"
                             :class="{ 'bg-indigo-600': currentStep > index + 1, 'bg-gray-200': currentStep <= index + 1 }"></div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
            <div class="flex items-center mb-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-exclamation-circle {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                <strong>{{ __('validation.fix_errors') }}</strong>
            </div>
            <ul class="{{ $isRtl ? 'list-inside pr-5' : 'list-disc list-inside' }} text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('orgs.campaigns.store', ['org' => $currentOrg]) }}" @submit.prevent="submitForm">
        @csrf

        <!-- Step 1: Platform Selection -->
        <div x-show="currentStep === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                    <h2 class="text-xl font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-bullhorn {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                        {{ __('campaigns.wizard.step_1_title') }}
                    </h2>
                    <p class="text-indigo-100 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.select_platform') }}</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Meta Ads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="meta" x-model="form.platform" class="sr-only peer">
                            <div class="p-6 border-2 rounded-xl transition-all duration-200 peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:border-gray-400 hover:shadow-md">
                                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                                        <i class="fab fa-meta text-2xl text-blue-600"></i>
                                    </div>
                                    <div class="w-5 h-5 border-2 rounded-full peer-checked:bg-blue-500 peer-checked:border-blue-500 transition-all"
                                         :class="{ 'bg-blue-500 border-blue-500': form.platform === 'meta', 'border-gray-300': form.platform !== 'meta' }">
                                        <i class="fas fa-check text-white text-xs" x-show="form.platform === 'meta'"></i>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.meta') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.facebook') }} & {{ __('campaigns.platforms.instagram') }}</p>
                                <div class="mt-3 flex flex-wrap gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Feed</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Stories</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Reels</span>
                                </div>
                            </div>
                        </label>

                        <!-- Google Ads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="google" x-model="form.platform" class="sr-only peer">
                            <div class="p-6 border-2 rounded-xl transition-all duration-200 peer-checked:border-red-500 peer-checked:bg-red-50 hover:border-gray-400 hover:shadow-md">
                                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                                        <i class="fab fa-google text-2xl text-red-500"></i>
                                    </div>
                                    <div class="w-5 h-5 border-2 rounded-full transition-all"
                                         :class="{ 'bg-red-500 border-red-500': form.platform === 'google', 'border-gray-300': form.platform !== 'google' }">
                                        <i class="fas fa-check text-white text-xs" x-show="form.platform === 'google'"></i>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.google') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">Search, Display & YouTube</p>
                                <div class="mt-3 flex flex-wrap gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Search</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Display</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Video</span>
                                </div>
                            </div>
                        </label>

                        <!-- TikTok Ads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="tiktok" x-model="form.platform" class="sr-only peer">
                            <div class="p-6 border-2 rounded-xl transition-all duration-200 peer-checked:border-pink-500 peer-checked:bg-pink-50 hover:border-gray-400 hover:shadow-md">
                                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center">
                                        <i class="fab fa-tiktok text-2xl text-white"></i>
                                    </div>
                                    <div class="w-5 h-5 border-2 rounded-full transition-all"
                                         :class="{ 'bg-pink-500 border-pink-500': form.platform === 'tiktok', 'border-gray-300': form.platform !== 'tiktok' }">
                                        <i class="fas fa-check text-white text-xs" x-show="form.platform === 'tiktok'"></i>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.tiktok') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">In-Feed & TopView</p>
                                <div class="mt-3 flex flex-wrap gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">In-Feed</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">TopView</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Spark</span>
                                </div>
                            </div>
                        </label>

                        <!-- Snapchat Ads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="snapchat" x-model="form.platform" class="sr-only peer">
                            <div class="p-6 border-2 rounded-xl transition-all duration-200 peer-checked:border-yellow-500 peer-checked:bg-yellow-50 hover:border-gray-400 hover:shadow-md">
                                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-12 h-12 bg-yellow-400 rounded-xl flex items-center justify-center">
                                        <i class="fab fa-snapchat-ghost text-2xl text-white"></i>
                                    </div>
                                    <div class="w-5 h-5 border-2 rounded-full transition-all"
                                         :class="{ 'bg-yellow-500 border-yellow-500': form.platform === 'snapchat', 'border-gray-300': form.platform !== 'snapchat' }">
                                        <i class="fas fa-check text-white text-xs" x-show="form.platform === 'snapchat'"></i>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.snapchat') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">Snap Ads & Filters</p>
                                <div class="mt-3 flex flex-wrap gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Snap Ads</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Story</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">AR Lens</span>
                                </div>
                            </div>
                        </label>

                        <!-- X (Twitter) Ads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="twitter" x-model="form.platform" class="sr-only peer">
                            <div class="p-6 border-2 rounded-xl transition-all duration-200 peer-checked:border-gray-800 peer-checked:bg-gray-50 hover:border-gray-400 hover:shadow-md">
                                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-12 h-12 bg-gray-900 rounded-xl flex items-center justify-center">
                                        <i class="fab fa-x-twitter text-2xl text-white"></i>
                                    </div>
                                    <div class="w-5 h-5 border-2 rounded-full transition-all"
                                         :class="{ 'bg-gray-800 border-gray-800': form.platform === 'twitter', 'border-gray-300': form.platform !== 'twitter' }">
                                        <i class="fas fa-check text-white text-xs" x-show="form.platform === 'twitter'"></i>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.twitter') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">Promoted Posts & Trends</p>
                                <div class="mt-3 flex flex-wrap gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Promoted</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Follower</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Trend</span>
                                </div>
                            </div>
                        </label>

                        <!-- LinkedIn Ads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="platform" value="linkedin" x-model="form.platform" class="sr-only peer">
                            <div class="p-6 border-2 rounded-xl transition-all duration-200 peer-checked:border-blue-700 peer-checked:bg-blue-50 hover:border-gray-400 hover:shadow-md">
                                <div class="flex items-center justify-between mb-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-12 h-12 bg-blue-700 rounded-xl flex items-center justify-center">
                                        <i class="fab fa-linkedin-in text-2xl text-white"></i>
                                    </div>
                                    <div class="w-5 h-5 border-2 rounded-full transition-all"
                                         :class="{ 'bg-blue-700 border-blue-700': form.platform === 'linkedin', 'border-gray-300': form.platform !== 'linkedin' }">
                                        <i class="fas fa-check text-white text-xs" x-show="form.platform === 'linkedin'"></i>
                                    </div>
                                </div>
                                <h3 class="font-semibold text-gray-900 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.platforms.linkedin') }}</h3>
                                <p class="text-sm text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">B2B & Professional</p>
                                <div class="mt-3 flex flex-wrap gap-1 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Sponsored</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Message</span>
                                    <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded">Lead Gen</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <p class="text-red-500 text-sm mt-4 {{ $isRtl ? 'text-right' : '' }}" x-show="errors.platform" x-text="errors.platform"></p>
                </div>
            </div>
        </div>

        <!-- Step 2: Campaign Objective -->
        <div x-show="currentStep === 2" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                    <h2 class="text-xl font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-bullseye {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                        {{ __('campaigns.campaign_objective') }}
                    </h2>
                    <p class="text-indigo-100 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.wizard.objective_help') }}</p>
                </div>
                <div class="p-6">
                    <!-- Platform-specific objectives -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Awareness -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="awareness" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'awareness', 'border-gray-200': form.objective !== 'awareness' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-eye text-purple-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.awareness') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.awareness_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Traffic -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="traffic" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'traffic', 'border-gray-200': form.objective !== 'traffic' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-mouse-pointer text-blue-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.traffic') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.traffic_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Engagement -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="engagement" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'engagement', 'border-gray-200': form.objective !== 'engagement' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-heart text-pink-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.engagement') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.engagement_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Leads -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="leads" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'leads', 'border-gray-200': form.objective !== 'leads' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-user-plus text-green-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.leads') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.leads_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Conversions -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="conversions" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'conversions', 'border-gray-200': form.objective !== 'conversions' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-shopping-cart text-orange-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.conversions') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.conversions_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- App Installs -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="app_installs" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'app_installs', 'border-gray-200': form.objective !== 'app_installs' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-mobile-alt text-indigo-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.app_installs') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.app_installs_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Video Views -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="video_views" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'video_views', 'border-gray-200': form.objective !== 'video_views' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-play text-red-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.objectives.video_views') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.video_views_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>

                        <!-- Reach -->
                        <label class="relative cursor-pointer">
                            <input type="radio" name="objective" value="reach" x-model="form.objective" class="sr-only">
                            <div class="p-5 border-2 rounded-xl transition-all duration-200 hover:border-indigo-300"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.objective === 'reach', 'border-gray-200': form.objective !== 'reach' }">
                                <div class="flex items-start {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center {{ $isRtl ? 'ml-4' : 'mr-4' }}">
                                        <i class="fas fa-broadcast-tower text-teal-600"></i>
                                    </div>
                                    <div class="{{ $isRtl ? 'text-right' : '' }}">
                                        <h3 class="font-semibold text-gray-900">{{ __('campaigns.metrics.reach') }}</h3>
                                        <p class="text-sm text-gray-500 mt-1">{{ __('campaigns.objectives.reach_desc') }}</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <p class="text-red-500 text-sm mt-4 {{ $isRtl ? 'text-right' : '' }}" x-show="errors.objective" x-text="errors.objective"></p>
                </div>
            </div>
        </div>

        <!-- Step 3: Campaign Details -->
        <div x-show="currentStep === 3" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                    <h2 class="text-xl font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-edit {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                        {{ __('campaigns.campaign_details') }}
                    </h2>
                    <p class="text-indigo-100 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.wizard.name_help') }}</p>
                </div>
                <div class="p-6 space-y-6">
                    <!-- Campaign Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.campaign_name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" x-model="form.name" required
                               placeholder="{{ __('campaigns.name_placeholder') }}"
                               dir="{{ $dir }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $isRtl ? 'text-right' : '' }}">
                        <p class="text-red-500 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}" x-show="errors.name" x-text="errors.name"></p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.description') }}
                        </label>
                        <textarea name="description" x-model="form.description" rows="3"
                                  placeholder="{{ __('campaigns.description_placeholder') }}"
                                  dir="{{ $dir }}"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none {{ $isRtl ? 'text-right' : '' }}"></textarea>
                    </div>

                    <!-- Platform-specific Ad Format -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.ad_format') }}
                        </label>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.destination_url') }}
                        </label>
                        <input type="url" name="destination_url" x-model="form.destination_url"
                               placeholder="https://yourwebsite.com/landing-page"
                               dir="ltr"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Call to Action -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                            {{ __('campaigns.call_to_action') }}
                        </label>
                        <select name="cta" x-model="form.cta"
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
        </div>

        <!-- Step 4: Budget & Schedule -->
        <div x-show="currentStep === 4" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-indigo-500 to-purple-600">
                    <h2 class="text-xl font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-wallet {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                        {{ __('campaigns.budget_schedule') }}
                    </h2>
                    <p class="text-indigo-100 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.wizard.budget_help') }}</p>
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
                                    <p class="text-xs text-gray-500 mt-1">{{ __('campaigns.spend_per_day') }}</p>
                                </div>
                            </label>
                            <label class="flex-1 cursor-pointer">
                                <input type="radio" name="budget_type" value="lifetime" x-model="form.budget_type" class="sr-only">
                                <div class="p-4 border-2 rounded-lg text-center transition-all"
                                     :class="{ 'border-indigo-500 bg-indigo-50': form.budget_type === 'lifetime', 'border-gray-200': form.budget_type !== 'lifetime' }">
                                    <i class="fas fa-calendar-alt text-2xl mb-2" :class="{ 'text-indigo-600': form.budget_type === 'lifetime', 'text-gray-400': form.budget_type !== 'lifetime' }"></i>
                                    <p class="font-medium">{{ __('campaigns.lifetime_budget') }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ __('campaigns.total_campaign_spend') }}</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Budget Amount -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">
                                <span x-text="form.budget_type === 'daily' ? '{{ __('campaigns.daily_budget') }}' : '{{ __('campaigns.total_budget') }}'"></span>
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 {{ $isRtl ? 'right-0 pr-4' : 'left-0 pl-4' }} flex items-center text-gray-500">{{ $isRtl ? 'ر.س' : '$' }}</span>
                                <input type="number" name="budget" x-model="form.budget" required min="1" step="0.01"
                                       placeholder="0.00"
                                       dir="ltr"
                                       class="w-full {{ $isRtl ? 'pr-12 pl-4 text-right' : 'pl-8 pr-4' }} py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            <p class="text-red-500 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}" x-show="errors.budget" x-text="errors.budget"></p>
                        </div>

                        <!-- Bid Strategy -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.bid_strategy') }}</label>
                            <select name="bid_strategy" x-model="form.bid_strategy"
                                    dir="{{ $dir }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $isRtl ? 'text-right' : '' }}">
                                <option value="lowest_cost">{{ __('campaigns.lowest_cost') }}</option>
                                <option value="cost_cap">{{ __('campaigns.cost_cap') }}</option>
                                <option value="bid_cap">{{ __('campaigns.bid_cap') }}</option>
                                <option value="target_cost">{{ __('campaigns.target_cost') }}</option>
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
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.end_date') }}</label>
                            <input type="datetime-local" name="end_date" x-model="form.end_date"
                                   dir="ltr"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <p class="text-xs text-gray-500 mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.wizard.end_date_help') }}</p>
                        </div>
                    </div>

                    <!-- Budget Summary -->
                    <div class="bg-gray-50 rounded-lg p-4" x-show="form.budget && form.start_date">
                        <h4 class="font-medium text-gray-900 mb-2 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.budget_summary') }}</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <p class="text-gray-500">{{ __('campaigns.estimated_reach') }}</p>
                                <p class="font-semibold text-lg" x-text="estimateReach()"></p>
                            </div>
                            <div class="{{ $isRtl ? 'text-right' : '' }}">
                                <p class="text-gray-500">{{ __('campaigns.duration') }}</p>
                                <p class="font-semibold text-lg" x-text="calculateDuration()"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 5: Review & Launch -->
        <div x-show="currentStep === 5" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-x-4" x-transition:enter-end="opacity-100 transform translate-x-0">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-{{ $isRtl ? 'l' : 'r' }} from-green-500 to-emerald-600">
                    <h2 class="text-xl font-semibold text-white flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                        <i class="fas fa-rocket {{ $isRtl ? 'ml-3' : 'mr-3' }}"></i>
                        {{ __('campaigns.wizard.review.almost_ready') }}
                    </h2>
                    <p class="text-green-100 text-sm mt-1 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.wizard.review.review_description') }}</p>
                </div>
                <div class="p-6">
                    <!-- Summary Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Platform & Objective -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-bullhorn {{ $isRtl ? 'ml-2' : 'mr-2' }} text-indigo-500"></i>
                                {{ __('campaigns.platform_objective') }}
                            </h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.platform') }}</dt>
                                    <dd class="font-medium capitalize" x-text="getPlatformName()"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.objective') }}</dt>
                                    <dd class="font-medium capitalize" x-text="form.objective?.replace('_', ' ') || '-'"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.ad_format') }}</dt>
                                    <dd class="font-medium capitalize" x-text="form.ad_format?.replace('_', ' ') || '-'"></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Campaign Details -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-info-circle {{ $isRtl ? 'ml-2' : 'mr-2' }} text-indigo-500"></i>
                                {{ __('campaigns.campaign_details') }}
                            </h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.name') }}</dt>
                                    <dd class="font-medium" x-text="form.name || '-'"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.call_to_action') }}</dt>
                                    <dd class="font-medium capitalize" x-text="form.cta?.replace('_', ' ') || '-'"></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Budget -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-wallet {{ $isRtl ? 'ml-2' : 'mr-2' }} text-indigo-500"></i>
                                {{ __('campaigns.budget') }}
                            </h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.budget_type') }}</dt>
                                    <dd class="font-medium capitalize" x-text="form.budget_type || 'Daily'"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.amount') }}</dt>
                                    <dd class="font-medium" x-text="'{{ $isRtl ? 'ر.س' : '$' }}' + (form.budget || 0)"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.bid_strategy') }}</dt>
                                    <dd class="font-medium capitalize" x-text="form.bid_strategy?.replace('_', ' ') || '{{ __('campaigns.lowest_cost') }}'"></dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Schedule -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-medium text-gray-700 mb-3 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <i class="fas fa-calendar {{ $isRtl ? 'ml-2' : 'mr-2' }} text-indigo-500"></i>
                                {{ __('campaigns.schedule') }}
                            </h4>
                            <dl class="space-y-2 text-sm">
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.start') }}</dt>
                                    <dd class="font-medium" x-text="formatDate(form.start_date)"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.end') }}</dt>
                                    <dd class="font-medium" x-text="form.end_date ? formatDate(form.end_date) : '{{ __('campaigns.ongoing') }}'"></dd>
                                </div>
                                <div class="flex justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                    <dt class="text-gray-500">{{ __('campaigns.duration') }}</dt>
                                    <dd class="font-medium" x-text="calculateDuration()"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Status Selection -->
                    <div class="mt-6 p-4 bg-indigo-50 rounded-lg">
                        <label class="block text-sm font-medium text-gray-700 mb-3 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.launch_status') }}</label>
                        <div class="flex gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                            <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <input type="radio" name="status" value="draft" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                                <span class="text-sm">{{ __('campaigns.save_draft') }}</span>
                            </label>
                            <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <input type="radio" name="status" value="active" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                                <span class="text-sm">{{ __('campaigns.launch_immediately') }}</span>
                            </label>
                            <label class="flex items-center cursor-pointer {{ $isRtl ? 'flex-row-reverse' : '' }}">
                                <input type="radio" name="status" value="scheduled" x-model="form.status" class="{{ $isRtl ? 'ml-2' : 'mr-2' }}">
                                <span class="text-sm">{{ __('campaigns.schedule_later') }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="mt-8 flex items-center justify-between {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <div>
                <button type="button" @click="previousStep" x-show="currentStep > 1"
                        class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-arrow-{{ $isRtl ? 'right' : 'left' }} {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('campaigns.previous') }}
                </button>
            </div>
            <div class="flex items-center gap-4 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}"
                   class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium">
                    {{ __('campaigns.cancel') }}
                </a>
                <button type="button" @click="nextStep" x-show="currentStep < 5"
                        class="px-8 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition shadow-sm flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    {{ __('campaigns.next') }}
                    <i class="fas fa-arrow-{{ $isRtl ? 'left' : 'right' }} {{ $isRtl ? 'mr-2' : 'ml-2' }}"></i>
                </button>
                <button type="submit" x-show="currentStep === 5"
                        class="px-8 py-3 bg-green-600 text-white rounded-lg font-medium hover:bg-green-700 transition shadow-sm flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-rocket {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i>
                    {{ __('campaigns.publish_campaign') }}
                </button>
            </div>
        </div>

        <!-- Hidden fields for form submission -->
        <input type="hidden" name="campaign_type" :value="form.objective">
    </form>
</div>
@endsection

@push('scripts')
<script>
function campaignWizard() {
    return {
        currentStep: 1,
        steps: [
            { title: '{{ __("campaigns.platform") }}' },
            { title: '{{ __("campaigns.objective") }}' },
            { title: '{{ __("campaigns.details") }}' },
            { title: '{{ __("campaigns.budget") }}' },
            { title: '{{ __("campaigns.preview") }}' }
        ],
        form: {
            platform: '',
            objective: '',
            name: '',
            description: '',
            ad_format: '',
            destination_url: '',
            cta: '',
            budget_type: 'daily',
            budget: '',
            bid_strategy: 'lowest_cost',
            start_date: '',
            end_date: '',
            status: 'draft'
        },
        errors: {},

        nextStep() {
            if (this.validateStep()) {
                this.currentStep++;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        },

        previousStep() {
            this.currentStep--;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        validateStep() {
            this.errors = {};

            switch(this.currentStep) {
                case 1:
                    if (!this.form.platform) {
                        this.errors.platform = '{{ __("validation.platform_required") }}';
                        return false;
                    }
                    break;
                case 2:
                    if (!this.form.objective) {
                        this.errors.objective = '{{ __("validation.objective_required") }}';
                        return false;
                    }
                    break;
                case 3:
                    if (!this.form.name || this.form.name.trim() === '') {
                        this.errors.name = '{{ __("validation.name_required") }}';
                        return false;
                    }
                    break;
                case 4:
                    if (!this.form.budget || parseFloat(this.form.budget) <= 0) {
                        this.errors.budget = '{{ __("validation.budget_required") }}';
                        return false;
                    }
                    if (!this.form.start_date) {
                        this.errors.start_date = '{{ __("validation.start_date_required") }}';
                        return false;
                    }
                    break;
            }
            return true;
        },

        submitForm(event) {
            if (!this.validateStep()) {
                event.preventDefault();
                return;
            }
            event.target.submit();
        },

        getAdFormats() {
            const formats = {
                meta: [
                    { value: 'image', label: '{{ __("campaigns.formats.single_image") }}', icon: 'fas fa-image' },
                    { value: 'video', label: '{{ __("campaigns.formats.video") }}', icon: 'fas fa-video' },
                    { value: 'carousel', label: '{{ __("campaigns.formats.carousel") }}', icon: 'fas fa-images' },
                    { value: 'stories', label: 'Stories', icon: 'fas fa-mobile-alt' }
                ],
                google: [
                    { value: 'search', label: 'Search', icon: 'fas fa-search' },
                    { value: 'display', label: 'Display', icon: 'fas fa-desktop' },
                    { value: 'video', label: '{{ __("campaigns.formats.video") }}', icon: 'fas fa-video' },
                    { value: 'shopping', label: 'Shopping', icon: 'fas fa-shopping-bag' }
                ],
                tiktok: [
                    { value: 'in_feed', label: 'In-Feed', icon: 'fas fa-stream' },
                    { value: 'topview', label: 'TopView', icon: 'fas fa-star' },
                    { value: 'spark', label: 'Spark Ads', icon: 'fas fa-bolt' },
                    { value: 'branded', label: 'Branded', icon: 'fas fa-hashtag' }
                ],
                snapchat: [
                    { value: 'snap_ad', label: 'Snap Ad', icon: 'fas fa-play' },
                    { value: 'story', label: 'Story Ad', icon: 'fas fa-book-open' },
                    { value: 'collection', label: '{{ __("campaigns.formats.collection") }}', icon: 'fas fa-th' },
                    { value: 'ar_lens', label: 'AR Lens', icon: 'fas fa-magic' }
                ],
                twitter: [
                    { value: 'promoted_tweet', label: 'Promoted', icon: 'fas fa-retweet' },
                    { value: 'follower', label: 'Follower', icon: 'fas fa-user-plus' },
                    { value: 'video', label: '{{ __("campaigns.formats.video") }}', icon: 'fas fa-video' },
                    { value: 'carousel', label: '{{ __("campaigns.formats.carousel") }}', icon: 'fas fa-images' }
                ],
                linkedin: [
                    { value: 'sponsored_content', label: 'Sponsored', icon: 'fas fa-newspaper' },
                    { value: 'message', label: 'Message', icon: 'fas fa-envelope' },
                    { value: 'text', label: 'Text Ad', icon: 'fas fa-font' },
                    { value: 'dynamic', label: 'Dynamic', icon: 'fas fa-magic' }
                ]
            };
            return formats[this.form.platform] || formats.meta;
        },

        getPlatformName() {
            const names = {
                meta: '{{ __("campaigns.platforms.meta") }}',
                google: '{{ __("campaigns.platforms.google") }}',
                tiktok: '{{ __("campaigns.platforms.tiktok") }}',
                snapchat: '{{ __("campaigns.platforms.snapchat") }}',
                twitter: '{{ __("campaigns.platforms.twitter") }}',
                linkedin: '{{ __("campaigns.platforms.linkedin") }}'
            };
            return names[this.form.platform] || this.form.platform;
        },

        estimateReach() {
            const budget = parseFloat(this.form.budget) || 0;
            const multiplier = this.form.budget_type === 'daily' ? 30 : 1;
            const reach = Math.floor((budget * multiplier) / 0.005);
            return reach.toLocaleString() + ' - ' + (reach * 1.5).toLocaleString();
        },

        calculateDuration() {
            if (!this.form.start_date) return '-';
            if (!this.form.end_date) return '{{ __("campaigns.ongoing") }}';

            const start = new Date(this.form.start_date);
            const end = new Date(this.form.end_date);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            return diff + ' {{ __("campaigns.days") }}';
        },

        formatDate(dateStr) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            return date.toLocaleDateString('{{ app()->getLocale() }}', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }
}
</script>
@endpush
