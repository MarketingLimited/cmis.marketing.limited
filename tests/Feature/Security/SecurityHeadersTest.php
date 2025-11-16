<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that security headers are present on all responses.
     *
     * @return void
     */
    public function test_security_headers_are_present()
    {
        $response = $this->get('/');

        // Check for all security headers
        $this->assertNotNull($response->headers->get('X-Content-Type-Options'));
        $this->assertNotNull($response->headers->get('X-Frame-Options'));
        $this->assertNotNull($response->headers->get('X-XSS-Protection'));
        $this->assertNotNull($response->headers->get('Referrer-Policy'));
        $this->assertNotNull($response->headers->get('Permissions-Policy'));
    }

    /**
     * Test X-Content-Type-Options header.
     *
     * @return void
     */
    public function test_x_content_type_options_is_nosniff()
    {
        $response = $this->get('/');

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    /**
     * Test X-Frame-Options header.
     *
     * @return void
     */
    public function test_x_frame_options_is_sameorigin()
    {
        $response = $this->get('/');

        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    /**
     * Test X-XSS-Protection header.
     *
     * @return void
     */
    public function test_x_xss_protection_is_enabled()
    {
        $response = $this->get('/');

        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    /**
     * Test Referrer-Policy header.
     *
     * @return void
     */
    public function test_referrer_policy_is_set()
    {
        $response = $this->get('/');

        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
    }

    /**
     * Test Permissions-Policy header.
     *
     * @return void
     */
    public function test_permissions_policy_is_restrictive()
    {
        $response = $this->get('/');

        $permissionsPolicy = $response->headers->get('Permissions-Policy');

        $this->assertNotNull($permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('payment=()', $permissionsPolicy);
        $this->assertStringContainsString('usb=()', $permissionsPolicy);
    }

    /**
     * Test HSTS header is present in production.
     *
     * @return void
     */
    public function test_hsts_header_in_production()
    {
        app()->detectEnvironment(fn() => 'production');

        $response = $this->get('/');

        $hsts = $response->headers->get('Strict-Transport-Security');

        $this->assertNotNull($hsts);
        $this->assertStringContainsString('max-age=31536000', $hsts);
        $this->assertStringContainsString('includeSubDomains', $hsts);
        $this->assertStringContainsString('preload', $hsts);
    }

    /**
     * Test HSTS header is not present in development.
     *
     * @return void
     */
    public function test_hsts_header_not_in_development()
    {
        app()->detectEnvironment(fn() => 'local');

        $response = $this->get('/');

        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    /**
     * Test Content-Security-Policy header for HTML responses.
     *
     * @return void
     */
    public function test_csp_header_for_html_responses()
    {
        $response = $this->get('/');

        // CSP should be present for HTML responses
        $csp = $response->headers->get('Content-Security-Policy');

        if ($response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
            $this->assertNotNull($csp);
            $this->assertStringContainsString("default-src 'self'", $csp);
            $this->assertStringContainsString("frame-ancestors 'self'", $csp);
            $this->assertStringContainsString("base-uri 'self'", $csp);
        }
    }

    /**
     * Test security headers on API responses.
     *
     * @return void
     */
    public function test_security_headers_on_api_responses()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        // Basic security headers should be present
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));

        // CSP should not be present for JSON responses
        // (Only applied to HTML responses)
        $contentType = $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $this->assertNull($response->headers->get('Content-Security-Policy'));
        }
    }

    /**
     * Test security headers on error responses.
     *
     * @return void
     */
    public function test_security_headers_on_error_responses()
    {
        $response = $this->get('/nonexistent-route');

        // Security headers should be present even on 404 responses
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
    }

    /**
     * Test that all critical security headers are present.
     *
     * @return void
     */
    public function test_all_critical_security_headers_present()
    {
        $response = $this->get('/');

        $criticalHeaders = [
            'X-Content-Type-Options',
            'X-Frame-Options',
            'X-XSS-Protection',
            'Referrer-Policy',
            'Permissions-Policy',
        ];

        foreach ($criticalHeaders as $header) {
            $this->assertNotNull(
                $response->headers->get($header),
                "Critical security header '{$header}' is missing"
            );
        }
    }

    /**
     * Test security headers on authenticated routes.
     *
     * @return void
     */
    public function test_security_headers_on_authenticated_routes()
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->get('/home');

        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
    }

    /**
     * Test security headers don't interfere with normal functionality.
     *
     * @return void
     */
    public function test_security_headers_dont_break_functionality()
    {
        $user = User::factory()->create(['status' => 'active']);
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        // Should still get successful response
        $response->assertStatus(200);

        // And security headers should be present
        $this->assertNotNull($response->headers->get('X-Content-Type-Options'));
    }

    /**
     * Test CSP allows necessary resources.
     *
     * @return void
     */
    public function test_csp_allows_necessary_resources()
    {
        $response = $this->get('/');

        $csp = $response->headers->get('Content-Security-Policy');

        if ($csp) {
            // Should allow self
            $this->assertStringContainsString("'self'", $csp);

            // Should allow necessary CDNs
            $this->assertStringContainsString('https://cdn.tailwindcss.com', $csp);
            $this->assertStringContainsString('https://fonts.googleapis.com', $csp);
            $this->assertStringContainsString('https://fonts.gstatic.com', $csp);
        }
    }
}
