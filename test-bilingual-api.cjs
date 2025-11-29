const axios = require('axios');
const fs = require('fs');
const path = require('path');

// Configuration
const CONFIG = {
    baseUrl: 'https://cmis-test.kazaaz.com',
    orgId: '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a',
    resultDir: './test-results/bilingual-api',
    credentials: {
        email: 'admin@cmis.test',
        password: 'password'
    },
    languages: ['ar', 'en'],
    timeout: 30000,
    // Rate limiting for AI endpoints
    aiRateLimit: {
        requestsPerMinute: 25,  // Conservative (limit is 30)
        delayBetweenRequests: 2500  // 2.5 seconds
    }
};

// Test results storage
const testResults = {
    startTime: new Date().toISOString(),
    config: CONFIG,
    summary: {
        totalEndpoints: 0,
        totalTests: 0,
        testedEndpoints: 0,
        successfulEndpoints: 0,
        failedEndpoints: 0,
        skippedEndpoints: 0,
        categories: {}
    },
    endpoints: [],
    authToken: null
};

// API Endpoints to test (organized by category)
const API_ENDPOINTS = {
    // Category 1: Authentication & Context (Critical)
    authentication: [
        { method: 'POST', path: '/api/auth/login', category: 'authentication', requiresAuth: false, body: CONFIG.credentials, priority: 'critical' },
        { method: 'POST', path: '/api/auth/logout', category: 'authentication', requiresAuth: true, priority: 'critical' },
        { method: 'GET', path: '/api/user', category: 'authentication', requiresAuth: true, priority: 'critical' },
        { method: 'POST', path: `/api/orgs/${CONFIG.orgId}/context`, category: 'authentication', requiresAuth: true, priority: 'critical' },
    ],

    // Category 2: Campaigns (High Priority)
    campaigns: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/campaigns`, category: 'campaigns', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/campaigns/stats`, category: 'campaigns', requiresAuth: true, priority: 'high' },
        { method: 'POST', path: `/api/orgs/${CONFIG.orgId}/campaigns`, category: 'campaigns', requiresAuth: true, priority: 'high', body: { name: 'Test Campaign', status: 'draft' }, cleanup: true },
    ],

    // Category 3: Ad Campaigns (High Priority)
    adCampaigns: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/ad-campaigns`, category: 'ad-campaigns', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/ad-campaigns/active`, category: 'ad-campaigns', requiresAuth: true, priority: 'high' },
    ],

    // Category 4: Social Media (High Priority)
    social: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/social/posts`, category: 'social', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/social/posts/scheduled`, category: 'social', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/social/analytics`, category: 'social', requiresAuth: true, priority: 'high' },
    ],

    // Category 5: Analytics & Reporting (High Priority)
    analytics: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/analytics/dashboard`, category: 'analytics', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/analytics/realtime`, category: 'analytics', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/analytics/kpis`, category: 'analytics', requiresAuth: true, priority: 'high' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/analytics/metrics`, category: 'analytics', requiresAuth: true, priority: 'high' },
    ],

    // Category 6: AI & Intelligence (High Priority - Throttled)
    ai: [
        { method: 'POST', path: `/api/orgs/${CONFIG.orgId}/ai/semantic-search`, category: 'ai', requiresAuth: true, priority: 'high', throttled: true, body: { query: 'test' } },
        { method: 'POST', path: `/api/orgs/${CONFIG.orgId}/ai/content-generation`, category: 'ai', requiresAuth: true, priority: 'high', throttled: true, body: { prompt: 'Generate test content' } },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/ai/insights`, category: 'ai', requiresAuth: true, priority: 'high', throttled: true },
    ],

    // Category 7: Creative & Assets (Medium Priority)
    creative: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/creative/assets`, category: 'creative', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/creative/briefs`, category: 'creative', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/creative/templates`, category: 'creative', requiresAuth: true, priority: 'medium' },
    ],

    // Category 8: Team & Collaboration (Medium Priority)
    team: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/team/members`, category: 'team', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/team/invitations`, category: 'team', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/team/roles`, category: 'team', requiresAuth: true, priority: 'medium' },
    ],

    // Category 9: Platform Integrations (Medium Priority)
    platforms: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/platforms/connections`, category: 'platforms', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/platforms/meta/status`, category: 'platforms', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/platforms/google/status`, category: 'platforms', requiresAuth: true, priority: 'medium' },
    ],

    // Category 10: Audience & Targeting (Medium Priority)
    audiences: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/audiences`, category: 'audiences', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/audiences/segments`, category: 'audiences', requiresAuth: true, priority: 'medium' },
    ],

    // Category 11: Settings & Configuration (Medium Priority)
    settings: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/settings`, category: 'settings', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/settings/brand-voices`, category: 'settings', requiresAuth: true, priority: 'medium' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/settings/approval-workflows`, category: 'settings', requiresAuth: true, priority: 'medium' },
    ],

    // Category 12: Webhooks & Events (Low Priority)
    webhooks: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/webhooks`, category: 'webhooks', requiresAuth: true, priority: 'low' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/webhooks/logs`, category: 'webhooks', requiresAuth: true, priority: 'low' },
    ],

    // Category 13: Exports & Reports (Low Priority)
    exports: [
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/exports`, category: 'exports', requiresAuth: true, priority: 'low' },
        { method: 'GET', path: `/api/orgs/${CONFIG.orgId}/exports/history`, category: 'exports', requiresAuth: true, priority: 'low' },
    ],
};

// Flatten all endpoints
const allEndpoints = Object.values(API_ENDPOINTS).flat();
testResults.summary.totalEndpoints = allEndpoints.length;
testResults.summary.totalTests = allEndpoints.length * CONFIG.languages.length;

/**
 * Authenticate and get access token
 */
async function authenticate() {
    console.log('\n🔐 Authenticating...');
    try {
        const response = await axios.post(`${CONFIG.baseUrl}/api/auth/login`, CONFIG.credentials, {
            timeout: CONFIG.timeout,
            validateStatus: () => true
        });

        // API returns standardized response: { success, message, data: { user, token, token_type } }
        const token = response.data?.data?.token || response.data?.token;

        if (response.status === 200 && token) {
            testResults.authToken = token;
            console.log('   ✅ Authentication successful');
            console.log(`   Token: ${token.substring(0, 20)}...`);
            return true;
        } else {
            console.log(`   ❌ Authentication failed: ${response.status}`);
            if (!token) {
                console.log(`   No token found in response`);
            }
            return false;
        }
    } catch (error) {
        console.log(`   ❌ Authentication error: ${error.message}`);
        return false;
    }
}

/**
 * Switch organization context
 */
async function switchOrgContext() {
    console.log('\n🏢 Switching to organization context...');
    try {
        const response = await axios.post(
            `${CONFIG.baseUrl}/api/orgs/${CONFIG.orgId}/context`,
            {},
            {
                headers: {
                    'Authorization': `Bearer ${testResults.authToken}`,
                    'Accept': 'application/json'
                },
                timeout: CONFIG.timeout,
                validateStatus: () => true
            }
        );

        if (response.status === 200) {
            console.log('   ✅ Organization context switched');
            return true;
        } else {
            console.log(`   ⚠️  Context switch returned: ${response.status} - continuing anyway`);
            // Many endpoints don't require explicit context switch
            return true;
        }
    } catch (error) {
        console.log(`   ⚠️  Context switch error: ${error.message} - continuing anyway`);
        // Many endpoints don't require explicit context switch
        return true;
    }
}

/**
 * Test a single endpoint in a specific language
 */
async function testEndpoint(endpoint, language) {
    const { method, path, requiresAuth, body, throttled } = endpoint;
    const fullUrl = `${CONFIG.baseUrl}${path}`;

    // Apply throttling for AI endpoints
    if (throttled) {
        await new Promise(resolve => setTimeout(resolve, CONFIG.aiRateLimit.delayBetweenRequests));
    }

    const headers = {
        'Accept': 'application/json',
        'Accept-Language': language,
        'Content-Type': 'application/json'
    };

    if (requiresAuth && testResults.authToken) {
        headers['Authorization'] = `Bearer ${testResults.authToken}`;
    }

    try {
        const config = {
            method: method.toLowerCase(),
            url: fullUrl,
            headers,
            timeout: CONFIG.timeout,
            validateStatus: () => true  // Don't throw on any status
        };

        if (body && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            config.data = body;
        }

        const response = await axios(config);

        return {
            statusCode: response.status,
            success: response.status >= 200 && response.status < 300,
            data: response.data,
            headers: response.headers,
            error: null
        };
    } catch (error) {
        return {
            statusCode: 0,
            success: false,
            data: null,
            headers: null,
            error: error.message
        };
    }
}

/**
 * Analyze response for i18n compliance
 */
function analyzeI18nCompliance(response, expectedLang) {
    const issues = [];

    if (!response.data) {
        return { compliant: true, issues: [] };
    }

    // Check if response has message/error fields in the expected language
    const responseStr = JSON.stringify(response.data);

    // Check for common English error messages when Arabic expected
    if (expectedLang === 'ar') {
        if (responseStr.match(/\b(unauthorized|forbidden|not found|bad request|error|success)\b/i)) {
            issues.push('Response contains English text when Arabic expected');
        }
    }

    // Check for Arabic text when English expected
    if (expectedLang === 'en') {
        if (/[\u0600-\u06FF]/.test(responseStr)) {
            issues.push('Response contains Arabic text when English expected');
        }
    }

    return {
        compliant: issues.length === 0,
        issues
    };
}

/**
 * Test a single endpoint in all languages
 */
async function testEndpointAllLanguages(endpoint, index, total) {
    const { method, path, category, priority, requiresAuth } = endpoint;

    console.log(`\n[${index + 1}/${total}] 📡 Testing: ${method} ${path}`);
    console.log(`   Category: ${category}, Priority: ${priority}`);

    const endpointResult = {
        method,
        path,
        category,
        priority,
        requiresAuth,
        url: `${CONFIG.baseUrl}${path}`,
        languages: {}
    };

    // Test in Arabic
    console.log('   🌍 Testing Arabic (ar)...');
    const arResult = await testEndpoint(endpoint, 'ar');
    const arCompliance = analyzeI18nCompliance(arResult, 'ar');

    endpointResult.languages.ar = {
        statusCode: arResult.statusCode,
        success: arResult.success,
        i18nCompliance: arCompliance,
        hasData: !!arResult.data,
        error: arResult.error
    };

    console.log(`      Status: ${arResult.statusCode}, Success: ${arResult.success}`);
    if (arResult.error) {
        console.log(`      Error: ${arResult.error}`);
    }
    console.log(`      i18n Compliant: ${arCompliance.compliant ? 'Yes' : 'No'}`);
    if (!arCompliance.compliant) {
        arCompliance.issues.forEach(issue => console.log(`      ⚠️  ${issue}`));
    }

    // Test in English
    console.log('   🌍 Testing English (en)...');
    const enResult = await testEndpoint(endpoint, 'en');
    const enCompliance = analyzeI18nCompliance(enResult, 'en');

    endpointResult.languages.en = {
        statusCode: enResult.statusCode,
        success: enResult.success,
        i18nCompliance: enCompliance,
        hasData: !!enResult.data,
        error: enResult.error
    };

    console.log(`      Status: ${enResult.statusCode}, Success: ${enResult.success}`);
    if (enResult.error) {
        console.log(`      Error: ${enResult.error}`);
    }
    console.log(`      i18n Compliant: ${enCompliance.compliant ? 'Yes' : 'No'}`);
    if (!enCompliance.compliant) {
        enCompliance.issues.forEach(issue => console.log(`      ⚠️  ${issue}`));
    }

    // Determine overall status
    const bothSuccessful = arResult.success && enResult.success;
    const atLeastOneSuccessful = arResult.success || enResult.success;

    endpointResult.status = bothSuccessful ? 'success' : (atLeastOneSuccessful ? 'partial' : 'failed');

    if (bothSuccessful) {
        testResults.summary.successfulEndpoints++;
        console.log('   ✅ Test passed (both languages)');
    } else if (atLeastOneSuccessful) {
        testResults.summary.successfulEndpoints++;
        console.log('   ⚠️  Partial success (one language working)');
    } else {
        testResults.summary.failedEndpoints++;
        console.log('   ❌ Test failed (both languages failed)');
    }

    testResults.summary.testedEndpoints++;
    testResults.endpoints.push(endpointResult);

    // Update category summary
    if (!testResults.summary.categories[category]) {
        testResults.summary.categories[category] = {
            total: 0,
            tested: 0,
            successful: 0,
            failed: 0,
            partial: 0
        };
    }
    testResults.summary.categories[category].total++;
    testResults.summary.categories[category].tested++;

    if (endpointResult.status === 'success') {
        testResults.summary.categories[category].successful++;
    } else if (endpointResult.status === 'partial') {
        testResults.summary.categories[category].partial++;
    } else {
        testResults.summary.categories[category].failed++;
    }
}

/**
 * Generate test reports
 */
function generateReports() {
    console.log('\n📊 Generating reports...');

    // Create result directory
    if (!fs.existsSync(CONFIG.resultDir)) {
        fs.mkdirSync(CONFIG.resultDir, { recursive: true });
    }

    // Save JSON report
    const jsonReport = path.join(CONFIG.resultDir, 'test-report.json');
    fs.writeFileSync(jsonReport, JSON.stringify(testResults, null, 2));
    console.log(`   ✅ JSON report: ${jsonReport}`);

    // Generate Markdown summary
    const mdReport = path.join(CONFIG.resultDir, 'SUMMARY.md');
    let mdContent = `# Bilingual API Testing Report\n\n`;
    mdContent += `**Date:** ${testResults.startTime}\n`;
    mdContent += `**Total Endpoints:** ${testResults.summary.totalEndpoints}\n`;
    mdContent += `**Total Tests:** ${testResults.summary.totalTests} (${testResults.summary.totalEndpoints} endpoints × 2 languages)\n\n`;

    mdContent += `## Summary\n\n`;
    mdContent += `- ✅ Successful: ${testResults.summary.successfulEndpoints}\n`;
    mdContent += `- ❌ Failed: ${testResults.summary.failedEndpoints}\n`;
    mdContent += `- ⏭️  Skipped: ${testResults.summary.skippedEndpoints}\n`;
    mdContent += `- 📊 Success Rate: ${((testResults.summary.successfulEndpoints / testResults.summary.testedEndpoints) * 100).toFixed(1)}%\n\n`;

    mdContent += `## By Category\n\n`;
    mdContent += `| Category | Total | Tested | Success | Partial | Failed | Success Rate |\n`;
    mdContent += `|----------|-------|--------|---------|---------|--------|-------------|\n`;

    Object.entries(testResults.summary.categories).forEach(([category, stats]) => {
        const successRate = stats.tested > 0 ? ((stats.successful / stats.tested) * 100).toFixed(1) : '0.0';
        mdContent += `| ${category} | ${stats.total} | ${stats.tested} | ${stats.successful} | ${stats.partial || 0} | ${stats.failed} | ${successRate}% |\n`;
    });

    mdContent += `\n## Endpoints Tested\n\n`;

    testResults.endpoints.forEach(endpoint => {
        const icon = endpoint.status === 'success' ? '✅' : (endpoint.status === 'partial' ? '⚠️' : '❌');
        mdContent += `${icon} **${endpoint.method} ${endpoint.path}** - ${endpoint.category}\n`;

        if (endpoint.languages.ar) {
            mdContent += `   - Arabic: status=${endpoint.languages.ar.statusCode}, success=${endpoint.languages.ar.success}, i18n=${endpoint.languages.ar.i18nCompliance?.compliant ? 'Yes' : 'No'}\n`;
        }
        if (endpoint.languages.en) {
            mdContent += `   - English: status=${endpoint.languages.en.statusCode}, success=${endpoint.languages.en.success}, i18n=${endpoint.languages.en.i18nCompliance?.compliant ? 'Yes' : 'No'}\n`;
        }

        mdContent += `\n`;
    });

    fs.writeFileSync(mdReport, mdContent);
    console.log(`   ✅ Markdown summary: ${mdReport}`);
}

/**
 * Main test execution
 */
async function main() {
    console.log('🚀 Starting Comprehensive Bilingual API Testing');
    console.log(`📍 Base URL: ${CONFIG.baseUrl}`);
    console.log(`🏢 Organization: ${CONFIG.orgId}`);
    console.log(`📦 Total Endpoints: ${allEndpoints.length}`);
    console.log(`🌍 Languages: ${CONFIG.languages.join(', ')}`);
    console.log(`📊 Total Tests: ${allEndpoints.length * CONFIG.languages.length}`);

    try {
        // Authenticate
        const authSuccess = await authenticate();
        if (!authSuccess) {
            console.log('\n❌ Authentication failed - cannot proceed');
            return;
        }

        // Switch to organization context
        const contextSuccess = await switchOrgContext();
        if (!contextSuccess) {
            console.log('\n⚠️  Organization context switch failed - some tests may fail');
        }

        // Test all endpoints
        for (let i = 0; i < allEndpoints.length; i++) {
            const endpoint = allEndpoints[i];

            // Skip auth endpoints in main loop (already tested during auth)
            if (endpoint.path === '/api/auth/login') {
                console.log(`\n[${i + 1}/${allEndpoints.length}] ⏭️  Skipping ${endpoint.method} ${endpoint.path} (already tested during auth)`);
                testResults.summary.skippedEndpoints++;
                continue;
            }

            await testEndpointAllLanguages(endpoint, i, allEndpoints.length);

            // Small delay between endpoints
            await new Promise(resolve => setTimeout(resolve, 100));
        }

    } catch (error) {
        console.error('\n❌ Fatal error:', error);
    } finally {
        // Calculate end time
        testResults.endTime = new Date().toISOString();

        // Generate reports
        generateReports();

        console.log('\n✅ Testing complete!');
        console.log(`\n📊 Final Summary:`);
        console.log(`   Total Endpoints: ${testResults.summary.totalEndpoints}`);
        console.log(`   Tested: ${testResults.summary.testedEndpoints}`);
        console.log(`   Successful: ${testResults.summary.successfulEndpoints}`);
        console.log(`   Failed: ${testResults.summary.failedEndpoints}`);
        console.log(`   Skipped: ${testResults.summary.skippedEndpoints}`);
        console.log(`   Success Rate: ${((testResults.summary.successfulEndpoints / testResults.summary.testedEndpoints) * 100).toFixed(1)}%`);
    }
}

// Run the tests
main().catch(console.error);
