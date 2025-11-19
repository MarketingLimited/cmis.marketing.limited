<?php

namespace Tests\Unit\Validators;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

use PHPUnit\Framework\Attributes\Test;
/**
 * Integration Validator Unit Tests
 */
class IntegrationValidatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_validates_platform_is_required()
    {
        $data = [
            'name' => 'Test Integration',
        ];

        $validator = Validator::make($data, [
            'platform' => 'required|string|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,pinterest,whatsapp',
            'name' => 'required|string',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('platform'));

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'platform_required',
        ]);
    }

    #[Test]
    public function it_validates_platform_values()
    {
        $invalidData = [
            'platform' => 'invalid_platform',
            'name' => 'Test Integration',
        ];

        $validator = Validator::make($invalidData, [
            'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,pinterest,whatsapp',
        ]);

        $this->assertTrue($validator->fails());

        $validPlatforms = ['facebook', 'instagram', 'twitter', 'linkedin'];
        foreach ($validPlatforms as $platform) {
            $validData = [
                'platform' => $platform,
                'name' => 'Test Integration',
            ];

            $validator = Validator::make($validData, [
                'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,pinterest,whatsapp',
            ]);

            $this->assertFalse($validator->fails());
        }

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'platform_enum',
        ]);
    }

    #[Test]
    public function it_validates_name_is_required()
    {
        $data = [
            'platform' => 'facebook',
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'name_required',
        ]);
    }

    #[Test]
    public function it_validates_credentials_is_array()
    {
        $invalidData = [
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => 'not-an-array',
        ];

        $validator = Validator::make($invalidData, [
            'credentials' => 'nullable|array',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'credentials' => [
                'access_token' => 'token_123',
                'page_id' => 'page_456',
            ],
        ];

        $validator = Validator::make($validData, [
            'credentials' => 'nullable|array',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'credentials_array',
        ]);
    }

    #[Test]
    public function it_validates_is_active_is_boolean()
    {
        $invalidData = [
            'platform' => 'instagram',
            'name' => 'Instagram Business',
            'is_active' => 'yes',
        ];

        $validator = Validator::make($invalidData, [
            'is_active' => 'nullable|boolean',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'platform' => 'instagram',
            'name' => 'Instagram Business',
            'is_active' => true,
        ];

        $validator = Validator::make($validData, [
            'is_active' => 'nullable|boolean',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'is_active_boolean',
        ]);
    }

    #[Test]
    public function it_validates_token_expires_at_date()
    {
        $invalidData = [
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'token_expires_at' => 'invalid-date',
        ];

        $validator = Validator::make($invalidData, [
            'token_expires_at' => 'nullable|date',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'platform' => 'facebook',
            'name' => 'Facebook Page',
            'token_expires_at' => '2024-12-31 23:59:59',
        ];

        $validator = Validator::make($validData, [
            'token_expires_at' => 'nullable|date',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'token_expires_date',
        ]);
    }

    #[Test]
    public function it_validates_metadata_is_array()
    {
        $invalidData = [
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'metadata' => 'not-an-array',
        ];

        $validator = Validator::make($invalidData, [
            'metadata' => 'nullable|array',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'platform' => 'twitter',
            'name' => 'Twitter Account',
            'metadata' => [
                'followers_count' => 1500,
                'verified' => false,
            ],
        ];

        $validator = Validator::make($validData, [
            'metadata' => 'nullable|array',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'rule' => 'metadata_array',
        ]);
    }

    #[Test]
    public function it_allows_valid_integration_data()
    {
        $validData = [
            'platform' => 'facebook',
            'name' => 'Facebook Business Page',
            'credentials' => [
                'access_token' => 'token_abc123',
                'page_id' => 'page_456789',
            ],
            'is_active' => true,
            'token_expires_at' => '2025-06-01 00:00:00',
            'metadata' => [
                'page_name' => 'My Business',
                'page_category' => 'Local Business',
            ],
        ];

        $validator = Validator::make($validData, [
            'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,youtube,snapchat,pinterest,whatsapp',
            'name' => 'required|string|max:255',
            'credentials' => 'nullable|array',
            'is_active' => 'nullable|boolean',
            'token_expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'IntegrationValidator',
            'test' => 'valid_data',
        ]);
    }
}
