<?php

namespace App\Http\Requests\ValidationRules;

/**
 * Centralized validation rules for Campaign resources.
 *
 * Use these rules across both web forms and API endpoints to ensure
 * consistent validation behavior regardless of interface.
 *
 * Issue #64 - Align validation rules across web/API
 */
class CampaignRules
{
    /**
     * Validation rules for creating a campaign.
     *
     * Used by both web forms and API endpoints.
     */
    public static function createRules(bool $isDraft = false): array
    {
        if ($isDraft) {
            return self::draftRules();
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'org_id' => ['required', 'uuid', 'exists:cmis.organizations,id'],
            'status' => ['required', 'in:draft,active,paused,completed,archived'],
            'objective' => ['required', 'string', 'in:awareness,consideration,conversion,retention'],
            'budget' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'budget_type' => ['required', 'in:daily,lifetime,unlimited'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'target_audience' => ['nullable', 'json'],
            'target_locations' => ['nullable', 'json'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['string', 'in:meta,google,tiktok,linkedin,twitter,snapchat'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }

    /**
     * Validation rules for draft campaigns.
     *
     * Drafts only require minimal fields.
     */
    public static function draftRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'org_id' => ['required', 'uuid', 'exists:cmis.organizations,id'],
            'status' => ['sometimes', 'in:draft'],
            'objective' => ['nullable', 'string', 'in:awareness,consideration,conversion,retention'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'budget_type' => ['nullable', 'in:daily,lifetime,unlimited'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
        ];
    }

    /**
     * Validation rules for updating a campaign.
     */
    public static function updateRules(bool $isDraft = false): array
    {
        $rules = self::createRules($isDraft);

        // Make all fields optional for updates (PATCH)
        foreach ($rules as $field => $fieldRules) {
            $rules[$field] = array_map(function ($rule) {
                return $rule === 'required' ? 'sometimes' : $rule;
            }, $fieldRules);
        }

        // Don't allow changing org_id
        unset($rules['org_id']);

        return $rules;
    }

    /**
     * Validation rules for status transitions.
     */
    public static function statusTransitionRules(string $currentStatus): array
    {
        // Define valid status transitions
        $validTransitions = [
            'draft' => ['active', 'archived'],
            'active' => ['paused', 'completed', 'archived'],
            'paused' => ['active', 'archived'],
            'completed' => ['archived'],
            'archived' => [], // Cannot transition from archived
        ];

        $allowedStatuses = $validTransitions[$currentStatus] ?? [];

        return [
            'status' => ['required', 'in:' . implode(',', $allowedStatuses)],
        ];
    }

    /**
     * Custom validation messages.
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Campaign name is required.',
            'name.max' => 'Campaign name cannot exceed 255 characters.',
            'budget.required' => 'Budget is required for non-draft campaigns.',
            'budget.numeric' => 'Budget must be a valid number.',
            'budget.min' => 'Budget must be greater than or equal to 0.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date cannot be in the past.',
            'end_date.after' => 'End date must be after the start date.',
            'objective.required' => 'Campaign objective is required.',
            'objective.in' => 'Invalid campaign objective. Must be one of: awareness, consideration, conversion, retention.',
            'status.in' => 'Invalid campaign status.',
            'platforms.*.in' => 'Invalid platform. Supported platforms: meta, google, tiktok, linkedin, twitter, snapchat.',
        ];
    }
}
