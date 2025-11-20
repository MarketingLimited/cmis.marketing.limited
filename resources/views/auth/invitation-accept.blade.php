@extends('layouts.guest')

@section('title', 'Accept Invitation')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                You're Invited!
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                Join <span class="font-semibold text-gray-900">{{ $invitation->org->name }}</span> on CMIS
            </p>
        </div>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <!-- Invitation Details -->
            <div class="mb-6">
                <div class="flex items-center justify-between py-3 border-b">
                    <span class="text-sm font-medium text-gray-500">Organization</span>
                    <span class="text-sm font-semibold text-gray-900">{{ $invitation->org->name }}</span>
                </div>
                <div class="flex items-center justify-between py-3 border-b">
                    <span class="text-sm font-medium text-gray-500">Your Role</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $invitation->role->role_name }}
                    </span>
                </div>
                <div class="flex items-center justify-between py-3">
                    <span class="text-sm font-medium text-gray-500">Email</span>
                    <span class="text-sm text-gray-900">{{ $invitation->user->email }}</span>
                </div>
            </div>

            <!-- Accept Form -->
            @if(!$invitation->user->password)
            <!-- New user - needs to set password -->
            <form method="POST" action="{{ route('invitations.accept', $token) }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">
                        Full Name
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        value="{{ old('name', $invitation->user->name) }}"
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Enter your full name"
                    >
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Choose a secure password"
                    >
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm Password
                    </label>
                    <input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                        placeholder="Confirm your password"
                    >
                </div>

                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Accept Invitation & Create Account
                    </button>
                </div>
            </form>
            @else
            <!-- Existing user - just accept -->
            <form method="POST" action="{{ route('invitations.accept', $token) }}">
                @csrf
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    Accept Invitation
                </button>
            </form>
            @endif

            <!-- Decline Link -->
            <div class="mt-4 text-center">
                <a
                    href="{{ route('invitations.decline', $token) }}"
                    class="text-sm font-medium text-gray-600 hover:text-gray-500"
                    onclick="return confirm('Are you sure you want to decline this invitation?')"
                >
                    Decline invitation
                </a>
            </div>
        </div>

        <!-- Security Note -->
        <div class="text-center">
            <p class="text-xs text-gray-500">
                This invitation was sent to {{ $invitation->user->email }}<br>
                Expires on {{ $invitation->invitation_expires_at->format('F j, Y') }}
            </p>
        </div>
    </div>
</div>
@endsection
