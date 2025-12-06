@extends('super-admin.layouts.app')

@section('title', __('super_admin.website.team_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">{{ __('super_admin.website.team_title') }}</h1>
            <p class="text-slate-600 dark:text-slate-400 mt-1">{{ __('super_admin.website.team_subtitle') }}</p>
        </div>
        <a href="{{ route('super-admin.website.team.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
            <i class="fas fa-plus"></i> {{ __('super_admin.website.create_team_member') }}
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @forelse($teamMembers ?? [] as $member)
            <div class="bg-white dark:bg-slate-800 rounded-xl p-6 border border-slate-200 dark:border-slate-700 text-center">
                @if($member->image_url)
                    <img src="{{ $member->image_url }}" alt="{{ $member->name }}" class="w-24 h-24 rounded-full object-cover mx-auto mb-4">
                @else
                    <div class="w-24 h-24 bg-slate-200 dark:bg-slate-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-user text-slate-400 text-3xl"></i>
                    </div>
                @endif
                <h3 class="font-semibold text-slate-900 dark:text-white">{{ $member->name }}</h3>
                <p class="text-sm text-red-600 dark:text-red-400">{{ $member->role }}</p>
                <p class="text-sm text-slate-500 mt-1">{{ $member->department }}</p>
                @if($member->social_links)
                    @php $socials = is_array($member->social_links) ? $member->social_links : json_decode($member->social_links, true); @endphp
                    <div class="flex items-center justify-center gap-2 mt-3">
                        @foreach($socials ?? [] as $platform => $url)
                            <a href="{{ $url }}" target="_blank" class="text-slate-400 hover:text-slate-600"><i class="fab fa-{{ $platform }}"></i></a>
                        @endforeach
                    </div>
                @endif
                <div class="flex items-center justify-center gap-2 mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $member->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                        {{ $member->is_active ? __('super_admin.website.active') : __('super_admin.website.inactive') }}
                    </span>
                    <a href="{{ route('super-admin.website.team.edit', $member->id) }}" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg"><i class="fas fa-edit"></i></a>
                    <form action="{{ route('super-admin.website.team.destroy', $member->id) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('super_admin.website.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="p-2 text-red-600 hover:bg-red-100 rounded-lg"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white dark:bg-slate-800 rounded-xl p-8 text-center border border-slate-200 dark:border-slate-700">
                <i class="fas fa-users text-4xl text-slate-400 mb-4"></i>
                <p class="text-slate-600 dark:text-slate-400">{{ __('super_admin.website.no_team_members') }}</p>
                <a href="{{ route('super-admin.website.team.create') }}" class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    <i class="fas fa-plus"></i> {{ __('super_admin.website.create_first_team_member') }}
                </a>
            </div>
        @endforelse
    </div>

    @if(($teamMembers ?? collect())->hasPages())
        <div class="mt-6">{{ $teamMembers->links() }}</div>
    @endif
</div>
@endsection
