<?php

namespace Tests\Unit\Validators;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;

use PHPUnit\Framework\Attributes\Test;
/**
 * Campaign Validator Unit Tests
 */
class CampaignValidatorTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_validates_campaign_name_is_required()
    {
        $data = [
            'description' => 'Campaign description',
            'status' => 'draft',
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'status' => 'required|in:draft,active,paused,completed',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'name_required',
        ]);
    }

    #[Test]
    public function it_validates_campaign_name_max_length()
    {
        $data = [
            'name' => str_repeat('a', 300),
            'status' => 'draft',
        ];

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'name_max_length',
        ]);
    }

    #[Test]
    public function it_validates_status_values()
    {
        $invalidData = [
            'name' => 'Test Campaign',
            'status' => 'invalid_status',
        ];

        $validator = Validator::make($invalidData, [
            'status' => 'required|in:draft,active,paused,completed',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'name' => 'Test Campaign',
            'status' => 'active',
        ];

        $validator = Validator::make($validData, [
            'status' => 'required|in:draft,active,paused,completed',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'status_enum',
        ]);
    }

    #[Test]
    public function it_validates_date_format()
    {
        $invalidData = [
            'name' => 'Test Campaign',
            'start_date' => 'invalid-date',
        ];

        $validator = Validator::make($invalidData, [
            'start_date' => 'nullable|date',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'name' => 'Test Campaign',
            'start_date' => '2024-06-01',
        ];

        $validator = Validator::make($validData, [
            'start_date' => 'nullable|date',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'date_format',
        ]);
    }

    #[Test]
    public function it_validates_end_date_after_start_date()
    {
        $invalidData = [
            'name' => 'Test Campaign',
            'start_date' => '2024-08-01',
            'end_date' => '2024-06-01',
        ];

        $validator = Validator::make($invalidData, [
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'name' => 'Test Campaign',
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
        ];

        $validator = Validator::make($validData, [
            'end_date' => 'nullable|date|after:start_date',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'end_date_after_start',
        ]);
    }

    #[Test]
    public function it_validates_budget_is_numeric()
    {
        $invalidData = [
            'name' => 'Test Campaign',
            'budget' => 'not-a-number',
        ];

        $validator = Validator::make($invalidData, [
            'budget' => 'nullable|numeric|min:0',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'name' => 'Test Campaign',
            'budget' => 10000.50,
        ];

        $validator = Validator::make($validData, [
            'budget' => 'nullable|numeric|min:0',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'budget_numeric',
        ]);
    }

    #[Test]
    public function it_validates_budget_minimum_value()
    {
        $invalidData = [
            'name' => 'Test Campaign',
            'budget' => -100,
        ];

        $validator = Validator::make($invalidData, [
            'budget' => 'nullable|numeric|min:0',
        ]);

        $this->assertTrue($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'budget_min_value',
        ]);
    }

    #[Test]
    public function it_validates_campaign_type()
    {
        $invalidData = [
            'name' => 'Test Campaign',
            'type' => 'invalid_type',
        ];

        $validator = Validator::make($invalidData, [
            'type' => 'nullable|in:awareness,consideration,conversion',
        ]);

        $this->assertTrue($validator->fails());

        $validData = [
            'name' => 'Test Campaign',
            'type' => 'conversion',
        ];

        $validator = Validator::make($validData, [
            'type' => 'nullable|in:awareness,consideration,conversion',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'rule' => 'type_enum',
        ]);
    }

    #[Test]
    public function it_allows_valid_campaign_data()
    {
        $validData = [
            'name' => 'Summer Sale Campaign',
            'description' => 'Promotional campaign for summer',
            'status' => 'active',
            'type' => 'conversion',
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
            'budget' => 15000.00,
        ];

        $validator = Validator::make($validData, [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,paused,completed',
            'type' => 'nullable|in:awareness,consideration,conversion',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
        ]);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'validator' => 'CampaignValidator',
            'test' => 'valid_data',
        ]);
    }
}
