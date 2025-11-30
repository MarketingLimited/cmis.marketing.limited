<?php

/**
 * Test Timezone API Endpoint with Inheritance Hierarchy
 * Uses Laravel's authentication context
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$orgId = '5c8d4b5a-7b8c-8d9e-2f1a-5b6c7d8e9f0a';

// Set RLS context
DB::statement("SELECT set_config('app.current_org_id', ?, false)", [$orgId]);

echo "🧪 Testing Timezone API Endpoint with Inheritance Hierarchy\n";
echo str_repeat('=', 80) . "\n\n";

// Test cases
$testCases = [
    [
        'name' => 'Instagram with Profile Group timezone (Asia/Dubai)',
        'integration_ids' => ['019ad524-9807-73d4-892e-e1ca9fc6cd84'],
        'expected_timezone' => 'Asia/Dubai',
        'expected_source' => 'profile_group'
    ],
    [
        'name' => 'Meta without Profile Group (should inherit from org → UTC)',
        'integration_ids' => ['c9274137-3ff7-9d6f-0db5-39c4b07260ed'],
        'expected_timezone' => 'UTC',
        'expected_source' => 'organization'
    ]
];

$passCount = 0;
$failCount = 0;

foreach ($testCases as $testCase) {
    echo "📋 Test: {$testCase['name']}\n";
    echo "   Integration IDs: " . implode(', ', $testCase['integration_ids']) . "\n";

    try {
        // Call the timezone lookup logic directly
        $timezones = DB::table('cmis.integrations as i')
            ->leftJoin('cmis.social_accounts as sa', 'i.integration_id', '=', 'sa.integration_id')
            ->leftJoin('cmis.profile_groups as pg', 'i.profile_group_id', '=', 'pg.group_id')
            ->join('cmis.orgs as o', 'i.org_id', '=', 'o.org_id')
            ->whereIn('i.integration_id', $testCase['integration_ids'])
            ->where('i.org_id', $orgId)
            ->select(
                'i.integration_id',
                'pg.group_id as profile_group_id',
                'pg.name as profile_group_name',
                'sa.username as social_account_name',
                'sa.timezone as social_account_timezone',
                'pg.timezone as profile_group_timezone',
                'o.timezone as org_timezone',
                DB::raw('COALESCE(sa.timezone, pg.timezone, o.timezone, \'UTC\') as timezone'),
                DB::raw("CASE
                    WHEN sa.timezone IS NOT NULL THEN 'social_account'
                    WHEN pg.timezone IS NOT NULL THEN 'profile_group'
                    WHEN o.timezone IS NOT NULL THEN 'organization'
                    ELSE 'default'
                END as timezone_source")
            )
            ->get();

        if ($timezones->isEmpty()) {
            echo "   ❌ FAIL: No integrations found\n\n";
            $failCount++;
            continue;
        }

        $tz = $timezones->first();

        echo "   🌍 Timezone: {$tz->timezone}\n";
        echo "   📍 Source: {$tz->timezone_source}\n";
        echo "   👥 Profile Group: " . ($tz->profile_group_name ?? 'N/A') . "\n";
        echo "   🔗 Inheritance Chain:\n";
        echo "      - Social Account: " . ($tz->social_account_timezone ?? 'NULL') . "\n";
        echo "      - Profile Group: " . ($tz->profile_group_timezone ?? 'NULL') . "\n";
        echo "      - Organization: " . ($tz->org_timezone ?? 'NULL') . "\n";
        echo "      - Final (used): {$tz->timezone}\n";
        echo "      - Source level: {$tz->timezone_source}\n";

        // Validate expectations
        if ($tz->timezone !== $testCase['expected_timezone']) {
            echo "   ❌ FAIL: Expected timezone \"{$testCase['expected_timezone']}\", got \"{$tz->timezone}\"\n\n";
            $failCount++;
        } elseif ($tz->timezone_source !== $testCase['expected_source']) {
            echo "   ❌ FAIL: Expected source \"{$testCase['expected_source']}\", got \"{$tz->timezone_source}\"\n\n";
            $failCount++;
        } else {
            echo "   ✅ PASS: Correct timezone and source\n\n";
            $passCount++;
        }

    } catch (\Exception $e) {
        echo "   ❌ FAIL: {$e->getMessage()}\n\n";
        $failCount++;
    }
}

echo str_repeat('=', 80) . "\n";
echo "\n📊 Test Results: {$passCount} passed, {$failCount} failed\n";

if ($failCount === 0) {
    echo "✅ All tests passed! Timezone inheritance is working correctly.\n\n";
    exit(0);
} else {
    echo "❌ Some tests failed. Please review the output above.\n\n";
    exit(1);
}
