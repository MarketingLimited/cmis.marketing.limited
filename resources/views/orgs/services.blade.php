@extends('layouts.app')

@section('content')
<h2>الخدمات</h2>
<ul>
@foreach($services as $s)
  <li>{{ $s->name }} — النوع: {{ $s->kind }}</li>
@endforeach
</ul>
@endsection