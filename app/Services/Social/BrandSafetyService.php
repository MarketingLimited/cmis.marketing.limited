<?php

namespace App\Services\Social;

use App\Models\Compliance\BrandSafetyPolicy;

/**
 * Service for brand safety validation.
 */
class BrandSafetyService
{
    /**
     * Validate content against brand safety policies.
     *
     * @param string $orgId Organization ID
     * @param string $content Content to validate
     * @param string|null $profileGroupId Optional profile group ID for specific policies
     * @return array Result with passed status and issues
     */
    public function validate(string $orgId, string $content, ?string $profileGroupId = null): array
    {
        $policies = $this->getApplicablePolicies($orgId, $profileGroupId);

        $issues = [];
        $passed = true;

        foreach ($policies as $policy) {
            $policyResult = $this->validateAgainstPolicy($content, $policy);

            if (!empty($policyResult['issues'])) {
                $issues = array_merge($issues, $policyResult['issues']);
            }

            if (!$policyResult['passed']) {
                $passed = false;
            }
        }

        return [
            'passed' => $passed,
            'issues' => array_unique($issues),
        ];
    }

    /**
     * Get applicable policies for the organization.
     */
    protected function getApplicablePolicies(string $orgId, ?string $profileGroupId): \Illuminate\Support\Collection
    {
        $query = BrandSafetyPolicy::where('org_id', $orgId)
            ->where('is_active', true);

        if ($profileGroupId) {
            $query->where(function ($q) use ($profileGroupId) {
                $q->whereNull('profile_group_id')
                    ->orWhere('profile_group_id', $profileGroupId);
            });
        }

        return $query->get();
    }

    /**
     * Validate content against a single policy.
     */
    protected function validateAgainstPolicy(string $content, BrandSafetyPolicy $policy): array
    {
        $issues = [];
        $passed = true;

        // Check blocked words
        $blockedResult = $this->checkBlockedWords($content, $policy);
        if (!$blockedResult['passed']) {
            $passed = false;
            $issues = array_merge($issues, $blockedResult['issues']);
        }

        // Check required elements
        $requiredResult = $this->checkRequiredElements($content, $policy);
        if (!empty($requiredResult['issues'])) {
            $issues = array_merge($issues, $requiredResult['issues']);
            if ($policy->severity_level === 'block') {
                $passed = false;
            }
        }

        // Check character limits
        $limitsResult = $this->checkCharacterLimits($content, $policy);
        if (!$limitsResult['passed']) {
            $passed = false;
            $issues = array_merge($issues, $limitsResult['issues']);
        }

        // Check URL restrictions
        $urlResult = $this->checkUrlRestrictions($content, $policy);
        if (!$urlResult['passed']) {
            $passed = false;
            $issues = array_merge($issues, $urlResult['issues']);
        }

        // Check disclosure requirements
        $disclosureResult = $this->checkDisclosureRequirements($content, $policy);
        if (!empty($disclosureResult['issues'])) {
            $issues = array_merge($issues, $disclosureResult['issues']);
            if ($policy->severity_level === 'block') {
                $passed = false;
            }
        }

        return [
            'passed' => $passed,
            'issues' => $issues,
        ];
    }

    /**
     * Check for blocked words in content.
     */
    protected function checkBlockedWords(string $content, BrandSafetyPolicy $policy): array
    {
        $issues = [];
        $blockedWords = $policy->blocked_words ?? [];

        foreach ($blockedWords as $word) {
            if (stripos($content, $word) !== false) {
                $issues[] = "Contains blocked word: '{$word}'";
            }
        }

        return [
            'passed' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check for required elements in content.
     */
    protected function checkRequiredElements(string $content, BrandSafetyPolicy $policy): array
    {
        $requiredElements = $policy->required_elements ?? [];

        if (empty($requiredElements)) {
            return ['passed' => true, 'issues' => []];
        }

        $hasRequired = false;
        foreach ($requiredElements as $element) {
            if (stripos($content, $element) !== false) {
                $hasRequired = true;
                break;
            }
        }

        if (!$hasRequired) {
            return [
                'passed' => false,
                'issues' => ['Missing required element from: ' . implode(', ', $requiredElements)],
            ];
        }

        return ['passed' => true, 'issues' => []];
    }

    /**
     * Check character limits.
     */
    protected function checkCharacterLimits(string $content, BrandSafetyPolicy $policy): array
    {
        $issues = [];
        $rules = $policy->rules ?? [];

        if (!empty($rules['min_characters']) && strlen($content) < $rules['min_characters']) {
            $issues[] = "Content is shorter than minimum {$rules['min_characters']} characters";
        }

        if (!empty($rules['max_characters']) && strlen($content) > $rules['max_characters']) {
            $issues[] = "Content exceeds maximum {$rules['max_characters']} characters";
        }

        return [
            'passed' => empty($issues),
            'issues' => $issues,
        ];
    }

    /**
     * Check URL restrictions.
     */
    protected function checkUrlRestrictions(string $content, BrandSafetyPolicy $policy): array
    {
        $rules = $policy->rules ?? [];

        if (!empty($rules['block_urls'])) {
            if (preg_match('/https?:\/\/[^\s]+/', $content)) {
                return [
                    'passed' => false,
                    'issues' => ['External URLs are not allowed'],
                ];
            }
        }

        return ['passed' => true, 'issues' => []];
    }

    /**
     * Check disclosure requirements.
     */
    protected function checkDisclosureRequirements(string $content, BrandSafetyPolicy $policy): array
    {
        $rules = $policy->rules ?? [];

        if (!empty($rules['require_disclosure'])) {
            if (!preg_match('/#(ad|sponsored|partnership)/i', $content)) {
                return [
                    'passed' => false,
                    'issues' => ['Sponsored content must include disclosure (#ad, #sponsored)'],
                ];
            }
        }

        return ['passed' => true, 'issues' => []];
    }
}
