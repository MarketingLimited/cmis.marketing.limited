@extends('layouts.admin')

@section('content')
<h2>{{ __('organizations.services') }}</h2>
<ul>
@foreach($services as $s)
  <li>{{ $s->name }} — {{ __('offerings.type') }}: {{ $s->kind }}</li>
@endforeach
</ul>
@endsection