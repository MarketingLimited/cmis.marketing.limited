@extends('layouts.guest')

@section('title', 'Invalid Invitation')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Invalid Invitation
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ $message }}
            </p>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6 text-center">
            <p class="text-gray-700 mb-6">
                This invitation link may have expired or been used already. Please contact your organization administrator for a new invitation.
            </p>

            <a
                href="{{ route('login') }}"
                class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
                Go to Login
            </a>
        </div>

        <div class="text-center">
            <p class="text-xs text-gray-500">
                Need help? Contact support or your organization administrator.
            </p>
        </div>
    </div>
</div>
@endsection
