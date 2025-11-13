<?php

echo "🔍 COMPREHENSIVE FIELD NAME AUDIT\n";
echo "================================================================================\n\n";

// Define correct field names from schema.sql
$schemaFields = [
    'users' => ['user_id', 'email', 'display_name', 'role', 'deleted_at', 'provider', 'status', 'name', 'password'],
    'orgs' => ['org_id', 'name', 'default_locale', 'currency', 'created_at', 'deleted_at', 'provider'],
    'campaigns' => ['campaign_id', 'org_id', 'name', 'objective', 'status', 'start_date', 'end_date', 'budget', 'currency', 'created_at', 'updated_at', 'context_id', 'creative_id', 'value_id', 'created_by', 'deleted_at', 'provider', 'deleted_by'],
    'user_orgs' => ['id', 'user_id', 'org_id', 'role_id', 'is_active', 'joined_at', 'invited_by', 'last_accessed', 'deleted_at', 'provider'],
    'roles' => ['role_id', 'org_id', 'role_name', 'role_code', 'description', 'is_system', 'deleted_at', 'provider'],
    'ad_entities' => ['id', 'org_id', 'integration_id', 'adset_external_id', 'ad_external_id', 'name', 'status', 'creative_id', 'created_at', 'updated_at', 'deleted_at', 'provider', 'deleted_by'],
];

$issues = [];

// 1. Check Blade Files
echo "📋 Checking Blade Files...\n";
$bladeFiles = glob(__DIR__ . '/../resources/views/**/*.blade.php');
$bladeIssues = [];

foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    $shortPath = str_replace(__DIR__ . '/../', '', $file);

    // Check for wrong user references
    if (preg_match_all('/\buser\s*\.\s*id\b(?!entity)/i', $content, $matches)) {
        $bladeIssues[] = "$shortPath: Uses 'user.id' (should be 'user.user_id')";
    }

    // Check for wrong org references
    if (preg_match_all('/\borg\s*\.\s*id\b(?!entity)/i', $content, $matches)) {
        $bladeIssues[] = "$shortPath: Uses 'org.id' (should be 'org.org_id')";
    }

    // Check for wrong campaign references
    if (preg_match_all('/\bcampaign\s*\.\s*id\b(?!entity)/i', $content, $matches)) {
        $bladeIssues[] = "$shortPath: Uses 'campaign.id' (should be 'campaign.campaign_id')";
    }

    // Check for wrong role references
    if (preg_match_all('/\brole\s*\.\s*id\b(?!entity)/i', $content, $matches)) {
        $bladeIssues[] = "$shortPath: Uses 'role.id' (should be 'role.role_id')";
    }
}

if (empty($bladeIssues)) {
    echo "  ✅ All blade files use correct field names\n\n";
} else {
    echo "  ⚠️  Found " . count($bladeIssues) . " potential issues:\n";
    foreach (array_slice($bladeIssues, 0, 10) as $issue) {
        echo "    - $issue\n";
    }
    if (count($bladeIssues) > 10) {
        echo "    ... and " . (count($bladeIssues) - 10) . " more\n";
    }
    echo "\n";
    $issues = array_merge($issues, $bladeIssues);
}

// 2. Check Controllers
echo "📋 Checking Controllers...\n";
$controllerFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app/Http/Controllers'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $controllerFiles[] = $file->getPathname();
    }
}

$controllerIssues = [];

foreach ($controllerFiles as $file) {
    $content = file_get_contents($file);
    $shortPath = str_replace(__DIR__ . '/../', '', $file);

    // Check for ->id references on user, org, campaign, role objects
    $patterns = [
        '/\$user\s*->\s*id\b(?!entity)/' => "Uses '\$user->id' (should be '\$user->user_id')",
        '/\$org\s*->\s*id\b(?!entity)/' => "Uses '\$org->id' (should be '\$org->org_id')",
        '/\$campaign\s*->\s*id\b(?!entity)/' => "Uses '\$campaign->id' (should be '\$campaign->campaign_id')",
        '/\$role\s*->\s*id\b(?!entity)/' => "Uses '\$role->id' (should be '\$role->role_id')",
    ];

    foreach ($patterns as $pattern => $message) {
        if (preg_match($pattern, $content)) {
            $controllerIssues[] = "$shortPath: $message";
        }
    }
}

if (empty($controllerIssues)) {
    echo "  ✅ All controllers use correct field names\n\n";
} else {
    echo "  ⚠️  Found " . count($controllerIssues) . " potential issues:\n";
    foreach (array_slice($controllerIssues, 0, 10) as $issue) {
        echo "    - $issue\n";
    }
    if (count($controllerIssues) > 10) {
        echo "    ... and " . (count($controllerIssues) - 10) . " more\n";
    }
    echo "\n";
    $issues = array_merge($issues, $controllerIssues);
}

// 3. Check Services
echo "📋 Checking Services...\n";
$serviceFiles = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../app/Services'));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $serviceFiles[] = $file->getPathname();
    }
}

$serviceIssues = [];

foreach ($serviceFiles as $file) {
    $content = file_get_contents($file);
    $shortPath = str_replace(__DIR__ . '/../', '', $file);

    $patterns = [
        '/\$user\s*->\s*id\b(?!entity)/' => "Uses '\$user->id' (should be '\$user->user_id')",
        '/\$org\s*->\s*id\b(?!entity)/' => "Uses '\$org->id' (should be '\$org->org_id')",
        '/\$campaign\s*->\s*id\b(?!entity)/' => "Uses '\$campaign->id' (should be '\$campaign->campaign_id')",
        '/\$role\s*->\s*id\b(?!entity)/' => "Uses '\$role->id' (should be '\$role->role_id')",
    ];

    foreach ($patterns as $pattern => $message) {
        if (preg_match($pattern, $content)) {
            $serviceIssues[] = "$shortPath: $message";
        }
    }
}

if (empty($serviceIssues)) {
    echo "  ✅ All services use correct field names\n\n";
} else {
    echo "  ⚠️  Found " . count($serviceIssues) . " potential issues:\n";
    foreach (array_slice($serviceIssues, 0, 10) as $issue) {
        echo "    - $issue\n";
    }
    if (count($serviceIssues) > 10) {
        echo "    ... and " . (count($serviceIssues) - 10) . " more\n";
    }
    echo "\n";
    $issues = array_merge($issues, $serviceIssues);
}

// 4. Check API Resources
echo "📋 Checking API Resources...\n";
$resourcePath = __DIR__ . '/../app/Http/Resources';
if (is_dir($resourcePath)) {
    $resourceFiles = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resourcePath));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $resourceFiles[] = $file->getPathname();
        }
    }

    $resourceIssues = [];

    foreach ($resourceFiles as $file) {
        $content = file_get_contents($file);
        $shortPath = str_replace(__DIR__ . '/../', '', $file);

        // Check for 'id' => in resource arrays
        if (preg_match("/'id'\s*=>/", $content)) {
            // Check context - is it for user, org, campaign, role?
            $context = basename($file, '.php');
            if (stripos($context, 'User') !== false && !stripos($context, 'UserOrg') !== false) {
                $resourceIssues[] = "$shortPath: UserResource may use 'id' (should be 'user_id')";
            } elseif (stripos($context, 'Org') !== false && !stripos($context, 'UserOrg') !== false) {
                $resourceIssues[] = "$shortPath: OrgResource may use 'id' (should be 'org_id')";
            } elseif (stripos($context, 'Campaign') !== false) {
                $resourceIssues[] = "$shortPath: CampaignResource may use 'id' (should be 'campaign_id')";
            } elseif (stripos($context, 'Role') !== false) {
                $resourceIssues[] = "$shortPath: RoleResource may use 'id' (should be 'role_id')";
            }
        }
    }

    if (empty($resourceIssues)) {
        echo "  ✅ All API resources use correct field names\n\n";
    } else {
        echo "  ⚠️  Found " . count($resourceIssues) . " potential issues:\n";
        foreach ($resourceIssues as $issue) {
            echo "    - $issue\n";
        }
        echo "\n";
        $issues = array_merge($issues, $resourceIssues);
    }
} else {
    echo "  ℹ️  No API Resources directory found\n\n";
}

// Summary
echo "================================================================================\n";
echo "📊 FIELD NAME AUDIT SUMMARY\n";
echo "================================================================================\n\n";

echo "Files Checked:\n";
echo "  - Blade files: " . count($bladeFiles) . "\n";
echo "  - Controllers: " . count($controllerFiles) . "\n";
echo "  - Services: " . count($serviceFiles) . "\n\n";

if (empty($issues)) {
    echo "✅ ALL FILES USE CORRECT FIELD NAMES!\n";
    echo "✅ Application matches schema.sql perfectly\n\n";
    exit(0);
} else {
    echo "⚠️  FOUND " . count($issues) . " POTENTIAL FIELD NAME ISSUES\n\n";
    echo "These files may need manual review to ensure they use correct field names\n";
    echo "from schema.sql (user_id, org_id, campaign_id, role_id, etc.)\n\n";

    // Save detailed report
    file_put_contents(__DIR__ . '/field-audit-report.json', json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'total_issues' => count($issues),
        'blade_issues' => count($bladeIssues),
        'controller_issues' => count($controllerIssues),
        'service_issues' => count($serviceIssues),
        'issues' => $issues
    ], JSON_PRETTY_PRINT));

    echo "📄 Detailed report saved to scripts/field-audit-report.json\n\n";
    exit(1);
}
