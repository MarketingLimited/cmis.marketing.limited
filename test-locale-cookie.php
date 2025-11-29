<?php
/**
 * Quick test to verify app_locale cookie is NOT encrypted
 * 
 * Usage: Visit https://cmis-test.kazaaz.com/test-locale-cookie.php in browser
 */

// Check if app_locale cookie exists and show its raw value
if (isset($_COOKIE['app_locale'])) {
    $cookieValue = $_COOKIE['app_locale'];
    
    echo "<h1>Locale Cookie Test</h1>";
    echo "<h2>✅ app_locale cookie exists</h2>";
    echo "<p><strong>Raw cookie value:</strong> <code>" . htmlspecialchars($cookieValue) . "</code></p>";
    
    // Check if it's encrypted (encrypted cookies are base64-encoded JSON)
    if (strlen($cookieValue) > 50 && strpos($cookieValue, 'eyJpdiI') === 0) {
        echo "<p style='color: red;'><strong>❌ ENCRYPTED</strong> - Cookie is still encrypted!</p>";
        echo "<p>The cookie looks like Laravel encrypted JSON. Clear your browser cookies and try switching languages again.</p>";
    } elseif (in_array($cookieValue, ['ar', 'en'])) {
        echo "<p style='color: green;'><strong>✅ VALID & UNENCRYPTED</strong> - Cookie is working correctly!</p>";
        echo "<p>Locale is set to: <strong>" . htmlspecialchars($cookieValue) . "</strong></p>";
    } else {
        echo "<p style='color: orange;'><strong>⚠️ UNEXPECTED VALUE</strong></p>";
        echo "<p>Expected 'ar' or 'en', but got: <code>" . htmlspecialchars($cookieValue) . "</code></p>";
    }
} else {
    echo "<h1>Locale Cookie Test</h1>";
    echo "<h2>❌ No app_locale cookie found</h2>";
    echo "<p>This is normal if you haven't switched languages yet. Try clicking the language switcher.</p>";
}

echo "<hr>";
echo "<p><a href='/'>← Back to home</a></p>";
echo "<p style='color: gray; font-size: 12px;'>To delete this test file: <code>rm /home/cmis-test/public_html/test-locale-cookie.php</code></p>";
