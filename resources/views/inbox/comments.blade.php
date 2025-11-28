@extends('layouts.admin')

@section('title', __('inbox.unified_comments'))

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('content')
<div class="space-y-6" x-data="unifiedComments()">
    {{-- Page Header with Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition">
                <i class="fas fa-home"></i>
            </a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.inbox.index', ['org' => $currentOrg]) }}" class="hover:text-blue-600 transition">{{ __('inbox.inbox') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('inbox.comments') }}</span>
        </nav>
        <h1 class="text-2xl font-bold text-gray-900">{{ __('inbox.unified_comments') }}</h1>
        <p class="text-gray-600 mt-1">{{ __('inbox.manage_all_comments') }}</p>
    </div>

    {{-- Tabs --}}
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex gap-x-8" aria-label="Tabs">
                <a href="{{ route('orgs.inbox.index', ['org' => $currentOrg]) }}"
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    {{ __('inbox.all_messages') }}
                </a>
                <a href="{{ route('orgs.inbox.comments', ['org' => $currentOrg]) }}"
                   class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    {{ __('inbox.comments') }}
                </a>
                <a href="#"
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    {{ __('inbox.direct_messages') }}
                </a>
                <a href="#"
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    {{ __('inbox.mentions') }}
                </a>
            </nav>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                </div>
                <div class="ms-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('inbox.total_comments') }}</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.total"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ms-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('inbox.pending_replies') }}</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.pending"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ms-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('inbox.replied') }}</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.replied"></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ms-4">
                    <p class="text-sm font-medium text-gray-500">{{ __('inbox.negative_sentiment') }}</p>
                    <p class="text-2xl font-semibold text-gray-900" x-text="stats.negative"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inbox.platform') }}</label>
                <select id="platform"
                        x-model="filters.platform"
                        @change="loadComments()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('inbox.all_platforms') }}</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="twitter">Twitter</option>
                    <option value="linkedin">LinkedIn</option>
                    <option value="tiktok">TikTok</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label for="sentiment" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inbox.sentiment') }}</label>
                <select id="sentiment"
                        x-model="filters.sentiment"
                        @change="loadComments()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('inbox.all') }}</option>
                    <option value="positive">{{ __('inbox.positive') }}</option>
                    <option value="neutral">{{ __('inbox.neutral') }}</option>
                    <option value="negative">{{ __('inbox.negative') }}</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inbox.status') }}</label>
                <select id="status"
                        x-model="filters.is_replied"
                        @change="loadComments()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">{{ __('inbox.all') }}</option>
                    <option value="0">{{ __('inbox.not_replied') }}</option>
                    <option value="1">{{ __('inbox.replied') }}</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">{{ __('inbox.search') }}</label>
                <input type="text"
                       id="search"
                       x-model="filters.search"
                       @input="debounceSearch()"
                       placeholder="{{ __('inbox.search_comments_placeholder') }}"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    {{-- Comments List --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div x-show="loading" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">{{ __('inbox.loading_comments') }}</p>
        </div>

        <div x-show="!loading && comments.length === 0" class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('inbox.no_comments') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('inbox.no_comments_found') }}</p>
        </div>

        <div x-show="!loading && comments.length > 0">
            <ul class="divide-y divide-gray-200">
                <template x-for="comment in comments" :key="comment.id">
                    <li class="hover:bg-gray-50 transition duration-150">
                        <div class="px-6 py-4">
                            <div class="flex items-start gap-x-4">
                                {{-- Author Avatar --}}
                                <div class="flex-shrink-0">
                                    <img :src="comment.author_avatar || '/images/default-avatar.png'"
                                         :alt="comment.author_name"
                                         class="h-10 w-10 rounded-full">
                                </div>

                                {{-- Comment Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900" x-text="comment.author_name"></p>
                                            <p class="text-xs text-gray-500">
                                                <span x-text="comment.platform" class="capitalize"></span>
                                                <span class="mx-1">•</span>
                                                <span x-text="comment.time_ago"></span>
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-x-2">
                                            {{-- Sentiment Badge --}}
                                            <span :class="{
                                                'bg-green-100 text-green-800': comment.sentiment === 'positive',
                                                'bg-gray-100 text-gray-800': comment.sentiment === 'neutral',
                                                'bg-red-100 text-red-800': comment.sentiment === 'negative'
                                            }" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                                                <span x-text="comment.sentiment"></span>
                                            </span>
                                            {{-- Status Badge --}}
                                            <span x-show="comment.is_replied" class="text-xs text-green-600 font-medium">
                                                {{ __('inbox.replied') }}
                                            </span>
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-gray-700" x-text="comment.content"></p>

                                    {{-- Actions --}}
                                    <div class="mt-3 flex items-center gap-x-4">
                                        <button @click="showReplyForm(comment.id)"
                                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                            {{ __('inbox.reply') }}
                                        </button>
                                        <button @click="hideComment(comment.id)"
                                                x-show="!comment.is_hidden"
                                                class="text-sm text-gray-600 hover:text-gray-800">
                                            {{ __('inbox.hide') }}
                                        </button>
                                        <button @click="likeComment(comment.id)"
                                                class="text-sm text-gray-600 hover:text-gray-800">
                                            {{ __('inbox.like') }}
                                        </button>
                                        <button @click="deleteComment(comment.id)"
                                                class="text-sm text-red-600 hover:text-red-800">
                                            {{ __('inbox.delete') }}
                                        </button>
                                    </div>

                                    {{-- Reply Form --}}
                                    <div x-show="replyingTo === comment.id" class="mt-4">
                                        <textarea x-model="replyText"
                                                  rows="3"
                                                  placeholder="{{ __('inbox.write_reply_placeholder') }}"
                                                  class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                        <div class="mt-2 flex justify-end gap-x-2">
                                            <button @click="cancelReply()"
                                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                                {{ __('inbox.cancel') }}
                                            </button>
                                            <button @click="submitReply(comment.id)"
                                                    :disabled="!replyText.trim()"
                                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed">
                                                {{ __('inbox.send_reply') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                </template>
            </ul>

            {{-- Pagination --}}
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        {{ __('inbox.showing') }} <span class="font-medium" x-text="comments.length"></span> {{ __('inbox.comments') }}
                    </div>
                    <div class="flex gap-x-2">
                        <button @click="loadPreviousPage()"
                                :disabled="!pagination.has_previous"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('inbox.previous') }}
                        </button>
                        <button @click="loadNextPage()"
                                :disabled="!pagination.has_next"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            {{ __('inbox.next') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function unifiedComments() {
    return {
        loading: true,
        comments: [],
        filters: {
            platform: '',
            sentiment: '',
            is_replied: '',
            search: ''
        },
        pagination: {
            current_page: 1,
            has_next: false,
            has_previous: false
        },
        stats: {
            total: 0,
            pending: 0,
            replied: 0,
            negative: 0
        },
        searchTimeout: null,
        replyingTo: null,
        replyText: '',

        init() {
            this.loadComments();
            this.loadStatistics();
        },

        async loadComments() {
            this.loading = true;
            try {
                // Get active org from user or use first org
                const orgId = await this.getActiveOrgId();

                const params = new URLSearchParams({
                    ...this.filters,
                    page: this.pagination.current_page
                });

                // TODO: Replace with actual API endpoint when backend is ready
                // const response = await fetch(`/api/orgs/${orgId}/comments?${params}`);
                // const data = await response.json();

                // Mock data for now
                this.comments = [];
                this.loading = false;
            } catch (error) {
                console.error('Failed to load comments:', error);
                this.loading = false;
            }
        },

        async loadStatistics() {
            try {
                const orgId = await this.getActiveOrgId();

                // TODO: Replace with actual API endpoint
                // const response = await fetch(`/api/orgs/${orgId}/comments/statistics`);
                // const data = await response.json();

                // Mock data for now
                this.stats = {
                    total: 0,
                    pending: 0,
                    replied: 0,
                    negative: 0
                };
            } catch (error) {
                console.error('Failed to load statistics:', error);
            }
        },

        async getActiveOrgId() {
            // This would normally come from the user's session/preferences
            // For now, return a placeholder
            return 'active-org-id';
        },

        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadComments();
            }, 500);
        },

        showReplyForm(commentId) {
            this.replyingTo = commentId;
            this.replyText = '';
        },

        cancelReply() {
            this.replyingTo = null;
            this.replyText = '';
        },

        async submitReply(commentId) {
            if (!this.replyText.trim()) return;

            try {
                const orgId = await this.getActiveOrgId();

                const response = await fetch(`/api/orgs/${orgId}/comments/${commentId}/reply`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        reply_text: this.replyText
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.cancelReply();
                    this.loadComments();
                    alert('{{ __('inbox.reply_sent_success') }}');
                } else {
                    alert('{{ __('inbox.reply_failed') }}: ' + (data.error || '{{ __('inbox.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Failed to submit reply:', error);
                alert('{{ __('inbox.reply_error') }}');
            }
        },

        async hideComment(commentId) {
            if (!confirm('{{ __('inbox.confirm_hide_comment') }}')) return;

            try {
                const orgId = await this.getActiveOrgId();

                const response = await fetch(`/api/orgs/${orgId}/comments/${commentId}/hide`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.loadComments();
                    alert('{{ __('inbox.comment_hidden_success') }}');
                } else {
                    alert('{{ __('inbox.comment_hide_failed') }}: ' + (data.error || '{{ __('inbox.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Failed to hide comment:', error);
                alert('{{ __('inbox.comment_hide_error') }}');
            }
        },

        async likeComment(commentId) {
            try {
                const orgId = await this.getActiveOrgId();

                const response = await fetch(`/api/orgs/${orgId}/comments/${commentId}/like`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    alert('{{ __('inbox.comment_liked_success') }}');
                } else {
                    alert('{{ __('inbox.comment_like_failed') }}: ' + (data.error || '{{ __('inbox.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Failed to like comment:', error);
                alert('{{ __('inbox.comment_like_error') }}');
            }
        },

        async deleteComment(commentId) {
            if (!confirm('{{ __('inbox.confirm_delete_comment') }}')) return;

            try {
                const orgId = await this.getActiveOrgId();

                const response = await fetch(`/api/orgs/${orgId}/comments/${commentId}`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.loadComments();
                    alert('{{ __('inbox.comment_deleted_success') }}');
                } else {
                    alert('{{ __('inbox.comment_delete_failed') }}: ' + (data.error || '{{ __('inbox.unknown_error') }}'));
                }
            } catch (error) {
                console.error('Failed to delete comment:', error);
                alert('{{ __('inbox.comment_delete_error') }}');
            }
        },

        loadNextPage() {
            if (this.pagination.has_next) {
                this.pagination.current_page++;
                this.loadComments();
            }
        },

        loadPreviousPage() {
            if (this.pagination.has_previous) {
                this.pagination.current_page--;
                this.loadComments();
            }
        }
    }
}
</script>
@endpush
@endsection
