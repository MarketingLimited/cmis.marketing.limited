@extends('layouts.app')

@section('content')
<h2>قائمة الشركات</h2>
<ul>
@foreach($orgs as $org)
  <li>
    <a href="{{ route('orgs.show', $org->org_id) }}">{{ $org->name }}</a>
  </li>
@endforeach
</ul>
@endsection