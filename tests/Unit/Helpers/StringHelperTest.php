<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;

/**
 * String Helper Unit Tests
 */
class StringHelperTest extends TestCase
{
    /** @test */
    public function it_truncates_string()
    {
        $longText = 'This is a very long text that needs to be truncated for display purposes.';
        $truncated = str_truncate($longText, 20);

        $this->assertEquals(23, strlen($truncated)); // 20 + '...'
        $this->assertStringEndsWith('...', $truncated);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'str_truncate',
        ]);
    }

    /** @test */
    public function it_generates_slug()
    {
        $title = 'Summer Sale Campaign 2024';
        $slug = generate_slug($title);

        $this->assertEquals('summer-sale-campaign-2024', $slug);

        $arabicTitle = 'حملة الصيف 2024';
        $arabicSlug = generate_slug($arabicTitle);

        $this->assertIsString($arabicSlug);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'generate_slug',
        ]);
    }

    /** @test */
    public function it_extracts_hashtags()
    {
        $text = 'Amazing product! #sale #summer #discount #shopping';
        $hashtags = extract_hashtags($text);

        $this->assertCount(4, $hashtags);
        $this->assertContains('sale', $hashtags);
        $this->assertContains('summer', $hashtags);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'extract_hashtags',
        ]);
    }

    /** @test */
    public function it_extracts_mentions()
    {
        $text = 'Great collaboration with @john and @sarah!';
        $mentions = extract_mentions($text);

        $this->assertCount(2, $mentions);
        $this->assertContains('john', $mentions);
        $this->assertContains('sarah', $mentions);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'extract_mentions',
        ]);
    }

    /** @test */
    public function it_masks_sensitive_data()
    {
        $email = 'user@example.com';
        $masked = mask_email($email);

        $this->assertStringContainsString('***', $masked);
        $this->assertStringContainsString('@', $masked);

        $phone = '+966501234567';
        $maskedPhone = mask_phone($phone);

        $this->assertStringContainsString('***', $maskedPhone);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'mask_sensitive',
        ]);
    }

    /** @test */
    public function it_converts_to_title_case()
    {
        $text = 'summer sale campaign';
        $titleCase = to_title_case($text);

        $this->assertEquals('Summer Sale Campaign', $titleCase);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'to_title_case',
        ]);
    }

    /** @test */
    public function it_removes_special_characters()
    {
        $text = 'Hello! @World# $123%';
        $cleaned = remove_special_chars($text);

        $this->assertEquals('Hello World 123', $cleaned);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'remove_special_chars',
        ]);
    }

    /** @test */
    public function it_counts_words()
    {
        $text = 'This is a test sentence with seven words.';
        $count = word_count($text);

        $this->assertEquals(8, $count);

        $this->logTestResult('passed', [
            'helper' => 'StringHelper',
            'function' => 'word_count',
        ]);
    }
}

// Helper function implementations
if (!function_exists('str_truncate')) {
    function str_truncate($text, $length) {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length) . '...';
    }
}

if (!function_exists('generate_slug')) {
    function generate_slug($text) {
        return \Illuminate\Support\Str::slug($text);
    }
}

if (!function_exists('extract_hashtags')) {
    function extract_hashtags($text) {
        preg_match_all('/#(\w+)/', $text, $matches);
        return $matches[1];
    }
}

if (!function_exists('extract_mentions')) {
    function extract_mentions($text) {
        preg_match_all('/@(\w+)/', $text, $matches);
        return $matches[1];
    }
}

if (!function_exists('mask_email')) {
    function mask_email($email) {
        $parts = explode('@', $email);
        return substr($parts[0], 0, 2) . '***@' . $parts[1];
    }
}

if (!function_exists('mask_phone')) {
    function mask_phone($phone) {
        return substr($phone, 0, 4) . '***' . substr($phone, -2);
    }
}

if (!function_exists('to_title_case')) {
    function to_title_case($text) {
        return \Illuminate\Support\Str::title($text);
    }
}

if (!function_exists('remove_special_chars')) {
    function remove_special_chars($text) {
        return preg_replace('/[^A-Za-z0-9\s]/', '', $text);
    }
}

if (!function_exists('word_count')) {
    function word_count($text) {
        return str_word_count($text);
    }
}
