<?php

namespace Tests\Unit\Validators;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Validators\LeadValidator;
use Illuminate\Support\Facades\Validator;

use PHPUnit\Framework\Attributes\Test;
/**
 * Lead Validator Unit Tests
 */
class LeadValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected LeadValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new LeadValidator();
    }

    #[Test]
    public function it_validates_required_name()
    {
        $data = [
            'email' => 'lead@example.com',
            'source' => 'website',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('name'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'required_name',
        ]);
    }

    #[Test]
    public function it_validates_email_format()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'invalid-email',
            'source' => 'website',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('email'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'email_format',
        ]);
    }

    #[Test]
    public function it_validates_phone_format()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'phone' => 'abc123', // Invalid phone
            'source' => 'website',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('phone'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'phone_format',
        ]);
    }

    #[Test]
    public function it_validates_source_enum()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'source' => 'invalid_source',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('source'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'source_enum',
        ]);
    }

    #[Test]
    public function it_accepts_valid_sources()
    {
        $validSources = ['website', 'facebook', 'instagram', 'google_ads', 'linkedin', 'referral'];

        foreach ($validSources as $source) {
            $data = [
                'name' => 'Test Lead',
                'email' => 'lead@example.com',
                'source' => $source,
            ];

            $rules = $this->validator->rules();
            $v = Validator::make($data, $rules);

            $this->assertFalse($v->fails(), "Source {$source} should be valid");
        }

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'valid_sources',
        ]);
    }

    #[Test]
    public function it_validates_status_enum()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'source' => 'website',
            'status' => 'invalid_status',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('status'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'status_enum',
        ]);
    }

    #[Test]
    public function it_validates_score_range()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'source' => 'website',
            'score' => 150, // Out of range (0-100)
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('score'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'score_range',
        ]);
    }

    #[Test]
    public function it_validates_custom_fields_as_array()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'source' => 'website',
            'custom_fields' => 'not-an-array',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('custom_fields'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'custom_fields_array',
        ]);
    }

    #[Test]
    public function it_validates_utm_parameters()
    {
        $data = [
            'name' => 'Test Lead',
            'email' => 'lead@example.com',
            'source' => 'website',
            'utm_params' => 'not-an-array',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('utm_params'));

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'utm_params',
        ]);
    }

    #[Test]
    public function it_accepts_valid_lead_data()
    {
        $data = [
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '+966501234567',
            'source' => 'facebook',
            'status' => 'new',
            'score' => 75,
            'custom_fields' => [
                'company' => 'شركة التسويق',
                'industry' => 'تكنولوجيا',
            ],
            'utm_params' => [
                'utm_source' => 'facebook',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'summer_sale',
            ],
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertFalse($v->fails());

        $this->logTestResult('passed', [
            'validator' => 'LeadValidator',
            'test' => 'valid_data',
        ]);
    }
}
