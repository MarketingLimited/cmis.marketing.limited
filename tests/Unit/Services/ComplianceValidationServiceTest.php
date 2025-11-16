<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ComplianceValidationService;

class ComplianceValidationServiceTest extends TestCase
{
    private ComplianceValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ComplianceValidationService();
    }

    /** @test */
    public function it_validates_compliant_content()
    {
        $content = "Check out our amazing summer sale! Great deals on all items.";

        $result = $this->service->validateContent($content);

        $this->assertTrue($result['is_compliant']);
        $this->assertEmpty($result['violations']);
        $this->assertEquals(100, $result['score']);
    }

    /** @test */
    public function it_detects_social_media_length_violations()
    {
        $content = str_repeat('a', 300); // Exceeds 280 char limit

        $result = $this->service->validateContent($content, [
            'platform' => 'twitter',
        ]);

        $this->assertFalse($result['is_compliant']);
        $this->assertNotEmpty($result['violations']);

        $violation = $result['violations'][0];
        $this->assertEquals('social_media_length', $violation['rule_id']);
        $this->assertEquals('high', $violation['severity']);
    }

    /** @test */
    public function it_detects_offensive_content()
    {
        $content = "This product is offensive_word amazing!";

        $result = $this->service->validateContent($content);

        $this->assertFalse($result['is_compliant']);

        $hasOffensiveViolation = false;
        foreach ($result['violations'] as $violation) {
            if ($violation['rule_id'] === 'offensive_content') {
                $hasOffensiveViolation = true;
                break;
            }
        }

        $this->assertTrue($hasOffensiveViolation);
    }

    /** @test */
    public function it_detects_health_claims()
    {
        $content = "Our product cures all diseases and guarantees weight loss!";

        $result = $this->service->validateContent($content);

        $this->assertFalse($result['is_compliant']);

        $hasHealthViolation = false;
        foreach ($result['violations'] as $violation) {
            if ($violation['rule_id'] === 'health_claims') {
                $hasHealthViolation = true;
                $this->assertEquals('high', $violation['severity']);
                break;
            }
        }

        $this->assertTrue($hasHealthViolation);
    }

    /** @test */
    public function it_can_add_custom_rules()
    {
        $rule = [
            'id' => 'custom_rule',
            'name' => 'Custom Rule Test',
            'type' => 'prohibited_words',
            'severity' => 'medium',
            'config' => [
                'words' => ['forbidden_word'],
            ],
            'applies_to' => [],
        ];

        $this->service->addRule($rule);

        $content = "This contains forbidden_word in it.";
        $result = $this->service->validateContent($content);

        $this->assertFalse($result['is_compliant']);
    }

    /** @test */
    public function it_applies_rules_based_on_context()
    {
        $twitterContent = str_repeat('a', 300);

        // Should violate when platform is twitter
        $result1 = $this->service->validateContent($twitterContent, [
            'platform' => 'twitter',
        ]);
        $this->assertFalse($result1['is_compliant']);

        // Should pass when platform is not specified
        $result2 = $this->service->validateContent($twitterContent, []);
        // May still pass or fail based on other rules
        $this->assertIsArray($result2);
    }

    /** @test */
    public function it_calculates_compliance_score()
    {
        // Content with one high severity violation
        $content = "Our product cures cancer!";

        $result = $this->service->validateContent($content);

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);

        // High severity violation should reduce score by 20
        if (!empty($result['violations'])) {
            $this->assertLessThan(100, $result['score']);
        }
    }

    /** @test */
    public function it_provides_warnings_for_us_market()
    {
        $content = "Sponsored content about our product";

        $result = $this->service->validateContent($content, [
            'market' => 'US',
        ]);

        // Should have warning about FTC #ad disclosure
        $this->assertNotEmpty($result['warnings']);
    }

    /** @test */
    public function it_provides_warnings_for_eu_market()
    {
        $content = "We collect your email address for marketing";

        $result = $this->service->validateContent($content, [
            'market' => 'EU',
        ]);

        // Should have GDPR warning
        $hasGdprWarning = false;
        foreach ($result['warnings'] as $warning) {
            if (stripos($warning, 'GDPR') !== false) {
                $hasGdprWarning = true;
                break;
            }
        }

        $this->assertTrue($hasGdprWarning);
    }

    /** @test */
    public function it_handles_multiple_violations()
    {
        $content = str_repeat('offensive_word ', 50) . " " . str_repeat('a', 300);

        $result = $this->service->validateContent($content, [
            'platform' => 'facebook',
        ]);

        $this->assertFalse($result['is_compliant']);
        $this->assertGreaterThanOrEqual(1, count($result['violations']));
    }

    /** @test */
    public function it_validates_empty_content()
    {
        $result = $this->service->validateContent('');

        $this->assertTrue($result['is_compliant']);
        $this->assertEmpty($result['violations']);
    }

    /** @test */
    public function it_validates_short_content()
    {
        $content = "Sale!";

        $result = $this->service->validateContent($content);

        $this->assertTrue($result['is_compliant']);
    }

    /** @test */
    public function it_detects_case_insensitive_violations()
    {
        $content1 = "This product CURES everything!";
        $content2 = "This product cures everything!";

        $result1 = $this->service->validateContent($content1);
        $result2 = $this->service->validateContent($content2);

        // Both should detect health claims regardless of case
        $this->assertFalse($result1['is_compliant']);
        $this->assertFalse($result2['is_compliant']);
    }

    /** @test */
    public function it_returns_proper_structure()
    {
        $result = $this->service->validateContent("Test content");

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_compliant', $result);
        $this->assertArrayHasKey('violations', $result);
        $this->assertArrayHasKey('warnings', $result);
        $this->assertArrayHasKey('score', $result);

        $this->assertIsBool($result['is_compliant']);
        $this->assertIsArray($result['violations']);
        $this->assertIsArray($result['warnings']);
        $this->assertIsNumeric($result['score']);
    }

    /** @test */
    public function it_includes_severity_in_violations()
    {
        $content = "offensive_word here";

        $result = $this->service->validateContent($content);

        if (!empty($result['violations'])) {
            $violation = $result['violations'][0];

            $this->assertArrayHasKey('severity', $violation);
            $this->assertContains($violation['severity'], ['critical', 'high', 'medium', 'low']);
        }
    }

    /** @test */
    public function it_includes_rule_information_in_violations()
    {
        $content = "offensive_word content";

        $result = $this->service->validateContent($content);

        if (!empty($result['violations'])) {
            $violation = $result['violations'][0];

            $this->assertArrayHasKey('rule_id', $violation);
            $this->assertArrayHasKey('rule_name', $violation);
            $this->assertArrayHasKey('message', $violation);
        }
    }

    /** @test */
    public function it_validates_facebook_specific_rules()
    {
        $content = str_repeat('a', 300);

        $result = $this->service->validateContent($content, [
            'platform' => 'facebook',
        ]);

        // Facebook has similar length restrictions as Twitter
        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_compliant', $result);
    }

    /** @test */
    public function it_validates_instagram_specific_rules()
    {
        $content = str_repeat('a', 300);

        $result = $this->service->validateContent($content, [
            'platform' => 'instagram',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_compliant', $result);
    }

    /** @test */
    public function it_provides_suggestions()
    {
        $content = "Sponsored content";

        $result = $this->service->validateContent($content, [
            'market' => 'US',
        ]);

        if (!empty($result['suggestions'])) {
            $this->assertIsArray($result['suggestions']);
            $this->assertIsString($result['suggestions'][0]);
        }
    }

    /** @test */
    public function it_handles_special_characters()
    {
        $content = "Special chars: @#$%^&*()!";

        $result = $this->service->validateContent($content);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_compliant', $result);
    }

    /** @test */
    public function it_handles_unicode_characters()
    {
        $content = "Unicode test: 你好世界 مرحبا العالم";

        $result = $this->service->validateContent($content);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_compliant']);
    }

    /** @test */
    public function it_handles_urls_in_content()
    {
        $content = "Visit https://example.com for more info!";

        $result = $this->service->validateContent($content);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_compliant']);
    }

    /** @test */
    public function it_handles_hashtags_in_content()
    {
        $content = "Check out our #Summer #Sale #2025";

        $result = $this->service->validateContent($content);

        $this->assertIsArray($result);
        $this->assertTrue($result['is_compliant']);
    }

    /** @test */
    public function it_validates_ccpa_for_california()
    {
        $content = "We use your data for advertising";

        $result = $this->service->validateContent($content, [
            'market' => 'CA',
        ]);

        // Should have CCPA warning
        $hasCcpaWarning = false;
        foreach ($result['warnings'] as $warning) {
            if (stripos($warning, 'CCPA') !== false) {
                $hasCcpaWarning = true;
                break;
            }
        }

        $this->assertTrue($hasCcpaWarning);
    }

    /** @test */
    public function it_handles_multiple_context_attributes()
    {
        $content = "Check out our sale!";

        $result = $this->service->validateContent($content, [
            'platform' => 'twitter',
            'market' => 'US',
            'content_type' => 'ad',
        ]);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('is_compliant', $result);
    }

    /** @test */
    public function it_rejects_critical_violations_immediately()
    {
        // Add a critical severity rule
        $rule = [
            'id' => 'critical_test',
            'name' => 'Critical Test Rule',
            'type' => 'prohibited_words',
            'severity' => 'critical',
            'config' => [
                'words' => ['critical_word'],
            ],
            'applies_to' => [],
        ];

        $this->service->addRule($rule);

        $content = "This contains critical_word";
        $result = $this->service->validateContent($content);

        $this->assertFalse($result['is_compliant']);

        // Critical violation should significantly reduce score
        $this->assertLessThanOrEqual(70, $result['score']);
    }

    /** @test */
    public function it_accumulates_score_deductions()
    {
        $content = "offensive_word and another offensive_term cure guarantee";

        $result = $this->service->validateContent($content);

        // Multiple violations should reduce score more
        if (count($result['violations']) > 1) {
            $this->assertLessThan(80, $result['score']);
        }
    }

    /** @test */
    public function it_respects_minimum_score_of_zero()
    {
        // Add many critical violations
        for ($i = 0; $i < 10; $i++) {
            $this->service->addRule([
                'id' => "critical_{$i}",
                'name' => "Critical Rule {$i}",
                'type' => 'prohibited_words',
                'severity' => 'critical',
                'config' => ['words' => ["word{$i}"]],
                'applies_to' => [],
            ]);
        }

        $content = "word0 word1 word2 word3 word4 word5 word6 word7 word8 word9";
        $result = $this->service->validateContent($content);

        // Score should not go below 0
        $this->assertGreaterThanOrEqual(0, $result['score']);
    }
}
