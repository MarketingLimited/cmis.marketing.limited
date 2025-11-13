@extends('layouts.guest')

@section('title', 'Verify Email')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100">
                <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                تأكيد البريد الإلكتروني
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                شكراً على التسجيل! قبل البدء، يرجى التحقق من بريدك الإلكتروني عبر النقر على الرابط المرسل.
            </p>
        </div>

        @if (session('status') == 'verification-link-sent')
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="mr-3">
                        <p class="text-sm font-medium text-green-800">
                            تم إرسال رابط تأكيد جديد إلى بريدك الإلكتروني
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <div class="space-y-4">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    إعادة إرسال رابط التأكيد
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    تسجيل الخروج
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
