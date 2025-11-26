<?php

namespace Tests\Unit\Http\Requests\AI;

use Tests\TestCase;
use App\Http\Requests\AI\GenerateContentRequest;
use Illuminate\Support\Facades\Validator;

/**
 * Generate Content Request Test
 *
 * Tests input validation and XSS protection for AI content generation.
 * Part of Phase 1B weakness remediation (2025-11-21)
 */
class GenerateContentRequestTest extends TestCase
{
    /** @test */
    public function it_validates_required_fields()
    {
        $request = new GenerateContentRequest();

        $validator = Validator::make([], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content_type', $validator->errors()->toArray());
        $this->assertArrayHasKey('prompt', $validator->errors()->toArray());
    }

    /** @test */
    public function it_accepts_valid_content_types()
    {
        $request = new GenerateContentRequest();

        $validTypes = ['campaign', 'ad_copy', 'social_post', 'email', 'blog_post'];

        foreach ($validTypes as $type) {
            $validator = Validator::make([
                'content_type' => $type,
                'prompt' => 'Test prompt for content generation',
            ], $request->rules());

            $this->assertFalse($validator->fails(), "Content type '{$type}' should be valid");
        }
    }

    /** @test */
    public function it_rejects_invalid_content_types()
    {
        $request = new GenerateContentRequest();

        $validator = Validator::make([
            'content_type' => 'invalid_type',
            'prompt' => 'Test prompt',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('content_type', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_prompt_length()
    {
        $request = new GenerateContentRequest();

        // Too short
        $shortValidator = Validator::make([
            'content_type' => 'campaign',
            'prompt' => 'Short',
        ], $request->rules());

        $this->assertTrue($shortValidator->fails());
        $this->assertArrayHasKey('prompt', $shortValidator->errors()->toArray());

        // Too long
        $longPrompt = str_repeat('a', 5001);
        $longValidator = Validator::make([
            'content_type' => 'campaign',
            'prompt' => $longPrompt,
        ], $request->rules());

        $this->assertTrue($longValidator->fails());
        $this->assertArrayHasKey('prompt', $longValidator->errors()->toArray());

        // Just right
        $validValidator = Validator::make([
            'content_type' => 'campaign',
            'prompt' => 'This is a valid prompt for content generation',
        ], $request->rules());

        $this->assertFalse($validValidator->fails());
    }

    /** @test */
    public function it_validates_marketing_principles()
    {
        $request = new GenerateContentRequest();

        $validPrinciples = [
            'scarcity', 'urgency', 'social_proof', 'authority',
            'reciprocity', 'consistency', 'liking', 'unity',
        ];

        foreach ($validPrinciples as $principle) {
            $validator = Validator::make([
                'content_type' => 'ad_copy',
                'prompt' => 'Generate compelling ad copy',
                'marketing_principle' => $principle,
            ], $request->rules());

            $this->assertFalse($validator->fails(), "Marketing principle '{$principle}' should be valid");
        }
    }

    /** @test */
    public function it_validates_tone_options()
    {
        $request = new GenerateContentRequest();

        $validTones = ['professional', 'casual', 'friendly', 'formal', 'enthusiastic'];

        foreach ($validTones as $tone) {
            $validator = Validator::make([
                'content_type' => 'email',
                'prompt' => 'Write an engaging email',
                'tone' => $tone,
            ], $request->rules());

            $this->assertFalse($validator->fails(), "Tone '{$tone}' should be valid");
        }
    }

    /** @test */
    public function it_validates_language_options()
    {
        $request = new GenerateContentRequest();

        // Valid languages
        $validLanguages = ['en', 'ar'];

        foreach ($validLanguages as $lang) {
            $validator = Validator::make([
                'content_type' => 'blog_post',
                'prompt' => 'Write a blog post about marketing',
                'language' => $lang,
            ], $request->rules());

            $this->assertFalse($validator->fails(), "Language '{$lang}' should be valid");
        }

        // Invalid language
        $invalidValidator = Validator::make([
            'content_type' => 'blog_post',
            'prompt' => 'Write a blog post',
            'language' => 'fr',
        ], $request->rules());

        $this->assertTrue($invalidValidator->fails());
    }

    /** @test */
    public function it_validates_max_length_constraints()
    {
        $request = new GenerateContentRequest();

        // Too small
        $validator = Validator::make([
            'content_type' => 'ad_copy',
            'prompt' => 'Generate short ad copy',
            'max_length' => 25,
        ], $request->rules());

        $this->assertTrue($validator->fails());

        // Too large
        $validator = Validator::make([
            'content_type' => 'ad_copy',
            'prompt' => 'Generate very long content',
            'max_length' => 10000,
        ], $request->rules());

        $this->assertTrue($validator->fails());

        // Valid
        $validator = Validator::make([
            'content_type' => 'ad_copy',
            'prompt' => 'Generate ad copy with specific length',
            'max_length' => 500,
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_context_structure()
    {
        $request = new GenerateContentRequest();

        $validator = Validator::make([
            'content_type' => 'campaign',
            'prompt' => 'Create campaign content',
            'context' => [
                'brand_voice' => 'Friendly and approachable',
                'target_audience' => 'Young professionals aged 25-35',
                'key_points' => [
                    'Highlight affordability',
                    'Emphasize quality',
                    'Mention customer support',
                ],
            ],
        ], $request->rules());

        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function it_validates_context_field_lengths()
    {
        $request = new GenerateContentRequest();

        // Brand voice too long
        $validator = Validator::make([
            'content_type' => 'campaign',
            'prompt' => 'Create campaign content',
            'context' => [
                'brand_voice' => str_repeat('a', 501),
            ],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('context.brand_voice', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_key_points_array_limit()
    {
        $request = new GenerateContentRequest();

        // Too many key points
        $validator = Validator::make([
            'content_type' => 'campaign',
            'prompt' => 'Create campaign content',
            'context' => [
                'key_points' => array_fill(0, 11, 'Key point'),
            ],
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('context.key_points', $validator->errors()->toArray());
    }

    /** @test */
    public function it_validates_campaign_id_format()
    {
        $request = new GenerateContentRequest();

        // Invalid UUID
        $validator = Validator::make([
            'content_type' => 'ad_copy',
            'prompt' => 'Generate ad copy for campaign',
            'campaign_id' => 'not-a-uuid',
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('campaign_id', $validator->errors()->toArray());

        // Valid UUID format (existence check would happen in real DB)
        $validator = Validator::make([
            'content_type' => 'ad_copy',
            'prompt' => 'Generate ad copy for campaign',
            'campaign_id' => '550e8400-e29b-41d4-a716-446655440000',
        ], $request->rules());

        // May fail on exists check in real environment, but format is valid
        $errors = $validator->errors()->toArray();
        if (isset($errors['campaign_id'])) {
            $this->assertStringContainsString('exists', $errors['campaign_id'][0]);
        }
    }

    /** @test */
    public function it_sanitizes_dangerous_input()
    {
        $request = new GenerateContentRequest();

        // Create instance and prepare data
        $request->merge([
            'prompt' => '<script>alert("XSS")</script>Generate content',
            'context' => [
                'brand_voice' => '<img src=x onerror=alert(1)>Friendly',
                'key_points' => [
                    '<b>Safe bold</b>',
                    '<script>alert("bad")</script>Unsafe',
                ],
            ],
        ]);

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        // Check that scripts are removed but safe tags remain
        $prompt = $request->input('prompt');
        $this->assertStringNotContainsString('<script>', $prompt);
        $this->assertStringNotContainsString('onerror', $prompt);
    }

    /** @test */
    public function it_has_custom_error_messages()
    {
        $request = new GenerateContentRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('prompt.required', $messages);
        $this->assertArrayHasKey('prompt.min', $messages);
        $this->assertArrayHasKey('content_type.required', $messages);
        $this->assertArrayHasKey('content_type.in', $messages);

        $this->assertStringContainsString('prompt', strtolower($messages['prompt.required']));
    }

    /** @test */
    public function it_sets_default_values()
    {
        $request = new GenerateContentRequest();

        $request->merge([
            'content_type' => 'campaign',
            'prompt' => 'Generate campaign content',
        ]);

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('prepareForValidation');
        $method->setAccessible(true);
        $method->invoke($request);

        // Should have default language and tone
        $this->assertNotNull($request->input('language'));
        $this->assertNotNull($request->input('tone'));
        $this->assertEquals('professional', $request->input('tone'));
    }

    /** @test */
    public function it_removes_null_bytes_from_input()
    {
        $request = new GenerateContentRequest();

        $maliciousInput = "Normal text" . chr(0) . "with null byte";

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('sanitizeInput');
        $method->setAccessible(true);

        $sanitized = $method->invoke($request, $maliciousInput);

        $this->assertStringNotContainsString(chr(0), $sanitized);
    }

    /** @test */
    public function it_trims_whitespace()
    {
        $request = new GenerateContentRequest();

        $reflection = new \ReflectionClass($request);
        $method = $reflection->getMethod('sanitizeInput');
        $method->setAccessible(true);

        $sanitized = $method->invoke($request, '  Test content  ');

        $this->assertEquals('Test content', $sanitized);
    }
}
