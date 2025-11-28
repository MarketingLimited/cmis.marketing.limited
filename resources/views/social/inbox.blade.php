@extends('layouts.admin')
@section('title', __('social.unified_inbox'))
@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">{{ __('social.unified_inbox') }}</h1>
    <div class="bg-white shadow rounded-lg p-6">
        <p>{{ __('social.messages_from_platforms') }}</p>
    </div>
</div>
@endsection
