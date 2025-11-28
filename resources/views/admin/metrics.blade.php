@extends('layouts.admin')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">📊 {{ __('admin.knowledge_metrics_title') }}</h1>

    <div class="alert alert-info">
        {{ __('admin.semantic_trends_description') }}
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>{{ __('admin.intent') }}</th>
                <th>{{ __('admin.usage_count') }}</th>
                <th>{{ __('admin.average_quality') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($metrics as $m)
                <tr>
                    <td>{{ $m->intent }}</td>
                    <td>{{ $m->usage_count }}</td>
                    <td>{{ number_format($m->avg_score, 3) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center text-muted">{{ __('admin.no_data_available') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
