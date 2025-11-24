@extends('layouts.admin')

@section('content')
<h2>{{ __('organizations.products') }}</h2>
<ul>
@foreach($products as $p)
  <li>{{ $p->name }} — {{ __('offerings.type') }}: {{ $p->kind }}</li>
@endforeach
</ul>
@endsection