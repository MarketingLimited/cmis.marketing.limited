@props(['headers' => [], 'striped' => true, 'hoverable' => true])

<div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm bg-white">
    <table class="min-w-full divide-y divide-gray-200">
        <!-- Table Header -->
        <thead class="bg-gradient-to-r from-gray-50 to-gray-100">
            <tr>
                @foreach($headers as $header)
                    <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">
                        @if(is_array($header))
                            <div class="flex items-center gap-2">
                                @if(isset($header['icon']))
                                    <i class="{{ $header['icon'] }} text-gray-500"></i>
                                @endif
                                {{ $header['label'] }}
                                @if(isset($header['sortable']) && $header['sortable'])
                                    <i class="fas fa-sort text-gray-400 text-xs cursor-pointer hover:text-gray-600"></i>
                                @endif
                            </div>
                        @else
                            {{ $header }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>

        <!-- Table Body -->
        <tbody class="divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>

@push('styles')
<style>
    @if($striped)
        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }
    @endif

    @if($hoverable)
        tbody tr:hover {
            background-color: #f3f4f6;
            transition: background-color 0.2s ease;
        }
    @endif
</style>
@endpush
