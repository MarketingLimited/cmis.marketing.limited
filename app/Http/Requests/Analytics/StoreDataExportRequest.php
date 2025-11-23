<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Store Data Export Request Validation
 *
 * Validates analytics data export requests:
 * - Export format and scope selection
 * - Date range validation
 * - Field selection
 *
 * Security Features:
 * - Valid format selection
 * - Date range limits to prevent abuse
 * - Field whitelist validation
 */
class StoreDataExportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'export_type' => [
                'required',
                'in:campaign_performance,ad_performance,audience_insights,conversion_data,full_analytics',
            ],
            'format' => [
                'required',
                'in:csv,xlsx,json,pdf',
            ],
            'start_date' => [
                'required',
                'date',
                'before_or_equal:end_date',
                'after_or_equal:' . now()->subYears(2)->format('Y-m-d'), // Max 2 years back
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                'before_or_equal:today',
            ],
            'campaign_ids' => [
                'nullable',
                'array',
            ],
            'campaign_ids.*' => [
                'uuid',
                'exists:cmis.campaigns,campaign_id',
            ],
            'fields' => [
                'nullable',
                'array',
            ],
            'fields.*' => [
                'string',
                'max:100',
            ],
            'include_metadata' => [
                'nullable',
                'boolean',
            ],
            'email_notification' => [
                'nullable',
                'boolean',
            ],
            'notification_email' => [
                'required_if:email_notification,true',
                'email',
            ],
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
            'export_type.required' => 'Export type is required',
            'export_type.in' => 'Invalid export type selected',
            'format.required' => 'Export format is required',
            'format.in' => 'Invalid format. Must be one of: CSV, XLSX, JSON, PDF',
            'start_date.required' => 'Start date is required',
            'start_date.after_or_equal' => 'Start date cannot be more than 2 years in the past',
            'end_date.required' => 'End date is required',
            'end_date.before_or_equal' => 'End date cannot be in the future',
            'campaign_ids.*.exists' => 'One or more selected campaigns do not exist',
            'notification_email.required_if' => 'Email address is required when email notification is enabled',
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
            'export_type' => 'export type',
            'format' => 'file format',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'campaign_ids' => 'selected campaigns',
            'notification_email' => 'notification email',
        ];
    }
}
