<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Scheduled Report Request
 *
 * Validates creation of automated report schedules
 * Security: Ensures report configurations and recipients are valid
 */
class StoreScheduledReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization handled by middleware and policies
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'report_type' => 'required|in:campaign,organization,comparison,attribution',
            'frequency' => 'required|in:daily,weekly,monthly,quarterly',
            'format' => 'required|in:pdf,xlsx,csv,json',
            'recipients' => 'required|array|min:1|max:50',
            'recipients.*' => 'email',
            'config' => 'required|array',
            'config.metrics' => 'sometimes|array',
            'config.date_range' => 'sometimes|string',
            'config.filters' => 'sometimes|array',
            'timezone' => 'sometimes|string|timezone',
            'delivery_time' => 'sometimes|date_format:H:i:s',
            'day_of_week' => 'sometimes|integer|min:1|max:7',
            'day_of_month' => 'sometimes|integer|min:1|max:31',
            'is_active' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom error messages for validator.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Report name is required',
            'name.max' => 'Report name cannot exceed 255 characters',
            'report_type.required' => 'Report type is required',
            'report_type.in' => 'Invalid report type. Supported: campaign, organization, comparison, attribution',
            'frequency.required' => 'Frequency is required',
            'frequency.in' => 'Invalid frequency. Supported: daily, weekly, monthly, quarterly',
            'format.required' => 'Report format is required',
            'format.in' => 'Invalid format. Supported: pdf, xlsx, csv, json',
            'recipients.required' => 'At least one recipient email is required',
            'recipients.min' => 'At least one recipient is required',
            'recipients.max' => 'Cannot exceed 50 recipients',
            'recipients.*.email' => 'Each recipient must be a valid email address',
            'config.required' => 'Report configuration is required',
            'day_of_week.min' => 'Day of week must be between 1 (Monday) and 7 (Sunday)',
            'day_of_week.max' => 'Day of week must be between 1 (Monday) and 7 (Sunday)',
            'day_of_month.min' => 'Day of month must be between 1 and 31',
            'day_of_month.max' => 'Day of month must be between 1 and 31',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'report_type' => 'report type',
            'delivery_time' => 'delivery time',
            'day_of_week' => 'day of week',
            'day_of_month' => 'day of month',
        ];
    }
}
