@props(['rows' => 5, 'columns' => 4, 'class' => ''])

<div {{ $attributes->merge(['class' => 'bg-white shadow rounded-lg overflow-hidden animate-pulse ' . $class]) }}>
    <!-- Table header -->
    <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
        <div class="flex space-x-4">
            @for($i = 0; $i < $columns; $i++)
                <div class="h-4 bg-gray-200 rounded flex-1"></div>
            @endfor
        </div>
    </div>

    <!-- Table body -->
    <div class="divide-y divide-gray-200">
        @for($row = 0; $row < $rows; $row++)
            <div class="px-6 py-4">
                <div class="flex space-x-4 items-center">
                    @for($col = 0; $col < $columns; $col++)
                        @if($col === 0)
                            <div class="flex items-center flex-1">
                                <div class="h-10 w-10 bg-gray-200 rounded-full me-3"></div>
                                <div class="flex-1">
                                    <div class="h-4 bg-gray-200 rounded w-3/4 mb-1"></div>
                                    <div class="h-3 bg-gray-200 rounded w-1/2"></div>
                                </div>
                            </div>
                        @else
                            <div class="h-4 bg-gray-200 rounded flex-1"></div>
                        @endif
                    @endfor
                </div>
            </div>
        @endfor
    </div>
</div>
