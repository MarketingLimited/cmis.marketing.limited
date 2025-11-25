@extends('layouts.admin')

@section('title', 'Edit Campaign')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="max-w-5xl mx-auto" x-data="campaignEditor()">
    <!-- Header -->
    <div class="mb-8">
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li>
                    <a href="{{ route('orgs.dashboard.index', ['org' => $currentOrg]) }}" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-home"></i>
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                    <a href="{{ route('orgs.campaigns.index', ['org' => $currentOrg]) }}" class="text-gray-500 hover:text-gray-700">
                        Campaigns
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                    <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}" class="text-gray-500 hover:text-gray-700">
                        {{ Str::limit($campaign->name, 20) }}
                    </a>
                </li>
                <li class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                    <span class="text-gray-700 font-medium">Edit</span>
                </li>
            </ol>
        </nav>
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Campaign</h1>
                <p class="mt-2 text-gray-600">Update your campaign settings and configuration</p>
            </div>
            <span class="px-3 py-1 text-sm font-medium rounded-full"
                  :class="{
                      'bg-green-100 text-green-800': '{{ $campaign->status }}' === 'active',
                      'bg-yellow-100 text-yellow-800': '{{ $campaign->status }}' === 'paused',
                      'bg-gray-100 text-gray-800': '{{ $campaign->status }}' === 'draft',
                      'bg-blue-100 text-blue-800': '{{ $campaign->status }}' === 'completed'
                  }">
                {{ ucfirst($campaign->status ?? 'draft') }}
            </span>
        </div>
    </div>

    <!-- Error Messages -->
    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex items-center mb-2">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <strong>Please fix the following errors:</strong>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('orgs.campaigns.update', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Platform & Objective (Read-only) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gray-100 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-700 flex items-center">
                    <i class="fas fa-lock mr-3 text-gray-400"></i>
                    Platform & Objective
                    <span class="ml-2 text-xs font-normal text-gray-500">(Cannot be changed after creation)</span>
                </h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Platform Display -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">Advertising Platform</label>
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <template x-if="form.platform === 'meta'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fab fa-meta text-xl text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Meta Ads</p>
                                        <p class="text-sm text-gray-500">Facebook & Instagram</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'google'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fab fa-google text-xl text-red-500"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Google Ads</p>
                                        <p class="text-sm text-gray-500">Search, Display & YouTube</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'tiktok'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fab fa-tiktok text-xl text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">TikTok Ads</p>
                                        <p class="text-sm text-gray-500">In-Feed & TopView</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'snapchat'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-yellow-400 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fab fa-snapchat-ghost text-xl text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Snapchat Ads</p>
                                        <p class="text-sm text-gray-500">Snap Ads & Filters</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'twitter'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-gray-900 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fab fa-x-twitter text-xl text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">X Ads</p>
                                        <p class="text-sm text-gray-500">Promoted Posts & Trends</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.platform === 'linkedin'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-700 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fab fa-linkedin-in text-xl text-white"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">LinkedIn Ads</p>
                                        <p class="text-sm text-gray-500">B2B & Professional</p>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <!-- Hidden input to preserve the value -->
                        <input type="hidden" name="platform" :value="form.platform">
                    </div>

                    <!-- Objective Display -->
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-2">Campaign Objective</label>
                        <div class="flex items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <template x-if="form.objective === 'awareness'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-eye text-xl text-purple-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Brand Awareness</p>
                                        <p class="text-sm text-gray-500">Increase brand recognition</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'traffic'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-mouse-pointer text-xl text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Traffic</p>
                                        <p class="text-sm text-gray-500">Drive website visitors</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'engagement'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-heart text-xl text-pink-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Engagement</p>
                                        <p class="text-sm text-gray-500">Likes, comments, shares</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'leads'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-user-plus text-xl text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Lead Generation</p>
                                        <p class="text-sm text-gray-500">Collect leads & contacts</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'conversions'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-shopping-cart text-xl text-orange-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Conversions</p>
                                        <p class="text-sm text-gray-500">Drive sales & sign-ups</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'app_installs'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-mobile-alt text-xl text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">App Installs</p>
                                        <p class="text-sm text-gray-500">Get app downloads</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'video_views'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-play text-xl text-red-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Video Views</p>
                                        <p class="text-sm text-gray-500">Maximize video engagement</p>
                                    </div>
                                </div>
                            </template>
                            <template x-if="form.objective === 'reach'">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-teal-100 rounded-lg flex items-center justify-center mr-3">
                                        <i class="fas fa-broadcast-tower text-xl text-teal-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Reach</p>
                                        <p class="text-sm text-gray-500">Maximum unique users</p>
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
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-edit mr-3"></i>
                    Campaign Details
                </h2>
            </div>
            <div class="p-6 space-y-6">
                <!-- Campaign Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Campaign Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" x-model="form.name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea name="description" id="description" rows="3" x-model="form.description"
                              placeholder="Describe your campaign goals and target audience..."
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
                </div>

                <!-- Ad Format -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ad Format</label>
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
                    <label for="destination_url" class="block text-sm font-medium text-gray-700 mb-2">
                        Destination URL
                    </label>
                    <input type="url" name="destination_url" id="destination_url" x-model="form.destination_url"
                           placeholder="https://yourwebsite.com/landing-page"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>

                <!-- Call to Action -->
                <div>
                    <label for="cta" class="block text-sm font-medium text-gray-700 mb-2">Call to Action</label>
                    <select name="cta" id="cta" x-model="form.cta"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select CTA...</option>
                        <option value="learn_more">Learn More</option>
                        <option value="shop_now">Shop Now</option>
                        <option value="sign_up">Sign Up</option>
                        <option value="contact_us">Contact Us</option>
                        <option value="download">Download</option>
                        <option value="get_offer">Get Offer</option>
                        <option value="book_now">Book Now</option>
                        <option value="watch_more">Watch More</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Budget & Schedule -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-wallet mr-3"></i>
                    Budget & Schedule
                </h2>
            </div>
            <div class="p-6 space-y-6">
                <!-- Budget Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Budget Type</label>
                    <div class="flex gap-4">
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="budget_type" value="daily" x-model="form.budget_type" class="sr-only">
                            <div class="p-4 border-2 rounded-lg text-center transition-all"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.budget_type === 'daily', 'border-gray-200': form.budget_type !== 'daily' }">
                                <i class="fas fa-calendar-day text-2xl mb-2" :class="{ 'text-indigo-600': form.budget_type === 'daily', 'text-gray-400': form.budget_type !== 'daily' }"></i>
                                <p class="font-medium">Daily Budget</p>
                            </div>
                        </label>
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="budget_type" value="lifetime" x-model="form.budget_type" class="sr-only">
                            <div class="p-4 border-2 rounded-lg text-center transition-all"
                                 :class="{ 'border-indigo-500 bg-indigo-50': form.budget_type === 'lifetime', 'border-gray-200': form.budget_type !== 'lifetime' }">
                                <i class="fas fa-calendar-alt text-2xl mb-2" :class="{ 'text-indigo-600': form.budget_type === 'lifetime', 'text-gray-400': form.budget_type !== 'lifetime' }"></i>
                                <p class="font-medium">Lifetime Budget</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Budget & Bid Strategy -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <span x-text="form.budget_type === 'daily' ? 'Daily Budget' : 'Total Budget'"></span>
                            <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500">$</span>
                            <input type="number" name="budget" x-model="form.budget" required min="1" step="0.01"
                                   class="w-full pl-8 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        @error('budget')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bid Strategy</label>
                        <select name="bid_strategy" x-model="form.bid_strategy"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="lowest_cost">Lowest Cost (Automatic)</option>
                            <option value="cost_cap">Cost Cap</option>
                            <option value="bid_cap">Bid Cap</option>
                            <option value="target_cost">Target Cost</option>
                        </select>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" name="start_date" x-model="form.start_date" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        @error('start_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="datetime-local" name="end_date" x-model="form.end_date"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Status & Actions -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-500 to-purple-600">
                <h2 class="text-lg font-semibold text-white flex items-center">
                    <i class="fas fa-cog mr-3"></i>
                    Status & Settings
                </h2>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="status" value="draft" x-model="form.status" class="mr-2">
                        <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm">Draft</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="status" value="active" x-model="form.status" class="mr-2">
                        <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm">Active</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="status" value="paused" x-model="form.status" class="mr-2">
                        <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm">Paused</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="status" value="completed" x-model="form.status" class="mr-2">
                        <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm">Completed</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between pt-4">
            <button type="button" onclick="confirmDelete()" class="px-6 py-3 border border-red-300 text-red-700 rounded-lg hover:bg-red-50 transition">
                <i class="fas fa-trash mr-2"></i>
                Delete Campaign
            </button>

            <div class="flex items-center gap-4">
                <a href="{{ route('orgs.campaigns.show', ['org' => $currentOrg, 'campaign' => $campaign->campaign_id]) }}"
                   class="px-6 py-3 text-gray-600 hover:text-gray-900 font-medium">
                    Cancel
                </a>
                <button type="submit" class="px-8 py-3 bg-indigo-600 text-white rounded-lg font-medium hover:bg-indigo-700 transition shadow-sm">
                    <i class="fas fa-save mr-2"></i>
                    Save Changes
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
    if (confirm('Are you sure you want to delete this campaign? This action cannot be undone.')) {
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
                    { value: 'image', label: 'Image', icon: 'fas fa-image' },
                    { value: 'video', label: 'Video', icon: 'fas fa-video' },
                    { value: 'carousel', label: 'Carousel', icon: 'fas fa-images' },
                    { value: 'stories', label: 'Stories', icon: 'fas fa-mobile-alt' }
                ],
                google: [
                    { value: 'search', label: 'Search', icon: 'fas fa-search' },
                    { value: 'display', label: 'Display', icon: 'fas fa-desktop' },
                    { value: 'video', label: 'Video', icon: 'fas fa-video' },
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
                    { value: 'collection', label: 'Collection', icon: 'fas fa-th' },
                    { value: 'ar_lens', label: 'AR Lens', icon: 'fas fa-magic' }
                ],
                twitter: [
                    { value: 'promoted_tweet', label: 'Promoted', icon: 'fas fa-retweet' },
                    { value: 'follower', label: 'Follower', icon: 'fas fa-user-plus' },
                    { value: 'video', label: 'Video', icon: 'fas fa-video' },
                    { value: 'carousel', label: 'Carousel', icon: 'fas fa-images' }
                ],
                linkedin: [
                    { value: 'sponsored_content', label: 'Sponsored', icon: 'fas fa-newspaper' },
                    { value: 'message', label: 'Message', icon: 'fas fa-envelope' },
                    { value: 'text', label: 'Text Ad', icon: 'fas fa-font' },
                    { value: 'dynamic', label: 'Dynamic', icon: 'fas fa-magic' }
                ]
            };
            return formats[this.form.platform] || formats.meta;
        }
    }
}
</script>
@endpush
