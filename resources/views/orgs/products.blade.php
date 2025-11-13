@extends('layouts.app')

@section('content')
<h2>المنتجات</h2>
<ul>
@foreach($products as $p)
  <li>{{ $p->name }} — النوع: {{ $p->kind }}</li>
@endforeach
</ul>
@endsection