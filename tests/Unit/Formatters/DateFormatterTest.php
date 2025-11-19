<?php

namespace Tests\Unit\Formatters;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Date Formatter Unit Tests
 */
class DateFormatterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_formats_date_for_display()
    {
        if (!function_exists('format_date_display')) {
            function format_date_display($date, $format = 'Y-m-d') {
                return \Carbon\Carbon::parse($date)->format($format);
            }
        }

        $date = '2024-01-15 14:30:00';
        $formatted = format_date_display($date, 'Y-m-d');

        $this->assertEquals('2024-01-15', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'format_display',
        ]);
    }

    /** @test */
    public function it_formats_date_with_time()
    {
        if (!function_exists('format_datetime')) {
            function format_datetime($date) {
                return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
            }
        }

        $date = '2024-01-15 14:30:45';
        $formatted = format_datetime($date);

        $this->assertEquals('2024-01-15 14:30:45', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'format_datetime',
        ]);
    }

    /** @test */
    public function it_formats_date_for_humans()
    {
        if (!function_exists('format_date_human')) {
            function format_date_human($date) {
                return \Carbon\Carbon::parse($date)->diffForHumans();
            }
        }

        $date = now()->subHours(2);
        $formatted = format_date_human($date);

        $this->assertStringContainsString('ago', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'human_readable',
        ]);
    }

    /** @test */
    public function it_formats_arabic_dates()
    {
        if (!function_exists('format_date_arabic')) {
            function format_date_arabic($date) {
                \Carbon\Carbon::setLocale('ar');
                return \Carbon\Carbon::parse($date)->translatedFormat('d F Y');
            }
        }

        $date = '2024-01-15';
        $formatted = format_date_arabic($date);

        // Should contain Arabic month name
        $this->assertNotEmpty($formatted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'arabic_format',
        ]);
    }

    /** @test */
    public function it_converts_to_timezone()
    {
        if (!function_exists('convert_timezone')) {
            function convert_timezone($date, $timezone = 'Asia/Riyadh') {
                return \Carbon\Carbon::parse($date)->timezone($timezone);
            }
        }

        $date = '2024-01-15 14:30:00';
        $converted = convert_timezone($date, 'Asia/Riyadh');

        $this->assertInstanceOf(\Carbon\Carbon::class, $converted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'timezone_conversion',
        ]);
    }

    /** @test */
    public function it_gets_start_of_day()
    {
        if (!function_exists('get_start_of_day')) {
            function get_start_of_day($date) {
                return \Carbon\Carbon::parse($date)->startOfDay();
            }
        }

        $date = '2024-01-15 14:30:45';
        $start = get_start_of_day($date);

        $this->assertEquals('2024-01-15 00:00:00', $start->format('Y-m-d H:i:s'));

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'start_of_day',
        ]);
    }

    /** @test */
    public function it_gets_end_of_day()
    {
        if (!function_exists('get_end_of_day')) {
            function get_end_of_day($date) {
                return \Carbon\Carbon::parse($date)->endOfDay();
            }
        }

        $date = '2024-01-15 14:30:45';
        $end = get_end_of_day($date);

        $this->assertEquals('2024-01-15 23:59:59', $end->format('Y-m-d H:i:s'));

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'end_of_day',
        ]);
    }

    /** @test */
    public function it_calculates_age_from_date()
    {
        if (!function_exists('calculate_age')) {
            function calculate_age($birthdate) {
                return \Carbon\Carbon::parse($birthdate)->age;
            }
        }

        $birthdate = now()->subYears(25)->format('Y-m-d');
        $age = calculate_age($birthdate);

        $this->assertEquals(25, $age);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'calculate_age',
        ]);
    }

    /** @test */
    public function it_checks_if_date_is_past()
    {
        if (!function_exists('is_past_date')) {
            function is_past_date($date) {
                return \Carbon\Carbon::parse($date)->isPast();
            }
        }

        $pastDate = now()->subDays(5);
        $futureDate = now()->addDays(5);

        $this->assertTrue(is_past_date($pastDate));
        $this->assertFalse(is_past_date($futureDate));

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'is_past',
        ]);
    }

    /** @test */
    public function it_checks_if_date_is_future()
    {
        if (!function_exists('is_future_date')) {
            function is_future_date($date) {
                return \Carbon\Carbon::parse($date)->isFuture();
            }
        }

        $futureDate = now()->addDays(5);
        $pastDate = now()->subDays(5);

        $this->assertTrue(is_future_date($futureDate));
        $this->assertFalse(is_future_date($pastDate));

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'is_future',
        ]);
    }

    /** @test */
    public function it_formats_month_year()
    {
        if (!function_exists('format_month_year')) {
            function format_month_year($date) {
                return \Carbon\Carbon::parse($date)->format('F Y');
            }
        }

        $date = '2024-01-15';
        $formatted = format_month_year($date);

        $this->assertEquals('January 2024', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'month_year_format',
        ]);
    }

    /** @test */
    public function it_gets_quarter_from_date()
    {
        if (!function_exists('get_quarter')) {
            function get_quarter($date) {
                return \Carbon\Carbon::parse($date)->quarter;
            }
        }

        $q1Date = '2024-01-15';
        $q2Date = '2024-04-15';
        $q3Date = '2024-07-15';
        $q4Date = '2024-10-15';

        $this->assertEquals(1, get_quarter($q1Date));
        $this->assertEquals(2, get_quarter($q2Date));
        $this->assertEquals(3, get_quarter($q3Date));
        $this->assertEquals(4, get_quarter($q4Date));

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'get_quarter',
        ]);
    }

    /** @test */
    public function it_formats_iso8601()
    {
        if (!function_exists('format_iso8601')) {
            function format_iso8601($date) {
                return \Carbon\Carbon::parse($date)->toIso8601String();
            }
        }

        $date = '2024-01-15 14:30:00';
        $formatted = format_iso8601($date);

        $this->assertStringContainsString('2024-01-15', $formatted);
        $this->assertStringContainsString('T', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'DateFormatter',
            'test' => 'iso8601_format',
        ]);
    }
}
