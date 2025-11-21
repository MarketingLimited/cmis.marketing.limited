@extends('layouts.analytics')

@section('title', 'KPI Dashboard - ' . $entityName)

@section('page-title', 'KPI Performance Dashboard')
@section('page-subtitle', $entityName . ' - Key performance indicators and health metrics')

@section('content')
<div class="space-y-6">
    <!-- KPI Dashboard Component -->
    <div x-data="kpiDashboard()"
         data-org-id="{{ $orgId }}"
         data-entity-type="{{ $entityType }}"
         data-entity-id="{{ $entityId }}"
         x-init="init()"
         x-html="render()"
         class="space-y-6">
        <!-- Component will render its own HTML via render() method -->
    </div>
</div>

@push('scripts')
<script type="module">
    console.log('KPI dashboard initialized for {{ $entityType }}: {{ $entityId }}');
</script>
@endpush
@endsection
