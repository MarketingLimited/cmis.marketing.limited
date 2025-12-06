@extends('super-admin.layouts.app')

@section('title', __('super_admin.settings.title'))
@section('page-title', __('super_admin.settings.title'))

@section('content')
<div class="space-y-6">
    {{-- Header with Actions --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ __('super_admin.settings.title') }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.settings.subtitle') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('super-admin.settings.export') }}"
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                {{ __('super_admin.settings.export') }}
            </a>
            <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                {{ __('super_admin.settings.import') }}
            </button>
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <p class="ms-3 text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 rounded-lg p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <p class="ms-3 text-sm font-medium text-red-800 dark:text-red-200">{!! session('error') !!}</p>
            </div>
        </div>
    @endif

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Group Navigation --}}
        <div class="lg:w-64 flex-shrink-0">
            <nav class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        {{ __('super_admin.settings.groups') }}
                    </h3>
                </div>
                <div class="py-2">
                    @foreach($groups as $group)
                        <a href="{{ route('super-admin.settings.index', ['group' => $group]) }}"
                           class="flex items-center px-4 py-3 text-sm font-medium {{ $currentGroup === $group ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 border-s-4 border-indigo-600' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700' }}">
                            @switch($group)
                                @case('general')
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    </svg>
                                    @break
                                @case('registration')
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                    </svg>
                                    @break
                                @case('security')
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    @break
                                @case('api')
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                    @break
                                @case('email')
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    @break
                                @default
                                    <svg class="w-5 h-5 me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                    </svg>
                            @endswitch
                            {{ __('super_admin.settings.group_' . $group) }}
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>

        {{-- Settings Form --}}
        <div class="flex-1">
            <form action="{{ route('super-admin.settings.update') }}" method="POST" class="bg-white dark:bg-gray-800 shadow rounded-lg">
                @csrf
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('super_admin.settings.group_' . $currentGroup) }}
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('super_admin.settings.group_' . $currentGroup . '_desc') }}
                    </p>
                </div>

                <div class="px-6 py-4 space-y-6">
                    @forelse($settings as $setting)
                        <div class="flex flex-col sm:flex-row sm:items-start gap-4">
                            <div class="sm:w-1/3">
                                <label for="setting_{{ $setting->key }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $setting->label }}
                                </label>
                                @if($setting->description)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                                @endif
                            </div>
                            <div class="sm:w-2/3">
                                <div class="flex items-center gap-2">
                                    @switch($setting->type)
                                        @case('boolean')
                                            <div class="relative inline-flex items-center">
                                                <input type="hidden" name="settings[{{ $setting->key }}]" value="false">
                                                <input type="checkbox"
                                                       name="settings[{{ $setting->key }}]"
                                                       id="setting_{{ $setting->key }}"
                                                       value="true"
                                                       {{ $setting->value === 'true' ? 'checked' : '' }}
                                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ $setting->value === 'true' ? __('super_admin.settings.enabled') : __('super_admin.settings.disabled') }}
                                                </span>
                                            </div>
                                            @break
                                        @case('integer')
                                            <input type="number"
                                                   name="settings[{{ $setting->key }}]"
                                                   id="setting_{{ $setting->key }}"
                                                   value="{{ $setting->value }}"
                                                   class="block w-full sm:w-32 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            @break
                                        @case('text')
                                            <textarea name="settings[{{ $setting->key }}]"
                                                      id="setting_{{ $setting->key }}"
                                                      rows="3"
                                                      class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ $setting->value }}</textarea>
                                            @break
                                        @case('json')
                                            <textarea name="settings[{{ $setting->key }}]"
                                                      id="setting_{{ $setting->key }}"
                                                      rows="5"
                                                      class="block w-full font-mono text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ $setting->value }}</textarea>
                                            @break
                                        @default
                                            @if($setting->options)
                                                @if($setting->key === 'default_plan_id')
                                                    <select name="settings[{{ $setting->key }}]"
                                                            id="setting_{{ $setting->key }}"
                                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        <option value="">{{ __('super_admin.settings.select_plan') }}</option>
                                                        @foreach($plans as $plan)
                                                            <option value="{{ $plan->plan_id }}" {{ $setting->value === $plan->plan_id ? 'selected' : '' }}>
                                                                {{ $plan->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @else
                                                    <select name="settings[{{ $setting->key }}]"
                                                            id="setting_{{ $setting->key }}"
                                                            class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                                        @foreach($setting->options as $optValue => $optLabel)
                                                            <option value="{{ $optValue }}" {{ $setting->value === $optValue ? 'selected' : '' }}>
                                                                {{ $optLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            @else
                                                <input type="text"
                                                       name="settings[{{ $setting->key }}]"
                                                       id="setting_{{ $setting->key }}"
                                                       value="{{ $setting->value }}"
                                                       class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            @endif
                                    @endswitch

                                    {{-- Reset button --}}
                                    <form action="{{ route('super-admin.settings.reset', $setting->key) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                                title="{{ __('super_admin.settings.reset_to_default') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>

                                @if($setting->is_public)
                                    <span class="inline-flex items-center mt-2 px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                        <svg class="w-3 h-3 me-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                        </svg>
                                        {{ __('super_admin.settings.public') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ __('super_admin.settings.no_settings') }}</p>
                        </div>
                    @endforelse
                </div>

                @if($settings->count() > 0)
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-gray-700 flex justify-end">
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('super_admin.settings.save_changes') }}
                        </button>
                    </div>
                @endif
            </form>
        </div>
    </div>
</div>

{{-- Import Modal --}}
<div id="import-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 transition-opacity" onclick="document.getElementById('import-modal').classList.add('hidden')"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-start overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form action="{{ route('super-admin.settings.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/30 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ms-4 sm:text-start w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                {{ __('super_admin.settings.import_title') }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('super_admin.settings.import_desc') }}
                                </p>
                            </div>
                            <div class="mt-4">
                                <label class="block">
                                    <input type="file" name="file" accept=".json" required
                                           class="block w-full text-sm text-gray-500 dark:text-gray-400 file:me-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-900/30 dark:file:text-indigo-400 hover:file:bg-indigo-100">
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700/50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse gap-3">
                    <button type="submit"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:w-auto sm:text-sm">
                        {{ __('super_admin.settings.import_button') }}
                    </button>
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                        {{ __('super_admin.common.cancel') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
