@extends('layouts.admin')

@section('page-title', __('social.social_management'))
@section('page-subtitle', __('social.schedule_publish_description'))

@section('content')
<div x-data="socialManager()">
    {{-- Quick Stats Dashboard --}}
    @include('social.components.stats-dashboard')

    {{-- Main Controls Panel with Filters --}}
    @include('social.components.controls-panel')

    {{-- Calendar View --}}
    @include('social.components.views.calendar-view')

    {{-- Posts Grid View --}}
    @include('social.components.views.grid-view')

    {{-- Posts List View --}}
    @include('social.components.views.list-view')

    {{-- Empty State --}}
    @include('social.components.empty-state')

    {{-- Edit Post Modal --}}
    @include('social.components.modals.edit-post-modal')
</div>
@endsection

@push('scripts')
<script>
@include('social.scripts.social-manager')
</script>
@endpush
