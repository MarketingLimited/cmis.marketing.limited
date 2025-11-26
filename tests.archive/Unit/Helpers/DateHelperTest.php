<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

use PHPUnit\Framework\Attributes\Test;
/**
 * Date Helper Unit Tests
 */
class DateHelperTest extends TestCase
{
    #[Test]
    public function it_formats_date_for_display()
    {
        $date = '2024-06-15';
        $formatted = date_format_display($date);

        $this->assertIsString($formatted);

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'date_format_display',
        ]);
    }

    #[Test]
    public function it_calculates_days_difference()
    {
        $startDate = '2024-06-01';
        $endDate = '2024-06-30';

        $days = days_between($startDate, $endDate);

        $this->assertEquals(29, $days);

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'days_between',
        ]);
    }

    #[Test]
    public function it_checks_if_date_is_past()
    {
        $pastDate = now()->subDays(5)->toDateString();
        $futureDate = now()->addDays(5)->toDateString();

        $this->assertTrue(is_past_date($pastDate));
        $this->assertFalse(is_past_date($futureDate));

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'is_past_date',
        ]);
    }

    #[Test]
    public function it_checks_if_date_is_future()
    {
        $futureDate = now()->addDays(5)->toDateString();
        $pastDate = now()->subDays(5)->toDateString();

        $this->assertTrue(is_future_date($futureDate));
        $this->assertFalse(is_future_date($pastDate));

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'is_future_date',
        ]);
    }

    #[Test]
    public function it_gets_start_of_month()
    {
        $date = '2024-06-15';
        $startOfMonth = start_of_month($date);

        $this->assertEquals('2024-06-01', $startOfMonth);

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'start_of_month',
        ]);
    }

    #[Test]
    public function it_gets_end_of_month()
    {
        $date = '2024-06-15';
        $endOfMonth = end_of_month($date);

        $this->assertEquals('2024-06-30', $endOfMonth);

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'end_of_month',
        ]);
    }

    #[Test]
    public function it_formats_time_ago()
    {
        $recentDate = now()->subMinutes(5);
        $formatted = time_ago($recentDate);

        $this->assertStringContainsString('minutes', $formatted);

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'time_ago',
        ]);
    }

    #[Test]
    public function it_gets_date_range_array()
    {
        $startDate = '2024-06-01';
        $endDate = '2024-06-05';

        $range = date_range_array($startDate, $endDate);

        $this->assertCount(5, $range);
        $this->assertEquals('2024-06-01', $range[0]);
        $this->assertEquals('2024-06-05', $range[4]);

        $this->logTestResult('passed', [
            'helper' => 'DateHelper',
            'function' => 'date_range_array',
        ]);
    }
}

// Helper function implementations (would normally be in app/Helpers/DateHelper.php)
if (!function_exists('date_format_display')) {
    function date_format_display($date) {
        return \Carbon\Carbon::parse($date)->format('Y-m-d');
    }
}

if (!function_exists('days_between')) {
    function days_between($start, $end) {
        return \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));
    }
}

if (!function_exists('is_past_date')) {
    function is_past_date($date) {
        return \Carbon\Carbon::parse($date)->isPast();
    }
}

if (!function_exists('is_future_date')) {
    function is_future_date($date) {
        return \Carbon\Carbon::parse($date)->isFuture();
    }
}

if (!function_exists('start_of_month')) {
    function start_of_month($date) {
        return \Carbon\Carbon::parse($date)->startOfMonth()->toDateString();
    }
}

if (!function_exists('end_of_month')) {
    function end_of_month($date) {
        return \Carbon\Carbon::parse($date)->endOfMonth()->toDateString();
    }
}

if (!function_exists('time_ago')) {
    function time_ago($date) {
        return \Carbon\Carbon::parse($date)->diffForHumans();
    }
}

if (!function_exists('date_range_array')) {
    function date_range_array($start, $end) {
        $dates = [];
        $current = \Carbon\Carbon::parse($start);
        $endDate = \Carbon\Carbon::parse($end);

        while ($current->lte($endDate)) {
            $dates[] = $current->toDateString();
            $current->addDay();
        }

        return $dates;
    }
}
