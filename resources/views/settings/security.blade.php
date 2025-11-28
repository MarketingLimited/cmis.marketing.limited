@extends('layouts.admin')
@section('title', __('settings.security_settings'))
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">{{ __('settings.security_settings') }}</h1>
    <div class="bg-white shadow rounded-lg p-6"><form class="space-y-4"><div><label>{{ __('settings.current_password') }}</label><input type="password" class="mt-1 block w-full rounded-md border-gray-300"></div><div><label>{{ __('settings.new_password') }}</label><input type="password" class="mt-1 block w-full rounded-md border-gray-300"></div><div><button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md">{{ __('settings.update_password') }}</button></div></form></div>
</div>
@endsection
