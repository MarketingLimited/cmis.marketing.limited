<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Compliance Validation Service
 *
 * Validates content against compliance rules for different markets and platforms.
 * Checks for regulatory compliance, brand guidelines, and content policies.
 */
class ComplianceValidationService
{
    protected array $rules = [];

    public function __construct()
    {
        $this->loadDefaultRules();
    }

    /**
     * Validate content against all applicable rules
     */
    public function validateContent(string $content, array $context = []): array
    {
        $violations = [];
        $warnings = [];
        $suggestions = [];

        foreach ($this->rules as $rule) {
            // Check if rule applies to this context
            if (!$this->ruleApplies($rule, $context)) {
                continue;
            }

            $result = $this->applyRule($rule, $content, $context);

            if ($result['violation']) {
                $violations[] = [
                    'rule_id' => $rule['id'],
                    'rule_name' => $rule['name'],
                    'severity' => $rule['severity'],
                    'message' => $result['message'],
                    'details' => $result['details'] ?? null,
                ];
            }

            if (!empty($result['warnings'])) {
                $warnings = array_merge($warnings, $result['warnings']);
            }

            if (!empty($result['suggestions'])) {
                $suggestions = array_merge($suggestions, $result['suggestions']);
            }
        }

        $isCompliant = empty($violations);

        Log::info('Content compliance check completed', [
            'compliant' => $isCompliant,
            'violations' => count($violations),
            'warnings' => count($warnings),
        ]);

        return [
            'is_compliant' => $isCompliant,
            'violations' => $violations,
            'warnings' => $warnings,
            'suggestions' => $suggestions,
            'score' => $this->calculateComplianceScore($violations, $warnings),
        ];
    }

    /**
     * Add custom compliance rule
     */
    public function addRule(array $rule): void
    {
        $this->rules[] = array_merge([
            'id' => uniqid('rule_'),
            'name' => 'Custom Rule',
            'severity' => 'medium',
            'enabled' => true,
            'applies_to' => [],
        ], $rule);
    }

    /**
     * Check if rule applies to given context
     */
    protected function ruleApplies(array $rule, array $context): bool
    {
        if (!$rule['enabled']) {
            return false;
        }

        // Check market restrictions
        if (!empty($rule['markets']) && isset($context['market'])) {
            if (!in_array($context['market'], $rule['markets'])) {
                return false;
            }
        }

        // Check platform restrictions
        if (!empty($rule['platforms']) && isset($context['platform'])) {
            if (!in_array($context['platform'], $rule['platforms'])) {
                return false;
            }
        }

        // Check content type restrictions
        if (!empty($rule['content_types']) && isset($context['content_type'])) {
            if (!in_array($context['content_type'], $rule['content_types'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Apply single rule to content
     */
    protected function applyRule(array $rule, string $content, array $context): array
    {
        $result = [
            'violation' => false,
            'warnings' => [],
            'suggestions' => [],
        ];

        switch ($rule['type']) {
            case 'length':
                $result = $this->checkLength($content, $rule);
                break;

            case 'prohibited_words':
                $result = $this->checkProhibitedWords($content, $rule);
                break;

            case 'required_disclaimers':
                $result = $this->checkRequiredDisclaimers($content, $rule);
                break;

            case 'prohibited_claims':
                $result = $this->checkProhibitedClaims($content, $rule);
                break;

            case 'brand_guidelines':
                $result = $this->checkBrandGuidelines($content, $rule);
                break;

            case 'regulatory':
                $result = $this->checkRegulatory($content, $rule, $context);
                break;

            default:
                $result['warnings'][] = "Unknown rule type: {$rule['type']}";
        }

        return $result;
    }

    /**
     * Check content length compliance
     */
    protected function checkLength(string $content, array $rule): array
    {
        $length = mb_strlen($content);
        $violation = false;
        $message = null;

        if (isset($rule['min_length']) && $length < $rule['min_length']) {
            $violation = true;
            $message = "Content too short: {$length} characters (minimum: {$rule['min_length']})";
        }

        if (isset($rule['max_length']) && $length > $rule['max_length']) {
            $violation = true;
            $message = "Content too long: {$length} characters (maximum: {$rule['max_length']})";
        }

        return [
            'violation' => $violation,
            'message' => $message,
            'details' => ['current_length' => $length],
        ];
    }

    /**
     * Check for prohibited words/phrases
     */
    protected function checkProhibitedWords(string $content, array $rule): array
    {
        $found = [];
        $content_lower = mb_strtolower($content);

        foreach ($rule['words'] as $word) {
            if (stripos($content_lower, mb_strtolower($word)) !== false) {
                $found[] = $word;
            }
        }

        if (!empty($found)) {
            return [
                'violation' => true,
                'message' => "Prohibited words/phrases found: " . implode(', ', $found),
                'details' => ['prohibited_words' => $found],
            ];
        }

        return ['violation' => false];
    }

    /**
     * Check for required disclaimers
     */
    protected function checkRequiredDisclaimers(string $content, array $rule): array
    {
        $missing = [];

        foreach ($rule['disclaimers'] as $disclaimer) {
            if (stripos($content, $disclaimer) === false) {
                $missing[] = $disclaimer;
            }
        }

        if (!empty($missing)) {
            return [
                'violation' => true,
                'message' => "Missing required disclaimers",
                'details' => ['missing_disclaimers' => $missing],
                'suggestions' => ["Add required disclaimer: " . $missing[0]],
            ];
        }

        return ['violation' => false];
    }

    /**
     * Check for prohibited claims
     */
    protected function checkProhibitedClaims(string $content, array $rule): array
    {
        $found = [];

        foreach ($rule['claims'] as $claim) {
            if (preg_match($claim['pattern'], $content)) {
                $found[] = $claim['name'];
            }
        }

        if (!empty($found)) {
            return [
                'violation' => true,
                'message' => "Prohibited claims detected: " . implode(', ', $found),
                'details' => ['prohibited_claims' => $found],
            ];
        }

        return ['violation' => false];
    }

    /**
     * Check brand guidelines compliance
     */
    protected function checkBrandGuidelines(string $content, array $rule): array
    {
        $warnings = [];

        // Check tone
        if (isset($rule['required_tone'])) {
            // This would use AI sentiment analysis in production
            $warnings[] = "Verify content matches {$rule['required_tone']} tone";
        }

        // Check brand terms
        if (isset($rule['brand_terms'])) {
            foreach ($rule['brand_terms'] as $term => $correct) {
                if (stripos($content, $term) !== false && stripos($content, $correct) === false) {
                    $warnings[] = "Use '{$correct}' instead of '{$term}'";
                }
            }
        }

        return [
            'violation' => false,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check regulatory compliance
     */
    protected function checkRegulatory(string $content, array $rule, array $context): array
    {
        $violations = [];
        $warnings = [];

        // Market-specific regulatory checks
        $market = $context['market'] ?? 'US';

        switch ($market) {
            case 'EU':
                // GDPR considerations
                if (stripos($content, 'personal data') !== false) {
                    $warnings[] = "Verify GDPR compliance for personal data mentions";
                }
                break;

            case 'US':
                // FTC guidelines
                if (stripos($content, '#ad') === false && isset($context['is_sponsored']) && $context['is_sponsored']) {
                    $violations[] = "Sponsored content must include #ad disclosure (FTC requirement)";
                }
                break;

            case 'CA':
                // California-specific requirements
                if (stripos($content, 'california') !== false) {
                    $warnings[] = "Verify California Consumer Privacy Act (CCPA) compliance";
                }
                break;
        }

        return [
            'violation' => !empty($violations),
            'message' => !empty($violations) ? implode('; ', $violations) : null,
            'warnings' => $warnings,
        ];
    }

    /**
     * Calculate compliance score (0-100)
     */
    protected function calculateComplianceScore(array $violations, array $warnings): float
    {
        $score = 100;

        // Deduct points for violations
        foreach ($violations as $violation) {
            $deduction = match($violation['severity']) {
                'critical' => 30,
                'high' => 20,
                'medium' => 10,
                'low' => 5,
                default => 10,
            };
            $score -= $deduction;
        }

        // Deduct points for warnings
        $score -= count($warnings) * 2;

        return max(0, $score);
    }

    /**
     * Load default compliance rules
     */
    protected function loadDefaultRules(): void
    {
        $this->rules = [
            [
                'id' => 'length_social',
                'name' => 'Social Media Length',
                'type' => 'length',
                'severity' => 'medium',
                'platforms' => ['facebook', 'twitter', 'instagram'],
                'max_length' => 280,
                'enabled' => true,
            ],
            [
                'id' => 'prohibited_offensive',
                'name' => 'Offensive Content',
                'type' => 'prohibited_words',
                'severity' => 'critical',
                'words' => ['offensive', 'inappropriate', 'discriminatory'],
                'enabled' => true,
            ],
            [
                'id' => 'health_claims',
                'name' => 'Unsubstantiated Health Claims',
                'type' => 'prohibited_claims',
                'severity' => 'high',
                'claims' => [
                    ['name' => 'Cure claims', 'pattern' => '/(cure|cures|curing)/i'],
                    ['name' => 'Guarantee claims', 'pattern' => '/(guarantee|guaranteed|100%\s*effective)/i'],
                ],
                'markets' => ['US', 'EU', 'CA'],
                'enabled' => true,
            ],
        ];
    }

    /**
     * Get all active rules
     */
    public function getRules(array $filters = []): array
    {
        $rules = $this->rules;

        if (isset($filters['market'])) {
            $rules = array_filter($rules, function($rule) use ($filters) {
                return empty($rule['markets']) || in_array($filters['market'], $rule['markets']);
            });
        }

        return array_values($rules);
    }
}
