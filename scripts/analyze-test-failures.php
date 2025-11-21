#!/usr/bin/env php
<?php

/**
 * Test Failure Analysis Script
 *
 * Parses PHPUnit JUnit XML output to categorize all test failures
 * and provide actionable insights for fixing them.
 */

$junitFile = $argv[1] ?? '/home/cmis-test/public_html/build/junit.xml';

if (!file_exists($junitFile)) {
    echo "ERROR: JUnit XML file not found: $junitFile\n";
    echo "Usage: php analyze-test-failures.php [path/to/junit.xml]\n";
    exit(1);
}

echo "Analyzing test failures from: $junitFile\n\n";

$xml = simplexml_load_file($junitFile);
if (!$xml) {
    echo "ERROR: Failed to parse XML file\n";
    exit(1);
}

// Initialize counters
$stats = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => 0,
    'skipped' => 0,
    'risky' => 0,
];

$failures = [
    'missing_classes' => [],
    'missing_methods' => [],
    'null_constraints' => [],
    'undefined_columns' => [],
    'undefined_tables' => [],
    'assertion_failures' => [],
    'type_errors' => [],
    'connection_errors' => [],
    'other' => [],
];

// Patterns for categorizing failures
$patterns = [
    'missing_classes' => '/Class .* not found/i',
    'missing_methods' => '/Call to undefined method|Method .* does not exist/i',
    'null_constraints' => '/NOT NULL constraint|null value in column/i',
    'undefined_columns' => '/column .* does not exist/i',
    'undefined_tables' => '/relation .* does not exist|table .* does not exist/i',
    'type_errors' => '/Argument #\d+ .* must be of type|Type error/i',
    'connection_errors' => '/connection .* failed|SQLSTATE\[08\d+\]/i',
];

// Parse test suites
foreach ($xml->testsuite as $suite) {
    $stats['total'] += (int)$suite['tests'];
    $stats['failed'] += (int)$suite['failures'];
    $stats['errors'] += (int)$suite['errors'];
    $stats['skipped'] += (int)$suite['skipped'];

    // Recursively parse testcases
    parseTestCases($suite, $failures, $patterns, $stats);
}

$stats['passed'] = $stats['total'] - ($stats['failed'] + $stats['errors'] + $stats['skipped']);

// Print summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST SUITE SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo sprintf("Total Tests:     %d\n", $stats['total']);
echo sprintf("Passed:          %d (%.1f%%)\n", $stats['passed'], ($stats['passed'] / max($stats['total'], 1)) * 100);
echo sprintf("Failed:          %d (%.1f%%)\n", $stats['failed'], ($stats['failed'] / max($stats['total'], 1)) * 100);
echo sprintf("Errors:          %d (%.1f%%)\n", $stats['errors'], ($stats['errors'] / max($stats['total'], 1)) * 100);
echo sprintf("Skipped:         %d (%.1f%%)\n\n", $stats['skipped'], ($stats['skipped'] / max($stats['total'], 1)) * 100);

// Print failure categories
echo "═══════════════════════════════════════════════════════════════\n";
echo "FAILURE CATEGORIES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$categoryOrder = [
    'missing_classes',
    'missing_methods',
    'null_constraints',
    'undefined_tables',
    'undefined_columns',
    'type_errors',
    'connection_errors',
    'assertion_failures',
    'other'
];

foreach ($categoryOrder as $category) {
    $count = count($failures[$category]);
    if ($count === 0) continue;

    $label = ucwords(str_replace('_', ' ', $category));
    echo sprintf("%s: %d failures\n", $label, $count);
    echo str_repeat('-', 60) . "\n";

    // Group by error message
    $grouped = [];
    foreach ($failures[$category] as $failure) {
        $key = $failure['error_type'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'count' => 0,
                'tests' => [],
                'message' => $failure['message']
            ];
        }
        $grouped[$key]['count']++;
        $grouped[$key]['tests'][] = $failure['test'];
    }

    // Sort by count descending
    uasort($grouped, function($a, $b) {
        return $b['count'] - $a['count'];
    });

    // Print top 5 most common errors in this category
    $shown = 0;
    foreach ($grouped as $key => $group) {
        if ($shown >= 5) break;
        echo sprintf("  [%d occurrences] %s\n", $group['count'], $key);

        // Show first 3 affected tests
        $testCount = min(3, count($group['tests']));
        for ($i = 0; $i < $testCount; $i++) {
            echo sprintf("    - %s\n", $group['tests'][$i]);
        }
        if (count($group['tests']) > 3) {
            echo sprintf("    ... and %d more\n", count($group['tests']) - 3);
        }
        echo "\n";
        $shown++;
    }

    if (count($grouped) > 5) {
        echo sprintf("  ... and %d more error types\n\n", count($grouped) - 5);
    }

    echo "\n";
}

// Generate actionable recommendations
echo "═══════════════════════════════════════════════════════════════\n";
echo "RECOMMENDED FIXES (Priority Order)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$recommendations = [];

if (count($failures['missing_classes']) > 0) {
    $recommendations[] = [
        'priority' => 1,
        'impact' => count($failures['missing_classes']),
        'action' => 'Create missing service/model classes',
        'details' => 'Identify all missing classes and create stub implementations'
    ];
}

if (count($failures['missing_methods']) > 0) {
    $recommendations[] = [
        'priority' => 2,
        'impact' => count($failures['missing_methods']),
        'action' => 'Implement missing methods',
        'details' => 'Add method stubs to existing classes with sensible defaults'
    ];
}

if (count($failures['null_constraints']) > 0) {
    $recommendations[] = [
        'priority' => 3,
        'impact' => count($failures['null_constraints']),
        'action' => 'Fix NULL constraint violations',
        'details' => 'Update factories/seeders or make columns nullable in migrations'
    ];
}

if (count($failures['undefined_tables']) > 0) {
    $recommendations[] = [
        'priority' => 4,
        'impact' => count($failures['undefined_tables']),
        'action' => 'Create missing database tables',
        'details' => 'Add migrations for missing tables or fix migration order'
    ];
}

if (count($failures['undefined_columns']) > 0) {
    $recommendations[] = [
        'priority' => 5,
        'impact' => count($failures['undefined_columns']),
        'action' => 'Add missing table columns',
        'details' => 'Update migrations to include missing columns'
    ];
}

usort($recommendations, function($a, $b) {
    if ($b['impact'] !== $a['impact']) {
        return $b['impact'] - $a['impact'];
    }
    return $a['priority'] - $b['priority'];
});

foreach ($recommendations as $i => $rec) {
    echo sprintf("%d. [%d tests affected] %s\n", $i + 1, $rec['impact'], $rec['action']);
    echo sprintf("   → %s\n\n", $rec['details']);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "DETAILED FAILURE EXPORT\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Export detailed JSON for programmatic processing
$export = [
    'summary' => $stats,
    'failures_by_category' => array_map('count', $failures),
    'detailed_failures' => $failures,
];

$exportFile = dirname($junitFile) . '/test-failures-analysis.json';
file_put_contents($exportFile, json_encode($export, JSON_PRETTY_PRINT));
echo "Detailed analysis saved to: $exportFile\n\n";

// Helper function to recursively parse test cases
function parseTestCases($element, &$failures, $patterns, &$stats) {
    // Handle nested test suites
    if (isset($element->testsuite)) {
        foreach ($element->testsuite as $suite) {
            parseTestCases($suite, $failures, $patterns, $stats);
        }
    }

    // Handle test cases
    if (isset($element->testcase)) {
        foreach ($element->testcase as $testcase) {
            $testName = (string)$testcase['class'] . '::' . (string)$testcase['name'];

            // Check for failures
            if (isset($testcase->failure)) {
                foreach ($testcase->failure as $failure) {
                    categorizeFailure($testName, (string)$failure, $failures, $patterns);
                }
            }

            // Check for errors
            if (isset($testcase->error)) {
                foreach ($testcase->error as $error) {
                    categorizeFailure($testName, (string)$error, $failures, $patterns);
                }
            }
        }
    }
}

function categorizeFailure($testName, $message, &$failures, $patterns) {
    $categorized = false;

    foreach ($patterns as $category => $pattern) {
        if (preg_match($pattern, $message)) {
            // Extract specific error type
            $errorType = extractErrorType($message, $category);

            $failures[$category][] = [
                'test' => $testName,
                'message' => substr($message, 0, 200),
                'error_type' => $errorType
            ];
            $categorized = true;
            break;
        }
    }

    // Check for assertion failures
    if (!$categorized && (strpos($message, 'Failed asserting') !== false || strpos($message, 'AssertionFailedError') !== false)) {
        $errorType = extractAssertionType($message);
        $failures['assertion_failures'][] = [
            'test' => $testName,
            'message' => substr($message, 0, 200),
            'error_type' => $errorType
        ];
        $categorized = true;
    }

    // Catchall for other errors
    if (!$categorized) {
        $failures['other'][] = [
            'test' => $testName,
            'message' => substr($message, 0, 200),
            'error_type' => 'Unknown error'
        ];
    }
}

function extractErrorType($message, $category) {
    switch ($category) {
        case 'missing_classes':
            if (preg_match('/Class ["\']?([^\s"\']+)["\']? not found/', $message, $matches)) {
                return 'Missing class: ' . $matches[1];
            }
            break;

        case 'missing_methods':
            if (preg_match('/Call to undefined method ([^\(]+)/', $message, $matches)) {
                return 'Missing method: ' . $matches[1];
            }
            if (preg_match('/Method ([^\s]+) does not exist/', $message, $matches)) {
                return 'Missing method: ' . $matches[1];
            }
            break;

        case 'null_constraints':
            if (preg_match('/null value in column "([^"]+)"/', $message, $matches)) {
                return 'NULL in column: ' . $matches[1];
            }
            return 'NULL constraint violation';

        case 'undefined_columns':
            if (preg_match('/column "?([^"\s]+)"? does not exist/', $message, $matches)) {
                return 'Missing column: ' . $matches[1];
            }
            break;

        case 'undefined_tables':
            if (preg_match('/relation "?([^"\s]+)"? does not exist/', $message, $matches)) {
                return 'Missing table: ' . $matches[1];
            }
            if (preg_match('/table "?([^"\s]+)"? does not exist/', $message, $matches)) {
                return 'Missing table: ' . $matches[1];
            }
            break;
    }

    return substr($message, 0, 100);
}

function extractAssertionType($message) {
    if (preg_match('/Failed asserting that (.+)/', $message, $matches)) {
        return 'Assertion: ' . substr($matches[1], 0, 80);
    }
    return 'Assertion failed';
}
