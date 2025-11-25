@extends('layouts.app')

@section('title', ($connection ? __('Edit') : __('Add')) . ' Meta Token - Settings')

@section('content')
<div class="max-w-3xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    {{-- Breadcrumb --}}
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="inline-flex items-center space-x-1 md:space-x-3 text-sm">
            <li>
                <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-cog mr-1"></i> Platform Connections
                </a>
            </li>
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                    <span class="text-gray-700 font-medium">{{ $connection ? 'Edit' : 'Add' }} Meta Token</span>
                </div>
            </li>
        </ol>
    </nav>

    {{-- Header --}}
    <div class="mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fab fa-facebook text-blue-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <h1 class="text-2xl font-bold text-gray-900">
                    {{ $connection ? 'Edit Meta Connection' : 'Add Meta System User Token' }}
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Connect your Meta Business account using a System User access token
                </p>
            </div>
        </div>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form --}}
    <form action="{{ $connection
            ? route('orgs.settings.platform-connections.meta.update', [$currentOrg, $connection->connection_id])
            : route('orgs.settings.platform-connections.meta.store', $currentOrg) }}"
          method="POST" class="space-y-6">
        @csrf
        @if($connection)
            @method('PUT')
        @endif

        {{-- Account Name --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Connection Details</h3>

                <div class="space-y-4">
                    <div>
                        <label for="account_name" class="block text-sm font-medium text-gray-700">
                            Connection Name *
                        </label>
                        <input type="text" name="account_name" id="account_name"
                               value="{{ old('account_name', $connection->account_name ?? '') }}"
                               placeholder="e.g., My Business Meta Account"
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">A friendly name to identify this connection</p>
                    </div>

                    @if($connection)
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Current Status</p>
                                    <p class="text-xs text-gray-500">
                                        @if($connection->status === 'active')
                                            <span class="text-green-600"><i class="fas fa-check-circle mr-1"></i>Active</span>
                                        @elseif($connection->status === 'error')
                                            <span class="text-red-600"><i class="fas fa-exclamation-circle mr-1"></i>Error</span>
                                        @else
                                            <span class="text-yellow-600"><i class="fas fa-clock mr-1"></i>{{ ucfirst($connection->status) }}</span>
                                        @endif
                                        @if($connection->last_sync_at)
                                            &bull; Last synced {{ $connection->last_sync_at->diffForHumans() }}
                                        @endif
                                    </p>
                                </div>
                                @if($connection->account_metadata['ad_accounts'] ?? null)
                                    <span class="text-sm text-gray-600">
                                        {{ count($connection->account_metadata['ad_accounts']) }} ad account(s)
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Access Token --}}
        <div class="bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Access Token
                    @if($connection)
                        <span class="text-sm font-normal text-gray-500">(leave empty to keep current token)</span>
                    @endif
                </h3>

                <div class="space-y-4">
                    <div>
                        <label for="access_token" class="block text-sm font-medium text-gray-700">
                            System User Access Token {{ $connection ? '' : '*' }}
                        </label>
                        <div class="mt-1 relative">
                            <textarea name="access_token" id="access_token" rows="4"
                                      placeholder="Paste your Meta System User access token here..."
                                      {{ $connection ? '' : 'required' }}
                                      class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm font-mono text-xs">{{ old('access_token') }}</textarea>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">
                            <i class="fas fa-lock mr-1"></i>
                            Your token will be encrypted before storage
                        </p>
                    </div>

                    @if($connection && $connection->token_expires_at)
                        <div class="p-3 bg-yellow-50 rounded-lg">
                            <p class="text-sm text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>
                                Current token expires {{ $connection->token_expires_at->diffForHumans() }}
                                ({{ $connection->token_expires_at->format('M d, Y H:i') }})
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Required Permissions Info --}}
        <div class="bg-blue-50 rounded-lg p-4">
            <h4 class="text-sm font-medium text-blue-900 mb-2">
                <i class="fas fa-key mr-1"></i> Required Permissions
            </h4>
            <p class="text-sm text-blue-800 mb-2">
                Make sure your System User token has the following permissions:
            </p>
            <div class="flex flex-wrap gap-2">
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">ads_management</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">ads_read</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">business_management</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">pages_read_engagement</span>
                <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">pages_show_list</span>
            </div>
            <p class="mt-3 text-xs text-blue-700">
                Optional for advanced features:
                <span class="px-1 bg-blue-100 rounded">instagram_basic</span>,
                <span class="px-1 bg-blue-100 rounded">instagram_manage_insights</span>,
                <span class="px-1 bg-blue-100 rounded">leads_retrieval</span>
            </p>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end space-x-3">
            <a href="{{ route('orgs.settings.platform-connections.index', $currentOrg) }}"
               class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Cancel
            </a>
            <button type="submit"
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fas fa-save mr-2"></i>
                {{ $connection ? 'Update Connection' : 'Save & Validate Token' }}
            </button>
        </div>
    </form>

    {{-- Help Section --}}
    <div class="mt-8 border-t border-gray-200 pt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">How to Generate a System User Token</h3>

        <div class="prose prose-sm text-gray-600">
            <ol class="space-y-3">
                <li>
                    <strong>Go to Meta Business Settings</strong>
                    <p class="text-gray-500">Navigate to <a href="https://business.facebook.com/settings" target="_blank" class="text-blue-600 hover:underline">business.facebook.com/settings</a></p>
                </li>
                <li>
                    <strong>Create or Select a System User</strong>
                    <p class="text-gray-500">In the left menu, go to Users &rarr; System Users. Create a new one or select existing.</p>
                </li>
                <li>
                    <strong>Assign Assets</strong>
                    <p class="text-gray-500">Click "Add Assets" and assign your Ad Accounts with appropriate permissions (typically "Manage campaigns").</p>
                </li>
                <li>
                    <strong>Generate Token</strong>
                    <p class="text-gray-500">Click "Generate New Token", select your app, choose the required permissions, and set expiration to "Never" for long-lived tokens.</p>
                </li>
                <li>
                    <strong>Copy the Token</strong>
                    <p class="text-gray-500">Copy the generated token immediately (it won't be shown again) and paste it here.</p>
                </li>
            </ol>
        </div>

        <div class="mt-4 p-4 bg-yellow-50 rounded-lg">
            <p class="text-sm text-yellow-800">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>Security Note:</strong> Never share your access token with anyone. System User tokens provide full access to your ad accounts.
            </p>
        </div>
    </div>
</div>
@endsection
