{{-- Step 1: Campaign Basics --}}
<div class="space-y-6">
    {{-- Campaign Name --}}
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.name') }} <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" id="name"
               value="{{ old('name', $session['data']['name'] ?? '') }}"
               required
               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
               placeholder="{{ __('campaigns.name_placeholder') }}">
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.name_help') }}</p>
    </div>

    {{-- Campaign Objective --}}
    <div>
        <label for="objective" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.objective') }} <span class="text-red-500">*</span>
        </label>
        <select name="objective" id="objective" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">{{ __('common.select') }}</option>
            @foreach($step_data['objectives'] as $key => $label)
                <option value="{{ $key }}"
                        {{ old('objective', $session['data']['objective'] ?? '') == $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.objective_help') }}</p>
    </div>

    {{-- Budget --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="budget_total" class="block text-sm font-medium text-gray-700">
                {{ __('campaigns.budget_total') }} <span class="text-red-500">*</span>
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input type="number" name="budget_total" id="budget_total"
                       value="{{ old('budget_total', $session['data']['budget_total'] ?? '') }}"
                       min="10" step="0.01" required
                       class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="1000.00">
            </div>
            <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.budget_help') }}</p>
        </div>

        <div>
            <label for="budget_daily" class="block text-sm font-medium text-gray-700">
                {{ __('campaigns.budget_daily') }}
            </label>
            <div class="mt-1 relative rounded-md shadow-sm">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500 sm:text-sm">$</span>
                </div>
                <input type="number" name="budget_daily" id="budget_daily"
                       value="{{ old('budget_daily', $session['data']['budget_daily'] ?? '') }}"
                       min="1" step="0.01"
                       class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       placeholder="50.00">
            </div>
        </div>
    </div>

    {{-- Campaign Dates --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">
                {{ __('campaigns.start_date') }} <span class="text-red-500">*</span>
            </label>
            <input type="date" name="start_date" id="start_date"
                   value="{{ old('start_date', $session['data']['start_date'] ?? now()->toDateString()) }}"
                   min="{{ now()->toDateString() }}"
                   required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div>
            <label for="end_date" class="block text-sm font-medium text-gray-700">
                {{ __('campaigns.end_date') }}
            </label>
            <input type="date" name="end_date" id="end_date"
                   value="{{ old('end_date', $session['data']['end_date'] ?? '') }}"
                   min="{{ now()->toDateString() }}"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.end_date_help') }}</p>
        </div>
    </div>

    {{-- Description --}}
    <div>
        <label for="description" class="block text-sm font-medium text-gray-700">
            {{ __('campaigns.description') }}
        </label>
        <textarea name="description" id="description" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  placeholder="{{ __('campaigns.description_placeholder') }}">{{ old('description', $session['data']['description'] ?? '') }}</textarea>
        <p class="mt-1 text-sm text-gray-500">{{ __('campaigns.wizard.description_help') }}</p>
    </div>
</div>
