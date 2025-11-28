@extends('layouts.admin')

@section('title', __('embeddings.queue_manager'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">{{ __('embeddings.queue_title') }}</h1>

            @if(isset($error))
                <div class="alert alert-danger">
                    <strong>{{ __('common.error') }}:</strong> {{ $error }}
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Queue Statistics -->
    @if(isset($queueStats) && count($queueStats) > 0)
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ __('embeddings.queue_statistics') }}</h5>
                    <form method="POST" action="{{ route('vector-embeddings.queue.process') }}" class="d-inline">
                        @csrf
                        <input type="number" name="batch_size" value="50" min="1" max="500" class="form-control d-inline" style="width: 100px;" placeholder="{{ __('embeddings.batch_size') }}">
                        <button type="submit" class="btn btn-primary btn-sm">{{ __('embeddings.process_batch') }}</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('embeddings.status') }}</th>
                                    <th>{{ __('embeddings.count') }}</th>
                                    <th>{{ __('embeddings.avg_attempts') }}</th>
                                    <th>{{ __('embeddings.avg_wait_time') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($queueStats as $stat)
                                    <tr>
                                        <td>{{ $stat->{'الحالة'} ?? 'N/A' }}</td>
                                        <td>{{ number_format($stat->{'العدد'} ?? 0) }}</td>
                                        <td>{{ number_format($stat->{'متوسط المحاولات'} ?? 0, 2) }}</td>
                                        <td>{{ number_format($stat->{'متوسط وقت الانتظار (دقيقة)'} ?? 0, 2) }} {{ __('embeddings.minutes') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Queue Items -->
    @if(isset($queueItems) && count($queueItems) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('embeddings.queue_items_first_100') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>{{ __('embeddings.knowledge_id') }}</th>
                                    <th>{{ __('embeddings.table') }}</th>
                                    <th>{{ __('embeddings.field') }}</th>
                                    <th>{{ __('embeddings.status') }}</th>
                                    <th>{{ __('embeddings.priority') }}</th>
                                    <th>{{ __('embeddings.attempts') }}</th>
                                    <th>{{ __('embeddings.created_at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($queueItems as $item)
                                    <tr>
                                        <td>{{ $item->queue_id }}</td>
                                        <td>{{ $item->knowledge_id }}</td>
                                        <td>{{ $item->source_table }}</td>
                                        <td>{{ $item->source_field }}</td>
                                        <td>
                                            <span class="badge
                                                @if($item->status == 'completed') badge-success
                                                @elseif($item->status == 'failed') badge-danger
                                                @elseif($item->status == 'processing') badge-warning
                                                @else badge-secondary
                                                @endif">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td>{{ $item->priority }}</td>
                                        <td>{{ $item->retry_count }}</td>
                                        <td>{{ $item->created_at }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
        <div class="alert alert-info">
            {{ __('embeddings.queue_empty') }}
        </div>
    @endif
</div>
@endsection
