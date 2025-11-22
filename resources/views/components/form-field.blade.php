@props([
    'name',
    'label',
    'type' => 'text',
    'required' => false,
    'maxlength' => null,
    'placeholder' => '',
    'helpText' => null,
    'showCharCount' => false
])

<div class="form-group">
    {{-- Label --}}
    <label :for="'{{ $name }}'" class="block text-sm font-medium text-gray-700 mb-1">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    {{-- Input Field --}}
    @if($type === 'textarea')
        <textarea
            id="{{ $name }}"
            name="{{ $name }}"
            x-model="formData.{{ $name }}"
            @blur="markTouched('{{ $name }}')"
            :class="getFieldClasses('{{ $name }}', 'w-full rounded-md shadow-sm p-2')"
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($required) required @endif
            placeholder="{{ $placeholder }}"
            rows="4"
        ></textarea>
    @elseif($type === 'date')
        <input
            type="date"
            id="{{ $name }}"
            name="{{ $name }}"
            x-model="formData.{{ $name }}"
            @blur="markTouched('{{ $name }}')"
            :class="getFieldClasses('{{ $name }}', 'w-full rounded-md shadow-sm p-2')"
            @if($required) required @endif
        />
    @else
        <input
            type="{{ $type }}"
            id="{{ $name }}"
            name="{{ $name }}"
            x-model="formData.{{ $name }}"
            @blur="markTouched('{{ $name }}')"
            :class="getFieldClasses('{{ $name }}', 'w-full rounded-md shadow-sm p-2')"
            @if($maxlength) maxlength="{{ $maxlength }}" @endif
            @if($required) required @endif
            placeholder="{{ $placeholder }}"
        />
    @endif

    {{-- Character Counter (Issue #6) --}}
    @if($showCharCount && $maxlength)
        <div class="flex items-center justify-between mt-1 text-xs">
            <span
                :class="getCounterClass('{{ $name }}')"
                x-text="getCharacterCountText('{{ $name }}')"
            ></span>
            <span x-show="isOverLimit('{{ $name }}')" class="text-red-600 font-semibold">
                Character limit exceeded!
            </span>
        </div>
    @endif

    {{-- Date Validation Error (Issue #5) --}}
    @if($type === 'date')
        <p x-show="dateErrors.{{ $name }}"
           class="mt-1 text-sm text-red-600"
           x-text="dateErrors.{{ $name }}">
        </p>
    @endif

    {{-- Validation Error (Issue #15) --}}
    <p x-show="shouldShowError('{{ $name }}')"
       class="mt-1 text-sm text-red-600 flex items-center gap-1"
       x-transition>
        <i class="fas fa-exclamation-circle"></i>
        <span x-text="getError('{{ $name }}')"></span>
    </p>

    {{-- Help Text --}}
    @if($helpText)
        <p class="mt-1 text-sm text-gray-500">{{ $helpText }}</p>
    @endif
</div>
