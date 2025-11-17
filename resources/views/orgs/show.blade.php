@extends('layouts.admin')

@section('content')
<h2>{{ $org->name }}</h2>
<p><strong>العملة:</strong> {{ $org->currency }}</p>
<p><strong>اللغة:</strong> {{ $org->default_locale }}</p>

<h3>الروابط</h3>
<ul>
  <li><a href="{{ route('orgs.campaigns', $org->org_id) }}">الحملات</a></li>
  <li><a href="{{ route('orgs.services', $org->org_id) }}">الخدمات</a></li>
  <li><a href="{{ route('orgs.products', $org->org_id) }}">المنتجات</a></li>
</ul>
@endsection