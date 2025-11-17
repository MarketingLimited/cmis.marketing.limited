@extends('layouts.admin')

@section('title', 'Campaigns')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Campaigns</h1>
        <a href="{{  route('campaigns.create')  }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
            <i class="fas fa-plus mr-2"></i>
            New Campaign
        </a>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
            {{  session('success')  }}
        </div>
    @endif

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow mb-6 p-4">
        <form method="GET" action="{{  route('campaigns.index')  }}" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" placeholder="Search campaigns..."
                       value="{{  request('search')  }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="min-w-[150px]">
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">All Statuses</option>
                    <option value="draft" {{  request('status') === 'draft' ? 'selected' : ''  }}>Draft</option>
                    <option value="active" {{  request('status') === 'active' ? 'selected' : ''  }}>Active</option>
                    <option value="paused" {{  request('status') === 'paused' ? 'selected' : ''  }}>Paused</option>
                    <option value="completed" {{  request('status') === 'completed' ? 'selected' : ''  }}>Completed</option>
                </select>
            </div>
            <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md">
                <i class="fas fa-filter mr-2"></i>Filter
            </button>
        </form>
    </div>

    <!-- Campaigns Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($campaigns ?? [] as $campaign)
            <div class="bg-white rounded-lg shadow hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">{{  $campaign->name  }}</h3>
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            @if($campaign->status === 'active') bg-green-100 text-green-800
                            @elseif($campaign->status === 'paused') bg-yellow-100 text-yellow-800
                            @elseif($campaign->status === 'draft') bg-gray-100 text-gray-800
                            @else bg-blue-100 text-blue-800
                            @endif">
                            {{  ucfirst($campaign->status ?? 'draft')  }}
                        </span>
                    </div>

                    <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                        {{  $campaign->description ?? 'No description provided.'  }}
                    </p>

                    <div class="grid grid-cols-2 gap-4 mb-4 text-sm">
                        <div>
                            <p class="text-gray-500">Budget</p>
                            <p class="font-semibold">${{  number_format($campaign->budget ?? 0, 2)  }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Spent</p>
                            <p class="font-semibold">${{  number_format($campaign->spend ?? 0, 2)  }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Impressions</p>
                            <p class="font-semibold">{{  number_format($campaign->impressions ?? 0)  }}</p>
                        </div>
                        <div>
                            <p class="text-gray-500">Clicks</p>
                            <p class="font-semibold">{{  number_format($campaign->clicks ?? 0)  }}</p>
                        </div>
                    </div>

                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <a href="{{  route('campaigns.show', $campaign->campaign_id ?? $campaign->id)  }}"
                           class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            View Details
                        </a>
                        <div class="flex space-x-2">
                            <a href="{{  route('campaigns.edit', $campaign->campaign_id ?? $campaign->id)  }}"
                               class="text-gray-600 hover:text-gray-900">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{  route('campaigns.destroy', $campaign->campaign_id ?? $campaign->id)  }}"
                                  onsubmit="return confirm('Are you sure you want to delete this campaign?');" class="inline">
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
                <i class="fas fa-bullhorn text-5xl text-gray-400 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">No campaigns yet</h3>
                <p class="text-gray-600 mb-4">Get started by creating your first campaign</p>
                <a href="{{  route('campaigns.create')  }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md">
                    <i class="fas fa-plus mr-2"></i>
                    Create Campaign
                </a>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if(isset($campaigns) && method_exists($campaigns, 'links'))
        <div class="mt-6">
            {{  $campaigns->links()  }}
        </div>
    @endif
</div>
@endsection
