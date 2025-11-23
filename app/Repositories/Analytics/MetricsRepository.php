<?php

namespace App\Repositories\Analytics;

use App\Models\Analytics\Metric;
use App\Models\Analytics\MetricDefinition;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * Metrics Repository
 *
 * Provides high-level interface for recording and retrieving metrics.
 *
 * @package App\Repositories\Analytics
 */
class MetricsRepository
{
    /**
     * Record a metric value
     */
    public function record(
        string $entityType,
        string $entityId,
        string $metricName,
        $value,
        ?string $platform = null,
        array $options = []
    ): Metric {
        return Metric::record($entityType, $entityId, $metricName, $value, $platform, $options);
    }

    /**
     * Record multiple metrics at once
     */
    public function recordBatch(
        string $entityType,
        string $entityId,
        array $metrics,
        ?string $platform = null,
        array $options = []
    ): Collection {
        $recorded = collect();

        foreach ($metrics as $metricName => $value) {
            $recorded->push(
                $this->record($entityType, $entityId, $metricName, $value, $platform, $options)
            );
        }

        return $recorded;
    }

    /**
     * Get metrics for an entity
     */
    public function getEntityMetrics(
        string $entityType,
        string $entityId,
        ?array $metricNames = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): Collection {
        $query = Metric::forEntity($entityType, $entityId)->latest();

        if ($metricNames) {
            $query->whereIn('metric_name', $metricNames);
        }

        if ($startDate && $endDate) {
            $query->dateRange($startDate, $endDate);
        }

        return $query->get();
    }

    /**
     * Get latest value for a specific metric
     */
    public function getLatestValue(
        string $entityType,
        string $entityId,
        string $metricName
    ): mixed {
        $metric = Metric::forEntity($entityType, $entityId)
            ->metric($metricName)
            ->latest()
            ->first();

        return $metric ? $metric->getValue() : null;
    }

    /**
     * Get aggregated metrics for time period
     */
    public function getAggregated(
        string $entityType,
        string $entityId,
        array $metricNames,
        string $startDate,
        string $endDate,
        string $aggregation = 'sum' // sum, avg, min, max
    ): array {
        $metrics = Metric::forEntity($entityType, $entityId)
            ->whereIn('metric_name', $metricNames)
            ->dateRange($startDate, $endDate)
            ->numeric()
            ->get();

        $result = [];
        foreach ($metricNames as $metricName) {
            $values = $metrics->where('metric_name', $metricName)->pluck('value_numeric');

            $result[$metricName] = match($aggregation) {
                'sum' => $values->sum(),
                'avg' => $values->avg(),
                'min' => $values->min(),
                'max' => $values->max(),
                default => $values->sum(),
            };
        }

        return $result;
    }

    /**
     * Get trend data (daily/hourly/monthly)
     */
    public function getTrend(
        string $entityType,
        string $entityId,
        string $metricName,
        string $startDate,
        string $endDate,
        string $interval = 'daily' // daily, hourly, monthly
    ): Collection {
        return Metric::forEntity($entityType, $entityId)
            ->metric($metricName)
            ->dateRange($startDate, $endDate)
            ->numeric()
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(function ($metric) use ($interval) {
                return match($interval) {
                    'hourly' => $metric->recorded_at->format('Y-m-d H:00'),
                    'monthly' => $metric->recorded_at->format('Y-m'),
                    default => $metric->recorded_at->format('Y-m-d'),
                };
            })
            ->map(function ($group) {
                return $group->sum('value_numeric');
            });
    }

    /**
     * Get all metric definitions
     */
    public function getDefinitions(?string $category = null): Collection
    {
        $query = MetricDefinition::active()->orderBy('sort_order');

        if ($category) {
            $query->category($category);
        }

        return $query->get();
    }
}
