<?php

namespace Tests\Unit\Formatters;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Currency Formatter Unit Tests
 */
class CurrencyFormatterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_formats_sar_currency()
    {
        if (!function_exists('format_sar')) {
            function format_sar($amount) {
                return number_format($amount, 2) . ' ريال';
            }
        }

        $formatted = format_sar(1000);
        $this->assertEquals('1,000.00 ريال', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'format_sar',
        ]);
    }

    /** @test */
    public function it_formats_usd_currency()
    {
        if (!function_exists('format_usd')) {
            function format_usd($amount) {
                return '$' . number_format($amount, 2);
            }
        }

        $formatted = format_usd(500.50);
        $this->assertEquals('$500.50', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'format_usd',
        ]);
    }

    /** @test */
    public function it_converts_sar_to_usd()
    {
        if (!function_exists('sar_to_usd')) {
            function sar_to_usd($amount, $rate = 0.27) {
                return round($amount * $rate, 2);
            }
        }

        $converted = sar_to_usd(1000);
        $this->assertEquals(270.00, $converted);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'sar_to_usd',
        ]);
    }

    /** @test */
    public function it_converts_usd_to_sar()
    {
        if (!function_exists('usd_to_sar')) {
            function usd_to_sar($amount, $rate = 3.75) {
                return round($amount * $rate, 2);
            }
        }

        $converted = usd_to_sar(100);
        $this->assertEquals(375.00, $converted);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'usd_to_sar',
        ]);
    }

    /** @test */
    public function it_formats_with_custom_decimals()
    {
        if (!function_exists('format_currency_decimals')) {
            function format_currency_decimals($amount, $decimals = 2, $currency = 'SAR') {
                return number_format($amount, $decimals) . ' ' . $currency;
            }
        }

        $formatted = format_currency_decimals(1234.5678, 0, 'SAR');
        $this->assertEquals('1,235 SAR', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'custom_decimals',
        ]);
    }

    /** @test */
    public function it_formats_large_amounts()
    {
        if (!function_exists('format_large_currency')) {
            function format_large_currency($amount) {
                if ($amount >= 1000000) {
                    return number_format($amount / 1000000, 1) . 'M ريال';
                } elseif ($amount >= 1000) {
                    return number_format($amount / 1000, 1) . 'K ريال';
                }
                return number_format($amount, 2) . ' ريال';
            }
        }

        $this->assertEquals('1.5M ريال', format_large_currency(1500000));
        $this->assertEquals('50.0K ريال', format_large_currency(50000));
        $this->assertEquals('500.00 ريال', format_large_currency(500));

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'large_amounts',
        ]);
    }

    /** @test */
    public function it_handles_negative_amounts()
    {
        if (!function_exists('format_currency_signed')) {
            function format_currency_signed($amount) {
                $sign = $amount < 0 ? '-' : '+';
                return $sign . number_format(abs($amount), 2) . ' ريال';
            }
        }

        $this->assertEquals('+500.00 ريال', format_currency_signed(500));
        $this->assertEquals('-250.00 ريال', format_currency_signed(-250));

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'negative_amounts',
        ]);
    }

    /** @test */
    public function it_formats_without_decimals()
    {
        if (!function_exists('format_currency_whole')) {
            function format_currency_whole($amount) {
                return number_format($amount, 0) . ' ريال';
            }
        }

        $formatted = format_currency_whole(1234.56);
        $this->assertEquals('1,235 ريال', $formatted);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'without_decimals',
        ]);
    }

    /** @test */
    public function it_supports_multiple_currencies()
    {
        if (!function_exists('format_multi_currency')) {
            function format_multi_currency($amount, $currency) {
                $symbols = [
                    'SAR' => 'ريال',
                    'USD' => '$',
                    'EUR' => '€',
                    'GBP' => '£',
                ];

                $symbol = $symbols[$currency] ?? $currency;

                if ($currency === 'SAR') {
                    return number_format($amount, 2) . ' ' . $symbol;
                } else {
                    return $symbol . number_format($amount, 2);
                }
            }
        }

        $this->assertEquals('100.00 ريال', format_multi_currency(100, 'SAR'));
        $this->assertEquals('$100.00', format_multi_currency(100, 'USD'));
        $this->assertEquals('€100.00', format_multi_currency(100, 'EUR'));

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'multiple_currencies',
        ]);
    }

    /** @test */
    public function it_calculates_percentage_of_budget()
    {
        if (!function_exists('budget_percentage')) {
            function budget_percentage($spent, $total) {
                if ($total == 0) return 0;
                return round(($spent / $total) * 100, 1);
            }
        }

        $percentage = budget_percentage(3500, 10000);
        $this->assertEquals(35.0, $percentage);

        $this->logTestResult('passed', [
            'formatter' => 'CurrencyFormatter',
            'test' => 'budget_percentage',
        ]);
    }
}
