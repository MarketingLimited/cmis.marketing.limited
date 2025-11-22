@extends('layouts.app')

@section('title', 'Unified Inbox')

@section('content')
<div class="container mx-auto px-4 py-8" x-data="unifiedInbox()">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Unified Inbox</h1>
        <p class="text-gray-600 mt-2">Manage all your messages and comments from one place</p>
    </div>

    {{-- Tabs --}}
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <a href="{{ route('inbox.index') }}"
                   class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    All Messages
                </a>
                <a href="{{ route('inbox.comments') }}"
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Comments
                </a>
                <a href="#"
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Direct Messages
                </a>
                <a href="#"
                   class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                    Mentions
                </a>
            </nav>
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-64">
                <label for="platform" class="block text-sm font-medium text-gray-700 mb-1">Platform</label>
                <select id="platform"
                        x-model="filters.platform"
                        @change="loadMessages()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Platforms</option>
                    <option value="facebook">Facebook</option>
                    <option value="instagram">Instagram</option>
                    <option value="twitter">Twitter</option>
                    <option value="linkedin">LinkedIn</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select id="status"
                        x-model="filters.status"
                        @change="loadMessages()"
                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                    <option value="replied">Replied</option>
                </select>
            </div>
            <div class="flex-1 min-w-64">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text"
                       id="search"
                       x-model="filters.search"
                       @input="debounceSearch()"
                       placeholder="Search messages..."
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    {{-- Messages List --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div x-show="loading" class="p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <p class="mt-2 text-gray-600">Loading messages...</p>
        </div>

        <div x-show="!loading && messages.length === 0" class="p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No messages</h3>
            <p class="mt-1 text-sm text-gray-500">You're all caught up!</p>
        </div>

        <div x-show="!loading && messages.length > 0">
            <ul class="divide-y divide-gray-200">
                <template x-for="message in messages" :key="message.id">
                    <li class="hover:bg-gray-50 transition duration-150 cursor-pointer"
                        @click="selectMessage(message)"
                        :class="{ 'bg-blue-50': !message.is_read }">
                        <div class="px-6 py-4">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <img :src="message.author_avatar || '/images/default-avatar.png'"
                                                 :alt="message.author_name"
                                                 class="h-10 w-10 rounded-full">
                                        </div>
                                        <div class="ml-3 flex-1">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="message.author_name"></p>
                                                    <p class="text-xs text-gray-500">
                                                        <span x-text="message.platform" class="capitalize"></span>
                                                        <span class="mx-1">·</span>
                                                        <span x-text="message.time_ago"></span>
                                                    </p>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span x-show="!message.is_read"
                                                          class="inline-block h-2 w-2 rounded-full bg-blue-600"></span>
                                                    <span x-show="message.is_replied"
                                                          class="text-xs text-green-600 font-medium">Replied</span>
                                                </div>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-700" x-text="message.content.substring(0, 150) + (message.content.length > 150 ? '...' : '')"></p>
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
                        Showing <span class="font-medium" x-text="messages.length"></span> messages
                    </div>
                    <div class="flex space-x-2">
                        <button @click="loadPreviousPage()"
                                :disabled="!pagination.has_previous"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Previous
                        </button>
                        <button @click="loadNextPage()"
                                :disabled="!pagination.has_next"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed">
                            Next
                        </button>
                    </div>
                </div>
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
        filters: {
            platform: '',
            status: '',
            search: ''
        },
        pagination: {
            current_page: 1,
            has_next: false,
            has_previous: false
        },
        searchTimeout: null,

        init() {
            this.loadMessages();
        },

        async loadMessages() {
            this.loading = true;
            try {
                const params = new URLSearchParams({
                    ...this.filters,
                    page: this.pagination.current_page
                });

                // TODO: Replace with actual API endpoint
                // const response = await fetch(`/api/convenience/inbox?${params}`);
                // const data = await response.json();

                // Mock data for now
                this.messages = [];
                this.loading = false;
            } catch (error) {
                console.error('Failed to load messages:', error);
                this.loading = false;
            }
        },

        debounceSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadMessages();
            }, 500);
        },

        selectMessage(message) {
            // Navigate to message detail or open modal
            console.log('Selected message:', message);
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
        }
    }
}
</script>
@endpush
@endsection
