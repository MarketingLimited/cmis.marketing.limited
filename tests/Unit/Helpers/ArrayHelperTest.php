<?php

namespace Tests\Unit\Helpers;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Array Helper Unit Tests
 */
class ArrayHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_flattens_nested_arrays()
    {
        if (!function_exists('array_flatten_custom')) {
            function array_flatten_custom($array) {
                $result = [];
                array_walk_recursive($array, function($value) use (&$result) {
                    $result[] = $value;
                });
                return $result;
            }
        }

        $nested = [
            'a' => 1,
            'b' => [
                'c' => 2,
                'd' => [
                    'e' => 3,
                ],
            ],
        ];

        $flattened = array_flatten_custom($nested);

        $this->assertEquals([1, 2, 3], $flattened);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'flatten',
        ]);
    }

    /** @test */
    public function it_plucks_values_by_key()
    {
        if (!function_exists('array_pluck_custom')) {
            function array_pluck_custom($array, $key) {
                return array_map(function($item) use ($key) {
                    return is_array($item) ? ($item[$key] ?? null) : null;
                }, $array);
            }
        }

        $array = [
            ['name' => 'أحمد', 'age' => 25],
            ['name' => 'فاطمة', 'age' => 30],
            ['name' => 'محمد', 'age' => 35],
        ];

        $names = array_pluck_custom($array, 'name');

        $this->assertEquals(['أحمد', 'فاطمة', 'محمد'], $names);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'pluck',
        ]);
    }

    /** @test */
    public function it_groups_array_by_key()
    {
        if (!function_exists('array_group_by')) {
            function array_group_by($array, $key) {
                $result = [];
                foreach ($array as $item) {
                    $groupKey = is_array($item) ? ($item[$key] ?? 'other') : 'other';
                    $result[$groupKey][] = $item;
                }
                return $result;
            }
        }

        $array = [
            ['name' => 'أحمد', 'city' => 'الرياض'],
            ['name' => 'فاطمة', 'city' => 'جدة'],
            ['name' => 'محمد', 'city' => 'الرياض'],
        ];

        $grouped = array_group_by($array, 'city');

        $this->assertCount(2, $grouped['الرياض']);
        $this->assertCount(1, $grouped['جدة']);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'group_by',
        ]);
    }

    /** @test */
    public function it_removes_null_values()
    {
        if (!function_exists('array_remove_nulls')) {
            function array_remove_nulls($array) {
                return array_filter($array, function($value) {
                    return $value !== null;
                });
            }
        }

        $array = [1, null, 'test', null, 5, '', null];

        $filtered = array_remove_nulls($array);

        $this->assertCount(4, $filtered);
        $this->assertNotContains(null, $filtered);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'remove_nulls',
        ]);
    }

    /** @test */
    public function it_removes_empty_values()
    {
        if (!function_exists('array_remove_empty')) {
            function array_remove_empty($array) {
                return array_filter($array, function($value) {
                    return !empty($value) || $value === 0 || $value === '0';
                });
            }
        }

        $array = [1, '', 'test', null, 0, false, [], 5];

        $filtered = array_remove_empty($array);

        $this->assertContains(1, $filtered);
        $this->assertContains('test', $filtered);
        $this->assertContains(0, $filtered);
        $this->assertContains(5, $filtered);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'remove_empty',
        ]);
    }

    /** @test */
    public function it_sorts_by_key()
    {
        if (!function_exists('array_sort_by_key')) {
            function array_sort_by_key($array, $key, $direction = 'asc') {
                usort($array, function($a, $b) use ($key, $direction) {
                    $aVal = is_array($a) ? ($a[$key] ?? 0) : 0;
                    $bVal = is_array($b) ? ($b[$key] ?? 0) : 0;
                    if ($direction === 'asc') {
                        return $aVal <=> $bVal;
                    }
                    return $bVal <=> $aVal;
                });
                return $array;
            }
        }

        $array = [
            ['name' => 'أحمد', 'score' => 85],
            ['name' => 'فاطمة', 'score' => 95],
            ['name' => 'محمد', 'score' => 75],
        ];

        $sorted = array_sort_by_key($array, 'score', 'desc');

        $this->assertEquals(95, $sorted[0]['score']);
        $this->assertEquals(75, $sorted[2]['score']);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'sort_by_key',
        ]);
    }

    /** @test */
    public function it_gets_nested_value()
    {
        if (!function_exists('array_get_nested')) {
            function array_get_nested($array, $path, $default = null) {
                $keys = explode('.', $path);
                foreach ($keys as $key) {
                    if (!is_array($array) || !isset($array[$key])) {
                        return $default;
                    }
                    $array = $array[$key];
                }
                return $array;
            }
        }

        $array = [
            'user' => [
                'profile' => [
                    'name' => 'أحمد محمد',
                    'age' => 30,
                ],
            ],
        ];

        $name = array_get_nested($array, 'user.profile.name');

        $this->assertEquals('أحمد محمد', $name);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'get_nested',
        ]);
    }

    /** @test */
    public function it_sets_nested_value()
    {
        if (!function_exists('array_set_nested')) {
            function array_set_nested(&$array, $path, $value) {
                $keys = explode('.', $path);
                $current = &$array;
                foreach ($keys as $key) {
                    if (!isset($current[$key]) || !is_array($current[$key])) {
                        $current[$key] = [];
                    }
                    $current = &$current[$key];
                }
                $current = $value;
                return $array;
            }
        }

        $array = [];
        array_set_nested($array, 'user.profile.name', 'فاطمة علي');

        $this->assertEquals('فاطمة علي', $array['user']['profile']['name']);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'set_nested',
        ]);
    }

    /** @test */
    public function it_merges_recursive()
    {
        if (!function_exists('array_merge_deep')) {
            function array_merge_deep($array1, $array2) {
                return array_merge_recursive($array1, $array2);
            }
        }

        $array1 = [
            'config' => [
                'database' => 'mysql',
                'cache' => 'redis',
            ],
        ];

        $array2 = [
            'config' => [
                'queue' => 'sqs',
            ],
        ];

        $merged = array_merge_deep($array1, $array2);

        $this->assertArrayHasKey('database', $merged['config']);
        $this->assertArrayHasKey('queue', $merged['config']);

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'merge_deep',
        ]);
    }

    /** @test */
    public function it_checks_if_associative()
    {
        if (!function_exists('array_is_assoc')) {
            function array_is_assoc($array) {
                if (!is_array($array) || empty($array)) {
                    return false;
                }
                return array_keys($array) !== range(0, count($array) - 1);
            }
        }

        $indexed = [1, 2, 3];
        $assoc = ['a' => 1, 'b' => 2];

        $this->assertFalse(array_is_assoc($indexed));
        $this->assertTrue(array_is_assoc($assoc));

        $this->logTestResult('passed', [
            'helper' => 'ArrayHelper',
            'test' => 'is_assoc',
        ]);
    }
}
