<?php

namespace App\Services\Analytics;

use App\Models\Analytics\Experiment;
use App\Models\Analytics\ExperimentVariant;
use Illuminate\Support\Facades\Cache;

/**
 * Variant Assignment Service
 *
 * Handles assignment of users to experiment variants using various algorithms:
 * - Random Assignment: Weighted random selection
 * - Consistent Hash Assignment: Same user always gets same variant
 * - Adaptive Assignment: Dynamic allocation based on performance
 */
class VariantAssignmentService
{
    /**
     * Assign a variant to a user
     *
     * @param Experiment $experiment The experiment
     * @param string $userId The user identifier
     * @param string $algorithm Assignment algorithm: random, hash, adaptive
     * @return ExperimentVariant
     */
    public function assignVariant(
        Experiment $experiment,
        string $userId,
        string $algorithm = 'hash'
    ): ExperimentVariant {
        // Check if user already has an assignment
        $existingAssignment = $this->getExistingAssignment($experiment, $userId);
        if ($existingAssignment) {
            return $existingAssignment;
        }

        // Assign based on algorithm
        $variant = match($algorithm) {
            'random' => $this->randomAssignment($experiment),
            'hash' => $this->consistentHashAssignment($experiment, $userId),
            'adaptive' => $this->adaptiveAssignment($experiment),
            default => $this->consistentHashAssignment($experiment, $userId),
        };

        // Store assignment for consistency
        $this->storeAssignment($experiment, $userId, $variant);

        return $variant;
    }

    /**
     * Random weighted assignment based on traffic percentage
     *
     * @param Experiment $experiment
     * @return ExperimentVariant
     */
    protected function randomAssignment(Experiment $experiment): ExperimentVariant
    {
        $variants = $experiment->variants()->where('status', 'active')->get();

        if ($variants->isEmpty()) {
            throw new \RuntimeException('No active variants available');
        }

        // Weighted random selection
        $rand = mt_rand(1, 10000) / 100; // Random float 0.00-100.00
        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->traffic_percentage;
            if ($rand <= $cumulative) {
                return $variant;
            }
        }

        // Fallback to control
        return $variants->where('is_control', true)->first() ?? $variants->first();
    }

    /**
     * Consistent hash-based assignment
     * Ensures same user always gets same variant
     *
     * @param Experiment $experiment
     * @param string $userId
     * @return ExperimentVariant
     */
    protected function consistentHashAssignment(Experiment $experiment, string $userId): ExperimentVariant
    {
        $variants = $experiment->variants()->where('status', 'active')->get();

        if ($variants->isEmpty()) {
            throw new \RuntimeException('No active variants available');
        }

        // Create consistent hash from experiment ID + user ID
        $hash = hexdec(substr(md5($experiment->experiment_id . $userId), 0, 8));
        $bucket = ($hash % 10000) / 100; // Convert to 0.00-100.00

        $cumulative = 0;

        foreach ($variants as $variant) {
            $cumulative += $variant->traffic_percentage;
            if ($bucket <= $cumulative) {
                return $variant;
            }
        }

        // Fallback to control
        return $variants->where('is_control', true)->first() ?? $variants->first();
    }

    /**
     * Adaptive assignment based on performance
     * Gradually allocates more traffic to winning variants
     *
     * @param Experiment $experiment
     * @return ExperimentVariant
     */
    protected function adaptiveAssignment(Experiment $experiment): ExperimentVariant
    {
        $variants = $experiment->variants()->where('status', 'active')->get();

        if ($variants->isEmpty()) {
            throw new \RuntimeException('No active variants available');
        }

        // Use Thompson Sampling for multi-armed bandit
        $scores = [];

        foreach ($variants as $variant) {
            // Beta distribution sampling
            $alpha = $variant->conversions + 1; // Add 1 for prior
            $beta = ($variant->impressions - $variant->conversions) + 1;

            // Simple approximation of beta distribution sampling
            $score = $this->betaSample($alpha, $beta);
            $scores[$variant->variant_id] = $score;
        }

        // Select variant with highest score
        arsort($scores);
        $winningVariantId = array_key_first($scores);

        return $variants->firstWhere('variant_id', $winningVariantId);
    }

    /**
     * Simple beta distribution sampling approximation
     *
     * @param float $alpha
     * @param float $beta
     * @return float
     */
    protected function betaSample(float $alpha, float $beta): float
    {
        // Use gamma distribution approximation
        $x = $this->gammaSample($alpha);
        $y = $this->gammaSample($beta);

        return $x / ($x + $y);
    }

    /**
     * Gamma distribution sampling (simple approximation)
     *
     * @param float $shape
     * @return float
     */
    protected function gammaSample(float $shape): float
    {
        // Simple approximation using normal distribution for large shape
        if ($shape > 1) {
            $d = $shape - 1.0 / 3.0;
            $c = 1.0 / sqrt(9.0 * $d);

            while (true) {
                $x = $this->normalSample();
                $v = 1.0 + $c * $x;

                if ($v > 0) {
                    $v = $v * $v * $v;
                    $u = mt_rand() / mt_getrandmax();

                    if ($u < 1.0 - 0.0331 * $x * $x * $x * $x) {
                        return $d * $v;
                    }

                    if (log($u) < 0.5 * $x * $x + $d * (1.0 - $v + log($v))) {
                        return $d * $v;
                    }
                }
            }
        }

        // For small shape, use simple exponential
        return -log(mt_rand() / mt_getrandmax()) / $shape;
    }

    /**
     * Standard normal distribution sample (Box-Muller transform)
     *
     * @return float
     */
    protected function normalSample(): float
    {
        $u1 = mt_rand() / mt_getrandmax();
        $u2 = mt_rand() / mt_getrandmax();

        return sqrt(-2.0 * log($u1)) * cos(2.0 * M_PI * $u2);
    }

    /**
     * Get existing assignment for user
     *
     * @param Experiment $experiment
     * @param string $userId
     * @return ExperimentVariant|null
     */
    protected function getExistingAssignment(Experiment $experiment, string $userId): ?ExperimentVariant
    {
        $cacheKey = "experiment:{$experiment->experiment_id}:user:{$userId}";

        $variantId = Cache::get($cacheKey);

        if ($variantId) {
            return ExperimentVariant::find($variantId);
        }

        return null;
    }

    /**
     * Store assignment for consistency
     *
     * @param Experiment $experiment
     * @param string $userId
     * @param ExperimentVariant $variant
     */
    protected function storeAssignment(
        Experiment $experiment,
        string $userId,
        ExperimentVariant $variant
    ): void {
        $cacheKey = "experiment:{$experiment->experiment_id}:user:{$userId}";

        // Store for experiment duration
        $ttl = $experiment->duration_days * 24 * 60; // Convert to minutes

        Cache::put($cacheKey, $variant->variant_id, $ttl);
    }

    /**
     * Assign variant and record impression
     *
     * @param Experiment $experiment
     * @param string $userId
     * @param array $context Additional context for the event
     * @return ExperimentVariant
     */
    public function assignAndRecordImpression(
        Experiment $experiment,
        string $userId,
        array $context = []
    ): ExperimentVariant {
        $variant = $this->assignVariant($experiment, $userId, $experiment->traffic_allocation);

        // Record impression event
        $eventService = app(ExperimentService::class);
        $eventService->recordEvent(
            $experiment->experiment_id,
            $variant->variant_id,
            'impression',
            array_merge([
                'user_id' => $userId,
                'session_id' => $context['session_id'] ?? null,
            ], $context)
        );

        return $variant;
    }

    /**
     * Clear assignment for user (useful for testing)
     *
     * @param Experiment $experiment
     * @param string $userId
     */
    public function clearAssignment(Experiment $experiment, string $userId): void
    {
        $cacheKey = "experiment:{$experiment->experiment_id}:user:{$userId}";
        Cache::forget($cacheKey);
    }

    /**
     * Get assignment statistics for an experiment
     *
     * @param Experiment $experiment
     * @return array
     */
    public function getAssignmentStats(Experiment $experiment): array
    {
        $variants = $experiment->variants()->get();

        $stats = [];

        foreach ($variants as $variant) {
            $stats[] = [
                'variant_id' => $variant->variant_id,
                'name' => $variant->name,
                'is_control' => $variant->is_control,
                'traffic_percentage' => (float) $variant->traffic_percentage,
                'impressions' => $variant->impressions,
                'actual_percentage' => $this->calculateActualPercentage($experiment, $variant),
            ];
        }

        return $stats;
    }

    /**
     * Calculate actual traffic percentage based on impressions
     *
     * @param Experiment $experiment
     * @param ExperimentVariant $variant
     * @return float
     */
    protected function calculateActualPercentage(Experiment $experiment, ExperimentVariant $variant): float
    {
        $totalImpressions = $experiment->variants()->sum('impressions');

        if ($totalImpressions === 0) {
            return 0;
        }

        return ($variant->impressions / $totalImpressions) * 100;
    }
}
