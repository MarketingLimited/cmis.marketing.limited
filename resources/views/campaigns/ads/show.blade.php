@extends('layouts.admin')

@section('title', $ad->name . ' - ' . __('campaigns.ad'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
    $isRtl = app()->getLocale() === 'ar';
    $dir = $isRtl ? 'rtl' : 'ltr';
@endphp

@section('content')
<div class="space-y-6" dir="{{ $dir }}">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2 {{ $isRtl ? 'flex-row-reverse' : '' }}">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('campaigns.campaigns') }}</a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.campaigns.ad-sets.show', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id]) }}" class="hover:text-blue-600 transition">{{ Str::limit($adSet->name, 15) }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ $ad->name }}</span>
        </nav>
    </div>

    {{-- Header --}}
    <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }} items-start mb-6">
        <div class="{{ $isRtl ? 'text-right' : '' }}">
            <h1 class="text-2xl font-bold text-gray-900 flex items-center {{ $isRtl ? 'flex-row-reverse' : '' }}">
                {{ $ad->name }}
                @php
                    $statusColors = ['draft' => 'bg-gray-100 text-gray-800', 'active' => 'bg-green-100 text-green-800', 'paused' => 'bg-yellow-100 text-yellow-800'];
                @endphp
                <span class="{{ $isRtl ? 'mr-3' : 'ml-3' }} px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$ad->status] ?? 'bg-gray-100 text-gray-800' }}">
                    {{ __('campaigns.status.' . $ad->status) }}
                </span>
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ __('campaigns.format') }}: {{ ucfirst($ad->ad_format ?? __('campaigns.unknown_format')) }}
                @if($ad->external_ad_id)
                    <span class="{{ $isRtl ? 'mr-2' : 'ml-2' }} text-green-600"><i class="fas fa-check-circle"></i> {{ __('campaigns.synced') }}</span>
                @endif
            </p>
        </div>
        <div class="flex {{ $isRtl ? 'flex-row-reverse space-x-reverse space-x-3' : 'space-x-3' }}">
            <a href="{{ route('org.campaigns.ad-sets.ads.preview', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
               class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-eye {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.preview') }}
            </a>
            <a href="{{ route('org.campaigns.ad-sets.ads.edit', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 {{ $isRtl ? 'flex-row-reverse' : '' }}">
                <i class="fas fa-edit {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.edit') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <p class="text-sm font-medium text-green-800 {{ $isRtl ? 'text-right' : '' }}">{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Content --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Creative Preview --}}
            <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.creative_preview') }}</h3>
                    <div class="bg-gray-100 rounded-lg p-4">
                        @if($ad->image_url || $ad->video_url)
                            <div class="max-w-md mx-auto">
                                @if($ad->video_url)
                                    <video controls class="w-full rounded-lg" poster="{{ $ad->thumbnail_url }}">
                                        <source src="{{ $ad->video_url }}" type="video/mp4">
                                    </video>
                                @else
                                    <img src="{{ $ad->image_url }}" alt="{{ $ad->name }}" class="w-full rounded-lg">
                                @endif
                                <div class="mt-4 p-4 bg-white rounded-lg shadow-sm {{ $isRtl ? 'text-right' : '' }}">
                                    @if($ad->headline)
                                        <h4 class="font-semibold text-gray-900">{{ $ad->headline }}</h4>
                                    @endif
                                    @if($ad->primary_text)
                                        <p class="mt-2 text-gray-600 text-sm">{{ $ad->primary_text }}</p>
                                    @endif
                                    @if($ad->call_to_action)
                                        <button class="mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded">
                                            {{ str_replace('_', ' ', $ad->call_to_action) }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-image text-4xl mb-2"></i>
                                <p>{{ __('campaigns.no_media_uploaded') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Ad Details --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.ad_details') }}</h3>
                    <dl class="grid grid-cols-1 gap-4">
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.primary_text_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->primary_text ?? __('campaigns.not_set') }}</dd>
                        </div>
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.headline_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->headline ?? __('campaigns.not_set') }}</dd>
                        </div>
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.description_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->description_text ?? __('campaigns.not_set') }}</dd>
                        </div>
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.call_to_action_label') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $ad->call_to_action ? str_replace('_', ' ', $ad->call_to_action) : __('campaigns.not_set') }}</dd>
                        </div>
                        <div class="{{ $isRtl ? 'text-right' : '' }}">
                            <dt class="text-sm font-medium text-gray-500">{{ __('campaigns.destination_url_label') }}</dt>
                            <dd class="mt-1 text-sm text-blue-600 break-all">
                                @if($ad->destination_url)
                                    <a href="{{ $ad->destination_url }}" target="_blank" dir="ltr">{{ $ad->destination_url }}</a>
                                @else
                                    <span class="text-gray-900">{{ __('campaigns.not_set') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Review Status --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.review_status_label') }}</h3>
                    @if($ad->review_status)
                        @php
                            $reviewColors = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                'in_review' => 'bg-blue-100 text-blue-800',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $reviewColors[$ad->review_status] ?? 'bg-gray-100 text-gray-800' }}">
                            {{ __('campaigns.review_status.' . $ad->review_status) }}
                        </span>
                        @if($ad->review_feedback)
                            <div class="mt-3 p-3 bg-gray-50 rounded-lg {{ $isRtl ? 'text-right' : '' }}">
                                <p class="text-sm text-gray-600">{{ $ad->review_feedback }}</p>
                            </div>
                        @endif
                    @else
                        <p class="text-gray-500 text-sm {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.not_submitted_for_review') }}</p>
                    @endif
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4 {{ $isRtl ? 'text-right' : '' }}">{{ __('campaigns.actions') }}</h3>
                    <div class="space-y-3">
                        <form action="{{ route('org.campaigns.ad-sets.ads.status', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}" method="POST">
                            @csrf @method('PATCH')
                            @if($ad->status === 'active')
                                <input type="hidden" name="status" value="paused">
                                <button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 flex items-center {{ $isRtl ? 'flex-row-reverse justify-center' : 'justify-center' }}">
                                    <i class="fas fa-pause {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.pause_ad') }}
                                </button>
                            @else
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="w-full px-4 py-2 border border-green-300 rounded-md text-sm text-green-700 bg-green-50 hover:bg-green-100 flex items-center {{ $isRtl ? 'flex-row-reverse justify-center' : 'justify-center' }}">
                                    <i class="fas fa-play {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.activate_ad') }}
                                </button>
                            @endif
                        </form>
                        <form action="{{ route('org.campaigns.ad-sets.ads.duplicate', [$currentOrg, $campaign->campaign_id, $adSet->ad_set_id, $ad->ad_id]) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50 flex items-center {{ $isRtl ? 'flex-row-reverse justify-center' : 'justify-center' }}">
                                <i class="fas fa-copy {{ $isRtl ? 'ml-2' : 'mr-2' }}"></i> {{ __('campaigns.duplicate') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Timestamps --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6 text-sm">
                    <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }}"><span class="text-gray-500">{{ __('campaigns.created') }}</span><span>{{ $ad->created_at->format('M d, Y') }}</span></div>
                    <div class="flex {{ $isRtl ? 'flex-row-reverse' : 'justify-between' }} mt-2"><span class="text-gray-500">{{ __('campaigns.updated') }}</span><span>{{ $ad->updated_at->diffForHumans() }}</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
