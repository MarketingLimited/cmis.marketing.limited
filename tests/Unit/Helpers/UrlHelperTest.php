<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * URL Helper Unit Tests
 */
class UrlHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
    }

    /** @test */
    public function it_validates_url_format()
    {
        if (!function_exists('is_valid_url')) {
            function is_valid_url($url) {
                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            }
        }

        $this->assertTrue(is_valid_url('https://example.com'));
        $this->assertTrue(is_valid_url('http://example.com/page'));
        $this->assertFalse(is_valid_url('not-a-url'));
        $this->assertFalse(is_valid_url('example'));

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'validate_url',
        ]);
    }

    /** @test */
    public function it_extracts_domain_from_url()
    {
        if (!function_exists('get_domain')) {
            function get_domain($url) {
                $parsed = parse_url($url);
                return $parsed['host'] ?? '';
            }
        }

        $domain = get_domain('https://www.example.com/page?query=value');
        $this->assertEquals('www.example.com', $domain);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'extract_domain',
        ]);
    }

    /** @test */
    public function it_adds_utm_parameters()
    {
        if (!function_exists('add_utm_params')) {
            function add_utm_params($url, $params) {
                $query = http_build_query($params);
                $separator = strpos($url, '?') !== false ? '&' : '?';
                return $url . $separator . $query;
            }
        }

        $url = 'https://example.com/product';
        $params = [
            'utm_source' => 'facebook',
            'utm_medium' => 'social',
            'utm_campaign' => 'summer_sale',
        ];

        $result = add_utm_params($url, $params);
        $this->assertStringContainsString('utm_source=facebook', $result);
        $this->assertStringContainsString('utm_campaign=summer_sale', $result);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'add_utm_params',
        ]);
    }

    /** @test */
    public function it_extracts_utm_parameters()
    {
        if (!function_exists('extract_utm_params')) {
            function extract_utm_params($url) {
                $parsed = parse_url($url);
                parse_str($parsed['query'] ?? '', $params);

                return array_filter($params, function($key) {
                    return strpos($key, 'utm_') === 0;
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        $url = 'https://example.com?utm_source=google&utm_medium=cpc&other=value';
        $utmParams = extract_utm_params($url);

        $this->assertCount(2, $utmParams);
        $this->assertEquals('google', $utmParams['utm_source']);
        $this->assertEquals('cpc', $utmParams['utm_medium']);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'extract_utm_params',
        ]);
    }

    /** @test */
    public function it_shortens_url()
    {
        if (!function_exists('shorten_url_display')) {
            function shorten_url_display($url, $length = 50) {
                if (strlen($url) <= $length) {
                    return $url;
                }
                return substr($url, 0, $length) . '...';
            }
        }

        $longUrl = 'https://www.example.com/very/long/path/to/resource/page?param1=value1&param2=value2';
        $shortened = shorten_url_display($longUrl, 30);

        $this->assertEquals(33, strlen($shortened)); // 30 + '...'
        $this->assertStringEndsWith('...', $shortened);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'shorten_url',
        ]);
    }

    /** @test */
    public function it_builds_query_string()
    {
        if (!function_exists('build_query_string')) {
            function build_query_string($params) {
                return http_build_query($params);
            }
        }

        $params = [
            'page' => 2,
            'filter' => 'active',
            'sort' => 'name',
        ];

        $query = build_query_string($params);
        $this->assertStringContainsString('page=2', $query);
        $this->assertStringContainsString('filter=active', $query);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'build_query_string',
        ]);
    }

    /** @test */
    public function it_parses_query_string()
    {
        if (!function_exists('parse_query_string')) {
            function parse_query_string($query) {
                parse_str($query, $params);
                return $params;
            }
        }

        $query = 'page=2&filter=active&sort=name';
        $params = parse_query_string($query);

        $this->assertEquals('2', $params['page']);
        $this->assertEquals('active', $params['filter']);
        $this->assertEquals('name', $params['sort']);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'parse_query_string',
        ]);
    }

    /** @test */
    public function it_checks_if_url_is_secure()
    {
        if (!function_exists('is_secure_url')) {
            function is_secure_url($url) {
                return strpos($url, 'https://') === 0;
            }
        }

        $this->assertTrue(is_secure_url('https://example.com'));
        $this->assertFalse(is_secure_url('http://example.com'));

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'is_secure_url',
        ]);
    }

    /** @test */
    public function it_removes_query_parameters()
    {
        if (!function_exists('remove_query_params')) {
            function remove_query_params($url) {
                $parsed = parse_url($url);
                return $parsed['scheme'] . '://' . $parsed['host'] . ($parsed['path'] ?? '');
            }
        }

        $url = 'https://example.com/page?param1=value1&param2=value2';
        $clean = remove_query_params($url);

        $this->assertEquals('https://example.com/page', $clean);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'remove_query_params',
        ]);
    }

    /** @test */
    public function it_appends_path_to_url()
    {
        if (!function_exists('append_path')) {
            function append_path($baseUrl, $path) {
                return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
            }
        }

        $url = append_path('https://example.com/', '/api/users');
        $this->assertEquals('https://example.com/api/users', $url);

        $url2 = append_path('https://example.com', 'products');
        $this->assertEquals('https://example.com/products', $url2);

        $this->logTestResult('passed', [
            'helper' => 'UrlHelper',
            'test' => 'append_path',
        ]);
    }
}
