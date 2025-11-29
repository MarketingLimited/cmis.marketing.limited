@extends('layouts.admin')

@section('title', __('inbox.unified_inbox'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="h-[calc(100vh-120px)] flex flex-col" x-data="unifiedInbox()">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-4 flex-shrink-0">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('inbox.unified_inbox') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('inbox.manage_all_messages') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('orgs.settings.platform-connections.index', ['org' => $currentOrg]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                <i class="fas fa-plug"></i>
                <span class="hidden sm:inline">{{ __('inbox.connect_platforms') }}</span>
            </a>
            <button @click="refreshMessages()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                <i class="fas fa-sync-alt" :class="{ 'animate-spin': loading }"></i>
                <span class="hidden sm:inline">{{ __('common.refresh') }}</span>
            </button>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 flex-shrink-0">
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fas fa-envelope text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('inbox.total_messages') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="stats.total || 0"></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                    <i class="fas fa-envelope-open text-red-600 dark:text-red-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('inbox.unread') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="stats.unread || 0"></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-reply text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('inbox.replied') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="stats.replied || 0"></p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 p-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-link text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('inbox.platforms') }}</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white" x-text="connectedPlatforms.length || 0"></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content - Three Column Layout -->
    <div class="flex-1 flex flex-col lg:flex-row gap-4 min-h-0 overflow-hidden">
        <!-- Sidebar - Platform Tabs (Hidden on mobile when message selected) -->
        <div class="lg:w-16 flex-shrink-0" :class="{ 'hidden lg:block': selectedMessage }">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 h-full">
                <div class="flex lg:flex-col gap-1 p-2 overflow-x-auto lg:overflow-visible">
                    <button @click="filters.platform = ''; loadMessages()"
                            :class="filters.platform === '' ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700'"
                            class="flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center transition"
                            title="{{ __('inbox.all') }}">
                        <i class="fas fa-layer-group text-lg"></i>
                    </button>
                    <template x-for="platform in allPlatforms" :key="platform.key">
                        <button @click="filters.platform = platform.key; loadMessages()"
                                :class="[
                                    filters.platform === platform.key ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-slate-700',
                                    !connectedPlatforms.includes(platform.key) ? 'opacity-40' : ''
                                ]"
                                class="relative flex-shrink-0 w-12 h-12 rounded-lg flex items-center justify-center transition"
                                :title="platform.name">
                            <i :class="platform.icon" class="text-lg"></i>
                            <span x-show="connectedPlatforms.includes(platform.key)"
                                  class="absolute top-1 end-1 w-2 h-2 bg-green-500 rounded-full"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- Messages List -->
        <div class="lg:w-80 xl:w-96 flex-shrink-0 flex flex-col" :class="{ 'hidden lg:flex': selectedMessage }">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 flex-1 flex flex-col overflow-hidden">
                <!-- Search & Filter -->
                <div class="p-3 border-b border-gray-200 dark:border-slate-700">
                    <div class="relative">
                        <i class="fas fa-search absolute start-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="text"
                               x-model="filters.search"
                               @input.debounce.500ms="loadMessages()"
                               placeholder="{{ __('inbox.search_messages_placeholder') }}"
                               class="w-full ps-10 pe-4 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <select x-model="filters.status" @change="loadMessages()"
                                class="flex-1 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 py-1.5">
                            <option value="">{{ __('inbox.all_status') }}</option>
                            <option value="unread">{{ __('inbox.unread') }}</option>
                            <option value="read">{{ __('inbox.read') }}</option>
                            <option value="replied">{{ __('inbox.replied') }}</option>
                        </select>
                        <select x-model="filters.type" @change="loadMessages()"
                                class="flex-1 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 py-1.5">
                            <option value="">{{ __('inbox.all_types') }}</option>
                            <option value="comment">{{ __('inbox.comments') }}</option>
                            <option value="dm">{{ __('inbox.direct_messages') }}</option>
                            <option value="mention">{{ __('inbox.mentions') }}</option>
                        </select>
                    </div>
                </div>

                <!-- Messages -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Loading State -->
                    <div x-show="loading" class="flex items-center justify-center py-12">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Empty State -->
                    <div x-show="!loading && messages.length === 0" class="text-center py-12 px-4">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                            <i class="fas fa-inbox text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ __('inbox.no_messages') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('inbox.all_caught_up') }}</p>
                    </div>

                    <!-- Message Items -->
                    <div x-show="!loading && messages.length > 0">
                        <template x-for="message in messages" :key="message.id">
                            <div @click="selectMessage(message)"
                                 :class="[
                                     'px-4 py-3 border-b border-gray-100 dark:border-slate-700/50 cursor-pointer transition hover:bg-gray-50 dark:hover:bg-slate-700/50',
                                     !message.is_read ? 'bg-blue-50/50 dark:bg-blue-900/10' : '',
                                     selectedMessage?.id === message.id ? 'bg-blue-100 dark:bg-blue-900/30' : ''
                                 ]">
                                <div class="flex items-start gap-3">
                                    <div class="relative flex-shrink-0">
                                        <img :src="message.author_avatar || '/images/default-avatar.png'"
                                             :alt="message.author_name"
                                             class="w-10 h-10 rounded-full object-cover">
                                        <span :class="getPlatformColor(message.platform)"
                                              class="absolute -bottom-1 -end-1 w-5 h-5 rounded-full flex items-center justify-center text-white text-xs">
                                            <i :class="getPlatformIcon(message.platform)"></i>
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="message.author_name"></p>
                                            <span class="text-xs text-gray-500 dark:text-gray-400 flex-shrink-0" x-text="message.time_ago"></span>
                                        </div>
                                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2 mt-0.5" x-text="message.content"></p>
                                        <div class="flex items-center gap-2 mt-1.5">
                                            <span x-show="!message.is_read" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-900/50 text-blue-800 dark:text-blue-300">
                                                {{ __('inbox.new') }}
                                            </span>
                                            <span x-show="message.is_replied" class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300">
                                                <i class="fas fa-check me-1"></i> {{ __('inbox.replied') }}
                                            </span>
                                            <span class="text-xs text-gray-400 capitalize" x-text="message.type"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Pagination -->
                <div x-show="messages.length > 0" class="p-3 border-t border-gray-200 dark:border-slate-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('inbox.page') }} <span x-text="pagination.current_page"></span> / <span x-text="pagination.last_page"></span>
                    </span>
                    <div class="flex gap-1">
                        <button @click="loadPreviousPage()" :disabled="!pagination.has_previous"
                                class="px-2 py-1 text-xs border border-gray-300 dark:border-slate-600 rounded disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button @click="loadNextPage()" :disabled="!pagination.has_next"
                                class="px-2 py-1 text-xs border border-gray-300 dark:border-slate-600 rounded disabled:opacity-50 hover:bg-gray-50 dark:hover:bg-slate-700 transition">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Conversation Detail -->
        <div class="flex-1 flex flex-col min-w-0" :class="{ 'hidden lg:flex': !selectedMessage }">
            <div class="bg-white dark:bg-slate-800 rounded-lg shadow-sm border border-gray-200 dark:border-slate-700 flex-1 flex flex-col overflow-hidden">
                <!-- No Message Selected -->
                <div x-show="!selectedMessage" class="flex-1 flex items-center justify-center">
                    <div class="text-center px-4">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-slate-700 flex items-center justify-center">
                            <i class="fas fa-comments text-3xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1">{{ __('inbox.select_conversation') }}</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('inbox.select_conversation_description') }}</p>
                    </div>
                </div>

                <!-- Message Detail -->
                <template x-if="selectedMessage">
                    <div class="flex flex-col h-full">
                        <!-- Header -->
                        <div class="p-4 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <button @click="selectedMessage = null" class="lg:hidden p-2 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition">
                                    <i class="fas fa-arrow-left"></i>
                                </button>
                                <img :src="selectedMessage.author_avatar || '/images/default-avatar.png'"
                                     class="w-10 h-10 rounded-full object-cover">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="selectedMessage.author_name"></p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        <span x-text="selectedMessage.platform" class="capitalize"></span> &bull;
                                        <span x-text="selectedMessage.time_ago"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="markAsRead([selectedMessage.id])"
                                        x-show="!selectedMessage.is_read"
                                        class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                                        title="{{ __('inbox.mark_as_read') }}">
                                    <i class="fas fa-envelope-open"></i>
                                </button>
                                <button @click="toggleStar(selectedMessage)"
                                        class="p-2 text-gray-500 hover:text-yellow-500 hover:bg-yellow-50 dark:hover:bg-yellow-900/30 rounded-lg transition"
                                        :class="{ 'text-yellow-500': selectedMessage.is_starred }"
                                        title="{{ __('inbox.star') }}">
                                    <i :class="selectedMessage.is_starred ? 'fas fa-star' : 'far fa-star'"></i>
                                </button>
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div x-show="open" @click.away="open = false"
                                         class="absolute end-0 mt-2 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-700 py-1 z-10">
                                        <button class="w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                            <i class="fas fa-user-plus me-2"></i> {{ __('inbox.assign') }}
                                        </button>
                                        <button class="w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                            <i class="fas fa-sticky-note me-2"></i> {{ __('inbox.add_note') }}
                                        </button>
                                        <button class="w-full px-4 py-2 text-start text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-slate-700">
                                            <i class="fas fa-archive me-2"></i> {{ __('inbox.archive') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Conversation Thread -->
                        <div class="flex-1 overflow-y-auto p-4 space-y-4">
                            <!-- Original Message -->
                            <div class="flex gap-3">
                                <img :src="selectedMessage.author_avatar || '/images/default-avatar.png'"
                                     class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                <div class="flex-1">
                                    <div class="bg-gray-100 dark:bg-slate-700 rounded-lg p-3">
                                        <p class="text-sm text-gray-900 dark:text-white" x-text="selectedMessage.content"></p>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="selectedMessage.created_at"></p>
                                </div>
                            </div>

                            <!-- Replies -->
                            <template x-for="reply in conversationReplies" :key="reply.id">
                                <div class="flex gap-3" :class="reply.is_own ? 'flex-row-reverse' : ''">
                                    <img :src="reply.author_avatar || '/images/default-avatar.png'"
                                         class="w-8 h-8 rounded-full object-cover flex-shrink-0">
                                    <div class="flex-1" :class="reply.is_own ? 'text-end' : ''">
                                        <div :class="reply.is_own ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-700'"
                                             class="rounded-lg p-3 inline-block max-w-[80%]">
                                            <p class="text-sm" x-text="reply.content"></p>
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="reply.time_ago"></p>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Reply Box -->
                        <div class="p-4 border-t border-gray-200 dark:border-slate-700">
                            <div class="flex gap-2">
                                <div class="flex-1">
                                    <textarea x-model="replyText"
                                              rows="2"
                                              placeholder="{{ __('inbox.write_reply_placeholder') }}"
                                              class="w-full px-4 py-2 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <button @click="sendReply()"
                                            :disabled="!replyText.trim() || sendingReply"
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition">
                                        <i class="fas fa-paper-plane" :class="{ 'animate-pulse': sendingReply }"></i>
                                    </button>
                                    <button @click="showSavedReplies = !showSavedReplies"
                                            class="px-4 py-2 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700 transition"
                                            title="{{ __('inbox.saved_replies') }}">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function unifiedInbox() {
    return {
        loading: true,
        messages: [],
        selectedMessage: null,
        conversationReplies: [],
        replyText: '',
        sendingReply: false,
        showSavedReplies: false,
        stats: {
            total: 0,
            unread: 0,
            replied: 0
        },
        filters: {
            platform: '',
            status: '',
            type: '',
            search: ''
        },
        pagination: {
            current_page: 1,
            last_page: 1,
            has_next: false,
            has_previous: false
        },
        connectedPlatforms: [],
        allPlatforms: [
            { key: 'facebook', name: 'Facebook', icon: 'fab fa-facebook' },
            { key: 'instagram', name: 'Instagram', icon: 'fab fa-instagram' },
            { key: 'twitter', name: 'X (Twitter)', icon: 'fab fa-x-twitter' },
            { key: 'linkedin', name: 'LinkedIn', icon: 'fab fa-linkedin' },
            { key: 'tiktok', name: 'TikTok', icon: 'fab fa-tiktok' },
            { key: 'snapchat', name: 'Snapchat', icon: 'fab fa-snapchat' }
        ],

        init() {
            this.loadPlatformConnections();
            this.loadMessages();
            this.loadStats();
        },

        async loadPlatformConnections() {
            try {
                const response = await fetch(`/api/orgs/{{ $currentOrg }}/platform-connections`);
                if (response.ok) {
                    const data = await response.json();
                    this.connectedPlatforms = (data.connections || [])
                        .filter(c => c.is_active)
                        .map(c => c.platform);
                }
            } catch (e) {
                console.log('Could not load platform connections');
                // Set some default connected platforms for demo
                this.connectedPlatforms = ['facebook', 'instagram'];
            }
        },

        async loadMessages() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...Object.fromEntries(Object.entries(this.filters).filter(([_, v]) => v)),
                    page: this.pagination.current_page
                });

                const response = await fetch(`/api/orgs/{{ $currentOrg }}/inbox?${params}`, {
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const data = await response.json();
                    this.messages = data.messages || [];
                    this.pagination = data.pagination || this.pagination;
                } else {
                    // Demo data when API not available
                    this.loadDemoData();
                }
            } catch (e) {
                this.loadDemoData();
            }
            this.loading = false;
        },

        loadDemoData() {
            this.messages = [
                {
                    id: 1,
                    author_name: '{{ __("inbox.demo_user_1") }}',
                    author_avatar: 'https://ui-avatars.com/api/?name=Ahmed&background=random',
                    platform: 'facebook',
                    type: 'comment',
                    content: '{{ __("inbox.demo_message_1") }}',
                    time_ago: '{{ __("inbox.just_now") }}',
                    created_at: new Date().toLocaleString(),
                    is_read: false,
                    is_replied: false,
                    is_starred: false
                },
                {
                    id: 2,
                    author_name: '{{ __("inbox.demo_user_2") }}',
                    author_avatar: 'https://ui-avatars.com/api/?name=Sara&background=random',
                    platform: 'instagram',
                    type: 'dm',
                    content: '{{ __("inbox.demo_message_2") }}',
                    time_ago: '2 {{ __("inbox.hours_ago") }}',
                    created_at: new Date().toLocaleString(),
                    is_read: true,
                    is_replied: true,
                    is_starred: false
                },
                {
                    id: 3,
                    author_name: '{{ __("inbox.demo_user_3") }}',
                    author_avatar: 'https://ui-avatars.com/api/?name=Mohammed&background=random',
                    platform: 'twitter',
                    type: 'mention',
                    content: '{{ __("inbox.demo_message_3") }}',
                    time_ago: '5 {{ __("inbox.hours_ago") }}',
                    created_at: new Date().toLocaleString(),
                    is_read: false,
                    is_replied: false,
                    is_starred: true
                }
            ];
            this.stats = { total: 3, unread: 2, replied: 1 };
        },

        async loadStats() {
            try {
                const response = await fetch(`/api/orgs/{{ $currentOrg }}/inbox/statistics`);
                if (response.ok) {
                    const data = await response.json();
                    this.stats = data.statistics || this.stats;
                }
            } catch (e) {
                // Use demo stats
            }
        },

        selectMessage(message) {
            this.selectedMessage = message;
            if (!message.is_read) {
                this.markAsRead([message.id]);
            }
            this.loadConversation(message.id);
        },

        async loadConversation(messageId) {
            this.conversationReplies = [];
            // Load conversation thread
        },

        async sendReply() {
            if (!this.replyText.trim() || !this.selectedMessage) return;

            this.sendingReply = true;
            try {
                const response = await fetch(`/api/orgs/{{ $currentOrg }}/inbox/${this.selectedMessage.id}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ reply_text: this.replyText })
                });

                if (response.ok) {
                    this.conversationReplies.push({
                        id: Date.now(),
                        content: this.replyText,
                        is_own: true,
                        time_ago: '{{ __("inbox.just_now") }}',
                        author_avatar: '{{ auth()->user()->avatar ?? "" }}'
                    });
                    this.replyText = '';
                    this.selectedMessage.is_replied = true;
                }
            } catch (e) {
                console.error('Failed to send reply');
            }
            this.sendingReply = false;
        },

        async markAsRead(ids) {
            try {
                await fetch(`/api/orgs/{{ $currentOrg }}/inbox/mark-read`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ message_ids: ids })
                });
                this.messages = this.messages.map(m =>
                    ids.includes(m.id) ? { ...m, is_read: true } : m
                );
                if (this.stats.unread > 0) this.stats.unread--;
            } catch (e) {}
        },

        toggleStar(message) {
            message.is_starred = !message.is_starred;
        },

        refreshMessages() {
            this.loadMessages();
            this.loadStats();
        },

        loadNextPage() {
            if (this.pagination.has_next) {
                this.pagination.current_page++;
                this.loadMessages();
            }
        },

        loadPreviousPage() {
            if (this.pagination.has_previous) {
                this.pagination.current_page--;
                this.loadMessages();
            }
        },

        getPlatformIcon(platform) {
            const icons = {
                facebook: 'fab fa-facebook-f',
                instagram: 'fab fa-instagram',
                twitter: 'fab fa-x-twitter',
                linkedin: 'fab fa-linkedin-in',
                tiktok: 'fab fa-tiktok',
                snapchat: 'fab fa-snapchat-ghost'
            };
            return icons[platform] || 'fas fa-globe';
        },

        getPlatformColor(platform) {
            const colors = {
                facebook: 'bg-blue-600',
                instagram: 'bg-gradient-to-br from-purple-600 to-pink-500',
                twitter: 'bg-black',
                linkedin: 'bg-blue-700',
                tiktok: 'bg-black',
                snapchat: 'bg-yellow-400'
            };
            return colors[platform] || 'bg-gray-500';
        }
    }
}
</script>
@endpush
@endsection
