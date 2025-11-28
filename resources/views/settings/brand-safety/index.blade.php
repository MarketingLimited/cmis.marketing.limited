@extends('layouts.admin')

@section('title', __('settings.brand_safety_policies') . ' - ' . __('Settings'))

@section('content')
<div class="space-y-6">
    <div class="mb-6">
        <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2">
            <a href="{{ route('orgs.dashboard.index', $currentOrg) }}" class="hover:text-blue-600 transition"><i class="fas fa-home"></i></a>
            <span class="text-gray-400">/</span>
            <a href="{{ route('orgs.settings.index', $currentOrg) }}" class="hover:text-blue-600 transition">{{ __('Settings') }}</a>
            <span class="text-gray-400">/</span>
            <span class="text-gray-900 font-medium">{{ __('settings.brand_safety') }}</span>
        </nav>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-900">{{ __('settings.brand_safety_policies') }}</h1>
            <p class="mt-1 text-xs sm:text-sm text-gray-500">
                {{ __('settings.define_content_guidelines') }}
            </p>
        </div>
        <a href="{{ route('orgs.settings.brand-safety.create', $currentOrg) }}"
           class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
            <i class="fas fa-plus me-2"></i>{{ __('settings.create_policy') }}
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-md bg-green-50 p-4">
            <div class="flex"><i class="fas fa-check-circle text-green-400 me-3"></i>
                <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($policies->count() > 0)
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('settings.policy') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('settings.risk_level') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('settings.usage') }}</th>
                        <th class="px-6 py-3 text-start text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('settings.status') }}</th>
                        <th class="px-6 py-3 text-end text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($policies as $policy)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
                                        <i class="fas fa-shield-alt text-red-600"></i>
                                    </div>
                                    <div class="ms-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $policy->name }}</div>
                                        <div class="text-xs text-gray-500">{{ Str::limit($policy->description, 40) }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full
                                    {{ $policy->risk_level === 'high' ? 'bg-red-100 text-red-700' :
                                       ($policy->risk_level === 'medium' ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700') }}">
                                    {{ ucfirst($policy->risk_level) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $policy->profile_groups_count ?? 0 }} {{ __('settings.groups') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs rounded-full {{ $policy->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $policy->is_active ? __('common.active') : __('common.inactive') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-end text-sm">
                                <a href="{{ route('orgs.settings.brand-safety.show', [$currentOrg, $policy->policy_id]) }}" class="text-gray-400 hover:text-blue-600 me-2"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('orgs.settings.brand-safety.edit', [$currentOrg, $policy->policy_id]) }}" class="text-gray-400 hover:text-blue-600 me-2"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('orgs.settings.brand-safety.destroy', [$currentOrg, $policy->policy_id]) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('settings.confirm_delete_policy_short') }}');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-gray-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="w-16 h-16 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-shield-alt text-red-400 text-2xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('settings.no_safety_policies_yet') }}</h3>
            <p class="text-sm text-gray-500 mb-6 max-w-md mx-auto">
                {{ __('settings.create_policies_description') }}
            </p>
            <a href="{{ route('orgs.settings.brand-safety.create', $currentOrg) }}"
               class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                <i class="fas fa-plus me-2"></i>{{ __('settings.create_first_policy') }}
            </a>
        </div>
    @endif
</div>
@endsection
