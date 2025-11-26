<?php

namespace Tests\Unit\Services\AI;

use Tests\TestCase;
use App\Services\AI\GeminiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Exception;

class GeminiServiceTest extends TestCase
{
    private GeminiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up test API key
        Config::set('services.google.ai_api_key', 'test-api-key-12345');

        // Create service instance
        $this->service = new GeminiService();

        // Fake storage
        Storage::fake('public');
    }

    /** @test */
    public function it_throws_exception_when_api_key_not_configured()
    {
        Config::set('services.google.ai_api_key', '');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Google AI API key not configured');

        new GeminiService();
    }

    /** @test */
    public function it_generates_text_with_valid_response()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Generated ad copy text']
                            ]
                        ],
                        'finishReason' => 'STOP'
                    ]
                ],
                'usageMetadata' => [
                    'promptTokenCount' => 50,
                    'candidatesTokenCount' => 100,
                    'totalTokenCount' => 150
                ]
            ], 200)
        ]);

        $result = $this->service->generateText('Test prompt');

        $this->assertIsArray($result);
        $this->assertEquals('Generated ad copy text', $result['text']);
        $this->assertEquals(150, $result['tokens_used']);
        $this->assertEquals(50, $result['input_tokens']);
        $this->assertEquals(100, $result['output_tokens']);
        $this->assertEquals('STOP', $result['finish_reason']);
    }

    /** @test */
    public function it_generates_and_stores_image_correctly()
    {
        $base64Image = base64_encode('fake-image-data');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'data' => $base64Image,
                                        'mimeType' => 'image/png'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 200
                ]
            ], 200)
        ]);

        $result = $this->service->generateImage('Create a modern ad design');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('storage_path', $result);
        $this->assertEquals('image/png', $result['mime_type']);
        $this->assertEquals(200, $result['tokens_used']);
        $this->assertStringContainsString('ai-generated/', $result['storage_path']);

        // Verify file was stored
        $this->assertTrue(Storage::disk('public')->exists($result['storage_path']));
    }

    /** @test */
    public function it_generates_ad_copy_with_parsed_structured_output()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => "HEADLINES:\n- Amazing Product\n- Best Deal Ever\n- Shop Now\n\nDESCRIPTIONS:\n- Get 50% off today\n- Limited time offer\n- Free shipping\n\nPRIMARY_TEXT:\nDiscover our amazing product with incredible features that will transform your life. Don't miss this opportunity.\n\nCTAs:\n- Shop Now\n- Learn More\n- Get Started"]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 300
                ]
            ], 200)
        ]);

        $result = $this->service->generateAdCopy(
            'awareness',
            'young professionals',
            'innovative product',
            []
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('headlines', $result);
        $this->assertArrayHasKey('descriptions', $result);
        $this->assertArrayHasKey('call_to_actions', $result);
        $this->assertArrayHasKey('primary_text', $result);
        $this->assertArrayHasKey('tokens_used', $result);
        $this->assertArrayHasKey('cost', $result);

        $this->assertCount(3, $result['headlines']);
        $this->assertCount(3, $result['descriptions']);
        $this->assertCount(3, $result['call_to_actions']);
        $this->assertStringContainsString('amazing product', $result['primary_text']);
    }

    /** @test */
    public function it_generates_multiple_ad_design_variations()
    {
        $base64Image = base64_encode('fake-image-data');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'inlineData' => [
                                        'data' => $base64Image,
                                        'mimeType' => 'image/png'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 200
                ]
            ], 200)
        ]);

        $results = $this->service->generateAdDesign(
            'awareness',
            'modern, blue color scheme',
            ['Include logo', 'Add call to action'],
            3,
            'high'
        );

        $this->assertIsArray($results);
        $this->assertCount(3, $results);

        foreach ($results as $index => $design) {
            $this->assertArrayHasKey('variation', $design);
            $this->assertArrayHasKey('url', $design);
            $this->assertArrayHasKey('storage_path', $design);
            $this->assertArrayHasKey('file_size', $design);
            $this->assertArrayHasKey('tokens_used', $design);
            $this->assertArrayHasKey('cost', $design);
            $this->assertEquals($index + 1, $design['variation']);
        }
    }

    /** @test */
    public function it_calculates_text_cost_accurately()
    {
        // Using reflection to test private method
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateTextCost');
        $method->setAccessible(true);

        // Test with 1 million tokens (should cost $7)
        $cost1M = $method->invoke($this->service, 1000000);
        $this->assertEquals(7.0, $cost1M);

        // Test with 500k tokens (should cost $3.5)
        $cost500k = $method->invoke($this->service, 500000);
        $this->assertEquals(3.5, $cost500k);

        // Test with 100k tokens (should cost $0.7)
        $cost100k = $method->invoke($this->service, 100000);
        $this->assertEquals(0.7, $cost100k);

        // Test with small amount (100 tokens)
        $cost100 = $method->invoke($this->service, 100);
        $this->assertEquals(0.0007, $cost100);
    }

    /** @test */
    public function it_calculates_image_cost_varies_by_resolution()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('calculateImageCost');
        $method->setAccessible(true);

        $tokens = 1000; // 1000 tokens = $0.007 base cost

        // Low resolution: base + $0.05
        $costLow = $method->invoke($this->service, $tokens, 'low');
        $this->assertEquals(0.057, $costLow);

        // Medium resolution: base + $0.10
        $costMedium = $method->invoke($this->service, $tokens, 'medium');
        $this->assertEquals(0.107, $costMedium);

        // High resolution: base + $0.20
        $costHigh = $method->invoke($this->service, $tokens, 'high');
        $this->assertEquals(0.207, $costHigh);

        // Verify high costs more than medium costs more than low
        $this->assertGreaterThan($costMedium, $costHigh);
        $this->assertGreaterThan($costLow, $costMedium);
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'message' => 'API quota exceeded',
                    'code' => 429
                ]
            ], 429)
        ]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/API error/i');

        $this->service->generateText('Test prompt');
    }

    /** @test */
    public function it_applies_safety_settings_to_requests()
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'Safe content']
                            ]
                        ]
                    ]
                ],
                'usageMetadata' => [
                    'totalTokenCount' => 100
                ]
            ], 200)
        ]);

        $this->service->generateText('Test prompt');

        Http::assertSent(function ($request) {
            $body = json_decode($request->body(), true);

            // Verify safety settings are present
            $this->assertArrayHasKey('safetySettings', $body);
            $this->assertIsArray($body['safetySettings']);
            $this->assertCount(4, $body['safetySettings']);

            // Verify each safety category
            $categories = array_column($body['safetySettings'], 'category');
            $this->assertContains('HARM_CATEGORY_HATE_SPEECH', $categories);
            $this->assertContains('HARM_CATEGORY_DANGEROUS_CONTENT', $categories);
            $this->assertContains('HARM_CATEGORY_SEXUALLY_EXPLICIT', $categories);
            $this->assertContains('HARM_CATEGORY_HARASSMENT', $categories);

            return true;
        });
    }
}
