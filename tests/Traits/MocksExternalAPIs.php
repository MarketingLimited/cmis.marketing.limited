<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

/**
 * Trait for mocking external API responses.
 */
trait MocksExternalAPIs
{
    /**
     * Mock Meta (Facebook/Instagram) API responses.
     *
     * @param string $type
     * @param array $customResponse
     * @return void
     */
    protected function mockMetaAPI(string $type = 'success', array $customResponse = []): void
    {
        $responses = [
            'success' => [
                'me' => [
                    'id' => '123456789',
                    'name' => 'Test Account',
                    'access_token' => 'test_token_123',
                ],
                'accounts' => [
                    'data' => [
                        [
                            'id' => '987654321',
                            'name' => 'Test Page',
                            'access_token' => 'page_token_123',
                            'category' => 'Business',
                        ],
                    ],
                ],
                'posts' => [
                    'data' => [
                        [
                            'id' => 'post_123',
                            'message' => 'Test post',
                            'created_time' => '2024-01-01T00:00:00+0000',
                            'likes' => ['summary' => ['total_count' => 100]],
                            'comments' => ['summary' => ['total_count' => 10]],
                        ],
                    ],
                    'paging' => [
                        'next' => null,
                    ],
                ],
                'insights' => [
                    'data' => [
                        [
                            'name' => 'page_impressions',
                            'period' => 'day',
                            'values' => [
                                ['value' => 1000, 'end_time' => '2024-01-01T00:00:00+0000'],
                            ],
                        ],
                    ],
                ],
            ],
            'error' => [
                'error' => [
                    'message' => 'Invalid OAuth access token.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ],
            'rate_limit' => [
                'error' => [
                    'message' => 'Application request limit reached',
                    'type' => 'OAuthException',
                    'code' => 4,
                ],
            ],
        ];

        $response = $customResponse ?: ($responses[$type] ?? $responses['success']);

        Http::fake([
            'graph.facebook.com/*' => Http::response($response, $type === 'error' ? 400 : 200),
            'graph.instagram.com/*' => Http::response($response, $type === 'error' ? 400 : 200),
        ]);
    }

    /**
     * Mock Google Ads API responses.
     *
     * @param string $type
     * @param array $customResponse
     * @return void
     */
    protected function mockGoogleAdsAPI(string $type = 'success', array $customResponse = []): void
    {
        $responses = [
            'success' => [
                'results' => [
                    [
                        'campaign' => [
                            'id' => '123456',
                            'name' => 'Test Campaign',
                            'status' => 'ENABLED',
                        ],
                        'metrics' => [
                            'impressions' => '10000',
                            'clicks' => '500',
                            'cost_micros' => '500000000',
                        ],
                    ],
                ],
            ],
            'error' => [
                'error' => [
                    'code' => 401,
                    'message' => 'Request is missing required authentication credential.',
                    'status' => 'UNAUTHENTICATED',
                ],
            ],
        ];

        $response = $customResponse ?: ($responses[$type] ?? $responses['success']);

        Http::fake([
            'googleads.googleapis.com/*' => Http::response($response, $type === 'error' ? 401 : 200),
        ]);
    }

    /**
     * Mock TikTok API responses.
     *
     * @param string $type
     * @param array $customResponse
     * @return void
     */
    protected function mockTikTokAPI(string $type = 'success', array $customResponse = []): void
    {
        $responses = [
            'success' => [
                'code' => 0,
                'message' => 'OK',
                'data' => [
                    'campaigns' => [
                        [
                            'campaign_id' => '123456',
                            'campaign_name' => 'Test Campaign',
                            'objective_type' => 'TRAFFIC',
                            'status' => 'ENABLE',
                        ],
                    ],
                ],
            ],
            'error' => [
                'code' => 40001,
                'message' => 'Invalid access token',
            ],
        ];

        $response = $customResponse ?: ($responses[$type] ?? $responses['success']);

        Http::fake([
            'business-api.tiktok.com/*' => Http::response($response, $type === 'error' ? 400 : 200),
        ]);
    }

    /**
     * Mock Gemini AI API responses.
     *
     * @param string $type
     * @param array $customResponse
     * @return void
     */
    protected function mockGeminiAPI(string $type = 'success', array $customResponse = []): void
    {
        $responses = [
            'success' => [
                'embedding' => [
                    'values' => array_fill(0, 768, 0.1),
                ],
            ],
            'batch_success' => [
                'embeddings' => [
                    ['values' => array_fill(0, 768, 0.1)],
                    ['values' => array_fill(0, 768, 0.2)],
                ],
            ],
            'error' => [
                'error' => [
                    'code' => 400,
                    'message' => 'Invalid request',
                    'status' => 'INVALID_ARGUMENT',
                ],
            ],
        ];

        $response = $customResponse ?: ($responses[$type] ?? $responses['success']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response($response, $type === 'error' ? 400 : 200),
        ]);
    }

    /**
     * Mock all external APIs with success responses.
     *
     * @return void
     */
    protected function mockAllAPIs(): void
    {
        $this->mockMetaAPI('success');
        $this->mockGoogleAdsAPI('success');
        $this->mockTikTokAPI('success');
        $this->mockGeminiAPI('success');
    }
}
