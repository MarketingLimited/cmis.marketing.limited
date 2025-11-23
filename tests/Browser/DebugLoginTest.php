<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class DebugLoginTest extends DuskTestCase
{
    /**
     * Debug test to see what's actually on the login page
     */
    public function test_debug_login_page_content(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/login')
                ->pause(2000)
                ->screenshot('debug-login-page');

            // Get the page HTML
            $html = $browser->driver->getPageSource();

            // Save to file for inspection
            file_put_contents(
                base_path('tests/Browser/console/login-page-html.txt'),
                $html
            );

            // Check what text is actually present
            try {
                $bodyText = $browser->driver->findElement(\Facebook\WebDriver\WebDriverBy::tagName('body'))->getText();
                file_put_contents(
                    base_path('tests/Browser/console/login-page-text.txt'),
                    $bodyText
                );
            } catch (\Exception $e) {
                file_put_contents(
                    base_path('tests/Browser/console/login-page-text.txt'),
                    "Error getting body text: " . $e->getMessage()
                );
            }

            echo "\n\nPage HTML saved to: tests/Browser/console/login-page-html.txt\n";
            echo "Page text saved to: tests/Browser/console/login-page-text.txt\n\n";

            // Always pass - this is just for debugging
            $this->assertTrue(true);
        });
    }
}
