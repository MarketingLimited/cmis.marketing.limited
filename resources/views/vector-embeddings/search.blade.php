@extends('layouts.app')

@section('title', 'Semantic Search')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-12">
            <h1 class="h3 mb-4">🔍 البحث الدلالي</h1>

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

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">نموذج البحث</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('vector-embeddings.search.execute') }}">
                        @csrf
                        <div class="form-group">
                            <label for="query">استعلام البحث *</label>
                            <input type="text" class="form-control" id="query" name="query"
                                   value="{{ old('query', $query ?? '') }}" required>
                        </div>

                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="intent">النية</label>
                                    <input type="text" class="form-control" id="intent" name="intent"
                                           value="{{ old('intent') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="direction">الاتجاه</label>
                                    <input type="text" class="form-control" id="direction" name="direction"
                                           value="{{ old('direction') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="purpose">الغرض</label>
                                    <input type="text" class="form-control" id="purpose" name="purpose"
                                           value="{{ old('purpose') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="category">الفئة</label>
                                    <input type="text" class="form-control" id="category" name="category"
                                           value="{{ old('category') }}">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="limit">عدد النتائج</label>
                                    <input type="number" class="form-control" id="limit" name="limit"
                                           value="{{ old('limit', 10) }}" min="1" max="50">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="threshold">الحد الأدنى للتطابق</label>
                                    <input type="number" class="form-control" id="threshold" name="threshold"
                                           value="{{ old('threshold', 0.7) }}" min="0" max="1" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="search_type">نوع البحث</label>
                                    <select class="form-control" id="search_type" name="search_type">
                                        <option value="semantic" {{ old('search_type') == 'semantic' ? 'selected' : '' }}>دلالي</option>
                                        <option value="hybrid" {{ old('search_type') == 'hybrid' ? 'selected' : '' }}>هجين</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">بحث</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Results -->
    @if(isset($results) && count($results) > 0)
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">✅ النتائج ({{ count($results) }})</h5>
                </div>
                <div class="card-body">
                    @foreach($results as $index => $result)
                        <div class="mb-3 p-3 border rounded">
                            <h6 class="font-weight-bold">
                                [{{ $index + 1 }}] {{ $result->topic ?? 'N/A' }}
                                <span class="badge badge-secondary">{{ $result->domain ?? 'N/A' }}</span>
                            </h6>
                            @if(isset($result->similarity_score))
                                <p class="mb-1">
                                    <small class="text-muted">
                                        📊 Similarity: <strong>{{ number_format($result->similarity_score * 100, 2) }}%</strong>
                                    </small>
                                </p>
                            @endif
                            @if(isset($result->content))
                                <p class="mb-0">{{ Str::limit($result->content, 200) }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @elseif(isset($results))
        <div class="alert alert-warning">
            ⚠️ لم يتم العثور على نتائج
        </div>
    @endif
</div>
@endsection
