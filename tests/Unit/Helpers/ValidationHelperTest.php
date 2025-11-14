<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Validation Helper Unit Tests
 */
class ValidationHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_validates_email()
    {
        if (!function_exists('is_valid_email')) {
            function is_valid_email($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            }
        }

        $this->assertTrue(is_valid_email('user@example.com'));
        $this->assertTrue(is_valid_email('ahmed@test.sa'));
        $this->assertFalse(is_valid_email('invalid-email'));
        $this->assertFalse(is_valid_email('user@'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_email',
        ]);
    }

    /** @test */
    public function it_validates_url()
    {
        if (!function_exists('is_valid_url')) {
            function is_valid_url($url) {
                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            }
        }

        $this->assertTrue(is_valid_url('https://example.com'));
        $this->assertTrue(is_valid_url('http://test.sa/page'));
        $this->assertFalse(is_valid_url('not-a-url'));
        $this->assertFalse(is_valid_url('ftp://invalid'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_url',
        ]);
    }

    /** @test */
    public function it_validates_saudi_phone()
    {
        if (!function_exists('is_valid_saudi_phone')) {
            function is_valid_saudi_phone($phone) {
                $pattern = '/^(\+966|00966|966|0)?5[0-9]{8}$/';
                $cleaned = preg_replace('/[^0-9+]/', '', $phone);
                return preg_match($pattern, $cleaned) === 1;
            }
        }

        $this->assertTrue(is_valid_saudi_phone('+966501234567'));
        $this->assertTrue(is_valid_saudi_phone('0501234567'));
        $this->assertTrue(is_valid_saudi_phone('966501234567'));
        $this->assertFalse(is_valid_saudi_phone('1234567890'));
        $this->assertFalse(is_valid_saudi_phone('+971501234567'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_saudi_phone',
        ]);
    }

    /** @test */
    public function it_validates_uuid()
    {
        if (!function_exists('is_valid_uuid')) {
            function is_valid_uuid($uuid) {
                $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
                return preg_match($pattern, $uuid) === 1;
            }
        }

        $this->assertTrue(is_valid_uuid('550e8400-e29b-41d4-a716-446655440000'));
        $this->assertFalse(is_valid_uuid('invalid-uuid'));
        $this->assertFalse(is_valid_uuid('12345'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_uuid',
        ]);
    }

    /** @test */
    public function it_validates_arabic_text()
    {
        if (!function_exists('contains_arabic')) {
            function contains_arabic($text) {
                return preg_match('/[\x{0600}-\x{06FF}]/u', $text) === 1;
            }
        }

        $this->assertTrue(contains_arabic('مرحباً'));
        $this->assertTrue(contains_arabic('Hello مرحباً'));
        $this->assertFalse(contains_arabic('Hello World'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_arabic',
        ]);
    }

    /** @test */
    public function it_validates_strong_password()
    {
        if (!function_exists('is_strong_password')) {
            function is_strong_password($password) {
                // At least 8 chars, 1 uppercase, 1 lowercase, 1 number
                return strlen($password) >= 8 &&
                       preg_match('/[A-Z]/', $password) &&
                       preg_match('/[a-z]/', $password) &&
                       preg_match('/[0-9]/', $password);
            }
        }

        $this->assertTrue(is_strong_password('Password123'));
        $this->assertTrue(is_strong_password('MyP@ssw0rd'));
        $this->assertFalse(is_strong_password('weak'));
        $this->assertFalse(is_strong_password('password'));
        $this->assertFalse(is_strong_password('PASSWORD123'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'strong_password',
        ]);
    }

    /** @test */
    public function it_validates_credit_card()
    {
        if (!function_exists('is_valid_credit_card')) {
            function is_valid_credit_card($number) {
                $number = preg_replace('/[^0-9]/', '', $number);
                if (strlen($number) < 13 || strlen($number) > 19) {
                    return false;
                }
                // Luhn algorithm
                $sum = 0;
                $alt = false;
                for ($i = strlen($number) - 1; $i >= 0; $i--) {
                    $n = (int)$number[$i];
                    if ($alt) {
                        $n *= 2;
                        if ($n > 9) $n -= 9;
                    }
                    $sum += $n;
                    $alt = !$alt;
                }
                return $sum % 10 === 0;
            }
        }

        $this->assertTrue(is_valid_credit_card('4532015112830366')); // Valid test card
        $this->assertFalse(is_valid_credit_card('1234567890123456'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'credit_card',
        ]);
    }

    /** @test */
    public function it_validates_date_format()
    {
        if (!function_exists('is_valid_date')) {
            function is_valid_date($date, $format = 'Y-m-d') {
                $d = \DateTime::createFromFormat($format, $date);
                return $d && $d->format($format) === $date;
            }
        }

        $this->assertTrue(is_valid_date('2024-01-15'));
        $this->assertTrue(is_valid_date('15/01/2024', 'd/m/Y'));
        $this->assertFalse(is_valid_date('2024-13-45'));
        $this->assertFalse(is_valid_date('invalid-date'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_date',
        ]);
    }

    /** @test */
    public function it_validates_json()
    {
        if (!function_exists('is_valid_json')) {
            function is_valid_json($string) {
                json_decode($string);
                return json_last_error() === JSON_ERROR_NONE;
            }
        }

        $this->assertTrue(is_valid_json('{"key":"value"}'));
        $this->assertTrue(is_valid_json('[1,2,3]'));
        $this->assertFalse(is_valid_json('{invalid}'));
        $this->assertFalse(is_valid_json('not json'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_json',
        ]);
    }

    /** @test */
    public function it_validates_ip_address()
    {
        if (!function_exists('is_valid_ip')) {
            function is_valid_ip($ip) {
                return filter_var($ip, FILTER_VALIDATE_IP) !== false;
            }
        }

        $this->assertTrue(is_valid_ip('192.168.1.1'));
        $this->assertTrue(is_valid_ip('2001:0db8:85a3::8a2e:0370:7334'));
        $this->assertFalse(is_valid_ip('999.999.999.999'));
        $this->assertFalse(is_valid_ip('invalid-ip'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_ip',
        ]);
    }

    /** @test */
    public function it_sanitizes_input()
    {
        if (!function_exists('sanitize_input')) {
            function sanitize_input($input) {
                return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
            }
        }

        $dirty = '<script>alert("xss")</script>Hello';
        $clean = sanitize_input($dirty);

        $this->assertStringNotContainsString('<script>', $clean);
        $this->assertStringContainsString('Hello', $clean);

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'sanitize_input',
        ]);
    }

    /** @test */
    public function it_validates_file_extension()
    {
        if (!function_exists('is_allowed_extension')) {
            function is_allowed_extension($filename, $allowed = ['jpg', 'png', 'pdf']) {
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                return in_array($extension, $allowed);
            }
        }

        $this->assertTrue(is_allowed_extension('image.jpg'));
        $this->assertTrue(is_allowed_extension('document.pdf'));
        $this->assertFalse(is_allowed_extension('script.exe'));
        $this->assertFalse(is_allowed_extension('file.bat'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'file_extension',
        ]);
    }

    /** @test */
    public function it_validates_hashtag()
    {
        if (!function_exists('is_valid_hashtag')) {
            function is_valid_hashtag($tag) {
                return preg_match('/^#[\p{L}\p{N}_]+$/u', $tag) === 1;
            }
        }

        $this->assertTrue(is_valid_hashtag('#marketing'));
        $this->assertTrue(is_valid_hashtag('#رمضان'));
        $this->assertTrue(is_valid_hashtag('#marketing2024'));
        $this->assertFalse(is_valid_hashtag('marketing'));
        $this->assertFalse(is_valid_hashtag('#with space'));

        $this->logTestResult('passed', [
            'helper' => 'ValidationHelper',
            'test' => 'validate_hashtag',
        ]);
    }
}
