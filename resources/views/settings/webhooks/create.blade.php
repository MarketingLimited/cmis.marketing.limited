@extends('layouts.admin')

@section('title', __('webhooks.create_title') . ' - ' . __('Settings'))

@section('content')
<div class="max-w-3xl mx-auto" x-data="webhookForm()">
    {{-- Breadcrumb --}}
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.webhooks.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('webhooks.title') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('Create') }}</span>
        </nav>
    </div>

    <div class="mb-6">
        <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('webhooks.create_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('webhooks.subtitle') }}</p>
    </div>

    {{-- Error Messages --}}
    <template x-if="error">
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <i class="fas fa-exclamation-circle text-red-400 me-3"></i>
                <p class="text-sm font-medium text-red-800" x-text="error"></p>
            </div>
        </div>
    </template>

    <form @submit.prevent="submitForm" class="space-y-6">
        {{-- Basic Information --}}
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('webhooks.name') }}</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">{{ __('webhooks.name') }} *</label>
                    <input type="text" name="name" id="name" x-model="form.name" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           placeholder="{{ __('webhooks.name_placeholder') }}">
                    <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.name_help') }}</p>
                </div>

                <div>
                    <label for="callback_url" class="block text-sm font-medium text-gray-700">{{ __('webhooks.callback_url') }} *</label>
                    <input type="url" name="callback_url" id="callback_url" x-model="form.callback_url" required
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono text-sm"
                           placeholder="{{ __('webhooks.callback_url_placeholder') }}">
                    <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.callback_url_help') }}</p>
                </div>
            </div>
        </div>

        {{-- Event Subscription --}}
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('webhooks.subscribed_events') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('webhooks.subscribed_events_help') }}</p>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div>
                    <label for="platform" class="block text-sm font-medium text-gray-700">{{ __('webhooks.platform') }}</label>
                    <select name="platform" id="platform" x-model="form.platform"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">{{ __('webhooks.all_platforms') }}</option>
                        @foreach($platforms as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.platform_help') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('webhooks.subscribed_events') }}</label>
                    <div class="space-y-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" x-model="allEvents" @change="toggleAllEvents"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span class="ms-2 text-sm text-gray-700 font-medium">{{ __('webhooks.all_events') }}</span>
                        </label>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2" x-show="!allEvents">
                        @foreach($eventTypes as $value => $label)
                            <label class="inline-flex items-center">
                                <input type="checkbox" value="{{ $value }}" x-model="form.subscribed_events"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="ms-2 text-sm text-gray-600">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Advanced Settings --}}
        <div class="bg-white shadow-sm rounded-lg">
            <div class="px-6 py-5 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Advanced Settings') }}</h3>
            </div>
            <div class="px-6 py-5 space-y-5">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="timeout_seconds" class="block text-sm font-medium text-gray-700">{{ __('webhooks.timeout') }}</label>
                        <div class="mt-1 flex rounded-md shadow-sm">
                            <input type="number" name="timeout_seconds" id="timeout_seconds" x-model="form.timeout_seconds"
                                   min="5" max="60"
                                   class="block w-full rounded-s-md border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <span class="inline-flex items-center px-3 rounded-e-md border border-s-0 border-gray-300 bg-gray-50 text-gray-500 sm:text-sm">sec</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.timeout_help') }}</p>
                    </div>
                    <div>
                        <label for="max_retries" class="block text-sm font-medium text-gray-700">{{ __('webhooks.max_retries') }}</label>
                        <input type="number" name="max_retries" id="max_retries" x-model="form.max_retries"
                               min="0" max="10"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">{{ __('webhooks.max_retries_help') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Verification Instructions --}}
        <div class="bg-blue-50 rounded-lg border border-blue-200 p-6">
            <h4 class="text-sm font-medium text-blue-800 mb-3">
                <i class="fas fa-info-circle me-2"></i>{{ __('webhooks.verification_instructions') }}
            </h4>
            <div class="text-sm text-blue-700 space-y-2">
                <p>{{ __('webhooks.verification_step1') }}</p>
                <code class="block bg-blue-100 p-2 rounded text-xs font-mono">
                    GET {{ $yourCallbackUrl ?? 'your-callback-url' }}?hub_mode=subscribe&hub_verify_token=YOUR_TOKEN&hub_challenge=RANDOM_STRING
                </code>
                <p class="mt-3">{{ __('webhooks.verification_step2') }}</p>
                <ul class="list-disc list-inside ms-4 space-y-1">
                    <li>{{ __('webhooks.verification_step2a') }}</li>
                    <li>{{ __('webhooks.verification_step2b') }}</li>
                </ul>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3 pt-4">
            <a href="{{ route('orgs.settings.webhooks.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                {{ __('Cancel') }}
            </a>
            <button type="submit" :disabled="loading"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50">
                <span x-show="!loading">{{ __('webhooks.create') }}</span>
                <span x-show="loading"><i class="fas fa-spinner fa-spin me-1"></i>{{ __('Creating...') }}</span>
            </button>
        </div>
    </form>
</div>

<script>
function webhookForm() {
    return {
        loading: false,
        error: null,
        allEvents: true,
        form: {
            name: '',
            callback_url: '',
            platform: '',
            subscribed_events: [],
            timeout_seconds: 30,
            max_retries: 3
        },

        toggleAllEvents() {
            if (this.allEvents) {
                this.form.subscribed_events = [];
            }
        },

        async submitForm() {
            this.loading = true;
            this.error = null;

            try {
                const payload = {
                    ...this.form,
                    subscribed_events: this.allEvents ? null : this.form.subscribed_events
                };

                const response = await fetch('{{ route('orgs.settings.webhooks.store', $currentOrg) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to the webhook details page
                    window.location.href = '{{ route('orgs.settings.webhooks.index', $currentOrg) }}/' + data.data.webhook.id;
                } else {
                    this.error = data.message || 'Failed to create webhook';
                }
            } catch (error) {
                this.error = 'An unexpected error occurred';
                console.error(error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
