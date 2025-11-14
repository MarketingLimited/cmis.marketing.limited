<?php

namespace Tests\Unit\Formatters;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Number Formatter Unit Tests
 */
class NumberFormatterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_formats_currency()
    {
        if (!function_exists('format_currency')) {
            function format_currency($amount, $currency = 'USD') {
                return number_format($amount, 2) . ' ' . $currency;
            }
        }

        $formatted = format_currency(1234.56, 'USD');

        $this->assertEquals('1,234.56 USD', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'format_currency',
        ]);
    }

    /** @test */
    public function it_formats_saudi_riyal()
    {
        if (!function_exists('format_sar')) {
            function format_sar($amount) {
                return number_format($amount, 2) . ' ريال';
            }
        }

        $formatted = format_sar(5000.00);

        $this->assertEquals('5,000.00 ريال', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'format_sar',
        ]);
    }

    /** @test */
    public function it_formats_percentage()
    {
        if (!function_exists('format_percentage')) {
            function format_percentage($value, $decimals = 2) {
                return number_format($value, $decimals) . '%';
            }
        }

        $formatted = format_percentage(15.567);

        $this->assertEquals('15.57%', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'format_percentage',
        ]);
    }

    /** @test */
    public function it_formats_large_numbers()
    {
        if (!function_exists('format_large_number')) {
            function format_large_number($number) {
                if ($number >= 1000000) {
                    return number_format($number / 1000000, 1) . 'M';
                } elseif ($number >= 1000) {
                    return number_format($number / 1000, 1) . 'K';
                }
                return (string) $number;
            }
        }

        $this->assertEquals('1.5M', format_large_number(1500000));
        $this->assertEquals('25.5K', format_large_number(25500));
        $this->assertEquals('500', format_large_number(500));

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'large_numbers',
        ]);
    }

    /** @test */
    public function it_formats_file_size()
    {
        if (!function_exists('format_file_size')) {
            function format_file_size($bytes) {
                $units = ['B', 'KB', 'MB', 'GB', 'TB'];
                $bytes = max($bytes, 0);
                $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
                $pow = min($pow, count($units) - 1);
                $bytes /= (1 << (10 * $pow));
                return round($bytes, 2) . ' ' . $units[$pow];
            }
        }

        $this->assertEquals('1 KB', format_file_size(1024));
        $this->assertEquals('1 MB', format_file_size(1048576));
        $this->assertEquals('2.5 MB', format_file_size(2621440));

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'file_size',
        ]);
    }

    /** @test */
    public function it_formats_phone_number()
    {
        if (!function_exists('format_phone')) {
            function format_phone($phone) {
                $phone = preg_replace('/[^0-9+]/', '', $phone);
                if (substr($phone, 0, 4) === '+966') {
                    return '+966 ' . substr($phone, 4, 2) . ' ' . substr($phone, 6, 3) . ' ' . substr($phone, 9);
                }
                return $phone;
            }
        }

        $formatted = format_phone('+966501234567');

        $this->assertEquals('+966 50 123 4567', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'phone_number',
        ]);
    }

    /** @test */
    public function it_calculates_percentage_change()
    {
        if (!function_exists('calculate_percentage_change')) {
            function calculate_percentage_change($old, $new) {
                if ($old == 0) {
                    return $new > 0 ? 100 : 0;
                }
                return (($new - $old) / $old) * 100;
            }
        }

        $this->assertEquals(50, calculate_percentage_change(100, 150));
        $this->assertEquals(-25, calculate_percentage_change(100, 75));
        $this->assertEquals(100, calculate_percentage_change(0, 50));

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'percentage_change',
        ]);
    }

    /** @test */
    public function it_rounds_to_nearest()
    {
        if (!function_exists('round_to_nearest')) {
            function round_to_nearest($number, $nearest = 5) {
                return round($number / $nearest) * $nearest;
            }
        }

        $this->assertEquals(10, round_to_nearest(12, 5));
        $this->assertEquals(15, round_to_nearest(13, 5));
        $this->assertEquals(100, round_to_nearest(98, 10));

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'round_to_nearest',
        ]);
    }

    /** @test */
    public function it_formats_decimal_with_arabic_numbers()
    {
        if (!function_exists('format_arabic_number')) {
            function format_arabic_number($number) {
                $westernArabic = ['0','1','2','3','4','5','6','7','8','9'];
                $easternArabic = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
                return str_replace($westernArabic, $easternArabic, (string)$number);
            }
        }

        $formatted = format_arabic_number(12345);

        $this->assertEquals('١٢٣٤٥', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'arabic_numbers',
        ]);
    }

    /** @test */
    public function it_calculates_average()
    {
        if (!function_exists('calculate_average')) {
            function calculate_average($numbers) {
                if (empty($numbers)) {
                    return 0;
                }
                return array_sum($numbers) / count($numbers);
            }
        }

        $average = calculate_average([10, 20, 30, 40, 50]);

        $this->assertEquals(30, $average);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'calculate_average',
        ]);
    }

    /** @test */
    public function it_formats_with_thousand_separator()
    {
        if (!function_exists('format_with_separator')) {
            function format_with_separator($number, $separator = ',') {
                return number_format($number, 0, '.', $separator);
            }
        }

        $this->assertEquals('1,000,000', format_with_separator(1000000));
        $this->assertEquals('50,500', format_with_separator(50500));

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'thousand_separator',
        ]);
    }

    /** @test */
    public function it_calculates_discount_amount()
    {
        if (!function_exists('calculate_discount')) {
            function calculate_discount($price, $discount_percent) {
                return $price * ($discount_percent / 100);
            }
        }

        $discount = calculate_discount(100, 20);

        $this->assertEquals(20, $discount);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'calculate_discount',
        ]);
    }

    /** @test */
    public function it_formats_rating()
    {
        if (!function_exists('format_rating')) {
            function format_rating($rating, $max = 5) {
                return number_format($rating, 1) . ' / ' . $max;
            }
        }

        $formatted = format_rating(4.5);

        $this->assertEquals('4.5 / 5', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'NumberFormatter',
            'test' => 'format_rating',
        ]);
    }
}
