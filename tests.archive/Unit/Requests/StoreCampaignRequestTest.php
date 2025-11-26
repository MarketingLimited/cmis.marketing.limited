<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Http\Requests\Campaign\StoreCampaignRequest;
use Illuminate\Support\Facades\Validator;

use PHPUnit\Framework\Attributes\Test;
/**
 * Store Campaign Request Unit Tests
 */
class StoreCampaignRequestTest extends TestCase
{
    #[Test]
    public function it_validates_required_fields()
    {
        $request = new StoreCampaignRequest();
        $rules = $request->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        $this->logTestResult('passed', [
            'request' => 'StoreCampaignRequest',
            'test' => 'required_fields',
        ]);
    }

    #[Test]
    public function it_passes_with_valid_data()
    {
        $request = new StoreCampaignRequest();
        $rules = $request->rules();

        $data = [
            'name' => 'Test Campaign',
            'description' => 'Campaign description',
            'status' => 'draft',
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertFalse($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'StoreCampaignRequest',
            'test' => 'valid_data',
        ]);
    }

    #[Test]
    public function it_validates_name_max_length()
    {
        $request = new StoreCampaignRequest();
        $rules = $request->rules();

        $data = [
            'name' => str_repeat('a', 300),
            'status' => 'draft',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));

        $this->logTestResult('passed', [
            'request' => 'StoreCampaignRequest',
            'test' => 'name_max_length',
        ]);
    }

    #[Test]
    public function it_validates_status_enum()
    {
        $request = new StoreCampaignRequest();
        $rules = $request->rules();

        $data = [
            'name' => 'Test Campaign',
            'status' => 'invalid_status',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('status'));

        $this->logTestResult('passed', [
            'request' => 'StoreCampaignRequest',
            'test' => 'status_enum',
        ]);
    }

    #[Test]
    public function it_validates_dates()
    {
        $request = new StoreCampaignRequest();
        $rules = $request->rules();

        $data = [
            'name' => 'Test Campaign',
            'status' => 'draft',
            'start_date' => 'invalid-date',
        ];

        $validator = Validator::make($data, $rules);

        $this->assertTrue($validator->fails());

        $this->logTestResult('passed', [
            'request' => 'StoreCampaignRequest',
            'test' => 'date_validation',
        ]);
    }
}
