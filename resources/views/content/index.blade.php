@extends('layouts.admin')

@php
    $currentOrg = $currentOrg ?? request()->route('org') ?? auth()->user()->active_org_id ?? auth()->user()->org_id;
@endphp

@section('title', __('content.content_library'))

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">{{ __('content.content_library') }}</h1>
        <a href="{{  route('orgs.content.create', ['org' => $currentOrg])  }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
            <i class="fas fa-plus me-2"></i>
            {{ __('content.new_content') }}
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
            {{  session('success')  }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" action="{{  route('orgs.content.index', ['org' => $currentOrg])  }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="{{ __('content.search_placeholder') }}"
                       value="{{  request('search')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="min-w-[150px]">
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('content.all_types') }}</option>
                    <option value="post" {{  request('type') === 'post' ? 'selected' : ''  }}>{{ __('content.type_labels.post') }}</option>
                    <option value="ad" {{  request('type') === 'ad' ? 'selected' : ''  }}>{{ __('content.type_labels.ad') }}</option>
                    <option value="email" {{  request('type') === 'email' ? 'selected' : ''  }}>{{ __('content.type_labels.email') }}</option>
                    <option value="article" {{  request('type') === 'article' ? 'selected' : ''  }}>{{ __('content.type_labels.article') }}</option>
                    <option value="video" {{  request('type') === 'video' ? 'selected' : ''  }}>{{ __('content.type_labels.video') }}</option>
                </select>
            </div>
            <div class="min-w-[150px]">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">{{ __('content.all_statuses') }}</option>
                    <option value="draft" {{  request('status') === 'draft' ? 'selected' : ''  }}>{{ __('content.status.draft') }}</option>
                    <option value="scheduled" {{  request('status') === 'scheduled' ? 'selected' : ''  }}>{{ __('content.status.scheduled') }}</option>
                    <option value="published" {{  request('status') === 'published' ? 'selected' : ''  }}>{{ __('content.status.published') }}</option>
                    <option value="archived" {{  request('status') === 'archived' ? 'selected' : ''  }}>{{ __('content.status.archived') }}</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md">
                <i class="fas fa-filter me-2"></i>{{ __('common.filter') }}
            </button>
        </form>
    </div>

    <!-- Content Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($itemS ?? [] as $item)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                @if($item->thumbnail_url)
                    <div class="h-48 bg-gray-200 rounded-t-lg overflow-hidden">
                        <img src="{{  $item->thumbnail_url  }}" alt="{{  $item->title  }}"
                             class="w-full h-full object-cover">
                    </div>
                @endif
                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-1 text-xs font-semibold rounded bg-gray-100 text-gray-800">
                                {{  ucfirst($item->content_type ?? 'post')  }}
                            </span>
                            <span class="px-2 py-1 text-xs font-semibold rounded
                                @if($item->status === 'published') bg-green-100 text-green-800
                                @elseif($item->status === 'scheduled') bg-blue-100 text-blue-800
                                @elseif($item->status === 'draft') bg-gray-100 text-gray-800
                                @else bg-yellow-100 text-yellow-800
                                @endif">
                                {{  ucfirst($item->status ?? 'draft')  }}
                            </span>
                        </div>
                    </div>

                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{  $item->title  }}</h3>
                    <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                        {{  $item->body ?? $item->description ?? __('content.no_content_provided')  }}
                    </p>

                    @if($item->published_at)
                        <p class="text-xs text-gray-500 mb-4">
                            <i class="fas fa-calendar me-1"></i>
                            {{  $item->published_at->format('M d, Y h:i A')  }}
                        </p>
                    @elseif($item->scheduled_for)
                        <p class="text-xs text-gray-500 mb-4">
                            <i class="fas fa-clock me-1"></i>
                            {{ __('content.scheduled_for_date') }} {{  $item->scheduled_for->format('M d, Y h:i A')  }}
                        </p>
                    @endif

                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <a href="{{  route('orgs.content.show', ['org' => $currentOrg, 'content' => $item->content_id ?? $item->id])  }}"
                           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            {{ __('content.view_details') }}
                        </a>
                        <div class="flex gap-2">
                            <a href="{{  route('orgs.content.edit', ['org' => $currentOrg, 'content' => $item->content_id ?? $item->id])  }}"
                               class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{  route('orgs.content.destroy', ['org' => $currentOrg, 'content' => $item->content_id ?? $item->id])  }}"
                                  onsubmit="return confirm('{{ __('confirmations.delete_content') }}');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-lg shadow p-12 text-center">
                <i class="fas fa-file-alt text-5xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ __('content.no_content_yet') }}</h3>
                <p class="text-gray-600 mb-4">{{ __('content.start_creating') }}</p>
                <a href="{{  route('orgs.content.create', ['org' => $currentOrg])  }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                    <i class="fas fa-plus me-2"></i>
                    {{ __('content.create_content') }}
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($itemS) && method_exists($itemS, 'links'))
        <div class="mt-6">
            {{  $itemS->links()  }}
        </div>
    @endif
</div>
@endsection
