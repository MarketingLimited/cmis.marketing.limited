@props(['items' => []])

<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-reverse space-x-2">
        <!-- Home Icon -->
        <li class="inline-flex items-center">
            <a href="{{ route('dashboard.index') }}" class="inline-flex items-center text-sm font-medium text-gray-500 hover:text-indigo-600 transition">
                <i class="fas fa-home ms-2"></i>
                {{ __('components.breadcrumb.home') }}
            </a>
        </li>

        <!-- Breadcrumb Items -->
        @foreach($items as $index => $item)
            <li>
                <div class="flex items-center">
                    <i class="fas fa-chevron-left text-gray-400 text-xs mx-2"></i>
                    @if($loop->last)
                        <span class="text-sm font-medium text-gray-700">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }} ms-1"></i>
                            @endif
                            {{ $item['label'] }}
                        </span>
                    @else
                        <a href="{{ $item['url'] }}" class="text-sm font-medium text-gray-500 hover:text-indigo-600 transition">
                            @if(isset($item['icon']))
                                <i class="{{ $item['icon'] }} ms-1"></i>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    @endif
                </div>
            </li>
        @endforeach
    </ol>
</nav>
