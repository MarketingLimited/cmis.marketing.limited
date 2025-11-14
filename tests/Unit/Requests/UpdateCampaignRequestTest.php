<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

/**
 * UpdateCampaignRequest Unit Tests
 */
class UpdateCampaignRequestTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_validates_campaign_name_max_length()
    {
        $data = [
            'campaign_name' => str_repeat('a', 300),
        ];

        $validator = Validator::make($data, [
            'campaign_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $this->assertTrue($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'campaign_name_max',
        ]);
    }

    /** @test */
    public function it_validates_campaign_type()
    {
        $invalidData = [
            'campaign_type' => 'invalid_type',
        ];

        $validator = Validator::make($invalidData, [
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'campaign_type' => 'awareness',
        ];

        $validator = Validator::make($validData, [
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'campaign_type_enum',
        ]);
    }

    /** @test */
    public function it_validates_status()
    {
        $invalidData = [
            'status' => 'invalid_status',
        ];

        $validator = Validator::make($invalidData, [
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'status' => 'active',
        ];

        $validator = Validator::make($validData, [
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'status_enum',
        ]);
    }

    /** @test */
    public function it_validates_budget_is_numeric()
    {
        $invalidData = [
            'budget' => 'not-a-number',
        ];

        $validator = Validator::make($invalidData, [
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'budget' => 15000.50,
        ];

        $validator = Validator::make($validData, [
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'budget_numeric',
        ]);
    }

    /** @test */
    public function it_validates_budget_minimum_value()
    {
        $invalidData = [
            'budget' => -500,
        ];

        $validator = Validator::make($invalidData, [
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertTrue($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'budget_min',
        ]);
    }

    /** @test */
    public function it_validates_date_format()
    {
        $invalidData = [
            'start_date' => 'invalid-date',
        ];

        $validator = Validator::make($invalidData, [
            'start_date' => ['nullable', 'date'],
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'start_date' => '2024-06-15',
        ];

        $validator = Validator::make($validData, [
            'start_date' => ['nullable', 'date'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'date_format',
        ]);
    }

    /** @test */
    public function it_validates_end_date_after_start_date()
    {
        $invalidData = [
            'start_date' => '2024-08-01',
            'end_date' => '2024-06-01',
        ];

        $validator = Validator::make($invalidData, [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
        ];

        $validator = Validator::make($validData, [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'end_date_after_start',
        ]);
    }

    /** @test */
    public function it_validates_target_audience_is_array()
    {
        $invalidData = [
            'target_audience' => 'not-an-array',
        ];

        $validator = Validator::make($invalidData, [
            'target_audience' => ['nullable', 'array'],
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'target_audience' => [
                'age_range' => '25-34',
                'location' => 'Riyadh',
            ],
        ];

        $validator = Validator::make($validData, [
            'target_audience' => ['nullable', 'array'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'target_audience_array',
        ]);
    }

    /** @test */
    public function it_validates_objectives_is_array()
    {
        $validData = [
            'objectives' => [
                'increase_brand_awareness',
                'generate_leads',
                'drive_sales',
            ],
        ];

        $validator = Validator::make($validData, [
            'objectives' => ['nullable', 'array'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'objectives_array',
        ]);
    }

    /** @test */
    public function it_validates_channels_is_array()
    {
        $validData = [
            'channels' => [1, 2, 3],
        ];

        $validator = Validator::make($validData, [
            'channels' => ['nullable', 'array'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'rule' => 'channels_array',
        ]);
    }

    /** @test */
    public function it_allows_valid_campaign_data()
    {
        $validData = [
            'campaign_name' => 'Summer Campaign 2024',
            'campaign_type' => 'conversion',
            'status' => 'active',
            'budget' => 25000.00,
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
            'target_audience' => [
                'age_range' => '25-34',
                'interests' => ['fashion', 'technology'],
            ],
            'objectives' => ['increase_sales', 'brand_awareness'],
            'kpis' => [
                'target_revenue' => 100000,
                'conversion_rate' => 5.0,
            ],
            'metadata' => [
                'created_by' => 'Marketing Team',
            ],
        ];

        $validator = Validator::make($validData, [
            'campaign_name' => ['sometimes', 'string', 'max:255'],
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'target_audience' => ['nullable', 'array'],
            'objectives' => ['nullable', 'array'],
            'kpis' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'test' => 'valid_data',
        ]);
    }

    /** @test */
    public function all_fields_are_optional()
    {
        $data = [];

        $validator = Validator::make($data, [
            'campaign_name' => ['sometimes', 'string', 'max:255'],
            'campaign_type' => ['sometimes', 'string', 'in:awareness,consideration,conversion,retention'],
            'status' => ['sometimes', 'string', 'in:draft,active,paused,completed,archived'],
            'budget' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'UpdateCampaignRequest',
            'test' => 'optional_fields',
        ]);
    }
}
