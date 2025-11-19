<?php

namespace Tests\Unit\Validators;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Validators\ContentValidator;
use Illuminate\Support\Facades\Validator;

/**
 * Content Validator Unit Tests
 */
class ContentValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected ContentValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ContentValidator();
    }

    /** @test */
    public function it_validates_required_title()
    {
        $data = [
            'body' => 'Content body',
            'status' => 'draft',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('title'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'required_title',
        ]);
    }

    /** @test */
    public function it_validates_title_max_length()
    {
        $data = [
            'title' => str_repeat('a', 300), // Too long
            'body' => 'Content body',
            'status' => 'draft',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('title'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'title_max_length',
        ]);
    }

    /** @test */
    public function it_validates_required_body()
    {
        $data = [
            'title' => 'Test Title',
            'status' => 'draft',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('body'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'required_body',
        ]);
    }

    /** @test */
    public function it_validates_status_enum()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Content body',
            'status' => 'invalid_status',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('status'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'status_enum',
        ]);
    }

    /** @test */
    public function it_accepts_valid_statuses()
    {
        $validStatuses = ['draft', 'published', 'archived', 'scheduled'];

        foreach ($validStatuses as $status) {
            $data = [
                'title' => 'Test Title',
                'body' => 'Content body',
                'status' => $status,
            ];

            $rules = $this->validator->rules();
            $v = Validator::make($data, $rules);

            $this->assertFalse($v->fails(), "Status {$status} should be valid");
        }

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'valid_statuses',
        ]);
    }

    /** @test */
    public function it_validates_scheduled_date_format()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Content body',
            'status' => 'scheduled',
            'scheduled_at' => 'invalid-date',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('scheduled_at'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'scheduled_date_format',
        ]);
    }

    /** @test */
    public function it_validates_scheduled_date_in_future()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Content body',
            'status' => 'scheduled',
            'scheduled_at' => now()->subDays(5)->toDateTimeString(),
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('scheduled_at'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'scheduled_future_date',
        ]);
    }

    /** @test */
    public function it_validates_media_urls_as_array()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Content body',
            'status' => 'draft',
            'media_urls' => 'not-an-array',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('media_urls'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'media_urls_array',
        ]);
    }

    /** @test */
    public function it_validates_media_urls_format()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Content body',
            'status' => 'draft',
            'media_urls' => ['not-a-url', 'also-not-url'],
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('media_urls.0'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'media_urls_format',
        ]);
    }

    /** @test */
    public function it_validates_tags_as_array()
    {
        $data = [
            'title' => 'Test Title',
            'body' => 'Content body',
            'status' => 'draft',
            'tags' => 'not-an-array',
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertTrue($v->fails());
        $this->assertTrue($v->errors()->has('tags'));

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'tags_array',
        ]);
    }

    /** @test */
    public function it_accepts_valid_content_data()
    {
        $data = [
            'title' => 'محتوى تجريبي',
            'body' => 'نص المحتوى هنا',
            'status' => 'draft',
            'media_urls' => [
                'https://example.com/image1.jpg',
                'https://example.com/image2.jpg',
            ],
            'tags' => ['marketing', 'social'],
        ];

        $rules = $this->validator->rules();
        $v = Validator::make($data, $rules);

        $this->assertFalse($v->fails());

        $this->logTestResult('passed', [
            'validator' => 'ContentValidator',
            'test' => 'valid_data',
        ]);
    }
}
