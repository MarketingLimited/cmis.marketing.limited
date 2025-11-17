@extends('layouts.admin')

@section('title', 'Queue Manager')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">⏳ إدارة قائمة الانتظار</h1>

            @if(isset($error))
                <div class="alert alert-danger">
                    <strong>خطأ:</strong> {{ $error }}
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
                    <h5 class="mb-0">📊 إحصائيات القائمة</h5>
                    <form method="POST" action="{{ route('vector-embeddings.queue.process') }}" class="d-inline">
                        @csrf
                        <input type="number" name="batch_size" value="50" min="1" max="500" class="form-control d-inline" style="width: 100px;">
                        <button type="submit" class="btn btn-primary btn-sm">معالجة الدفعة</button>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>الحالة</th>
                                    <th>العدد</th>
                                    <th>متوسط المحاولات</th>
                                    <th>متوسط الانتظار</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($queueStats as $stat)
                                    <tr>
                                        <td>{{ $stat->{'الحالة'} ?? 'N/A' }}</td>
                                        <td>{{ number_format($stat->{'العدد'} ?? 0) }}</td>
                                        <td>{{ number_format($stat->{'متوسط المحاولات'} ?? 0, 2) }}</td>
                                        <td>{{ number_format($stat->{'متوسط وقت الانتظار (دقيقة)'} ?? 0, 2) }} دقيقة</td>
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
                    <h5 class="mb-0">📋 عناصر القائمة (أول 100)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-sm">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Knowledge ID</th>
                                    <th>الجدول</th>
                                    <th>الحقل</th>
                                    <th>الحالة</th>
                                    <th>الأولوية</th>
                                    <th>المحاولات</th>
                                    <th>تاريخ الإنشاء</th>
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
            ✅ قائمة الانتظار فارغة
        </div>
    @endif
</div>
@endsection
