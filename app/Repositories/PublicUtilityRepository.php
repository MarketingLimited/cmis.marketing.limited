<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Repository for Public Utility Functions
 * Encapsulates PostgreSQL functions in the public schema
 */
class PublicUtilityRepository
{
    /**
     * Auto analyze knowledge - Updates verification status
     * Corresponds to: public.auto_analyze_knowledge()
     *
     * @return bool Success status
     */
    public function autoAnalyzeKnowledge(): bool
    {
        return DB::statement('SELECT public.auto_analyze_knowledge()');
    }

    /**
     * Auto snapshot diff - Periodic temporal analysis
     * Corresponds to: public.auto_snapshot_diff()
     *
     * @return bool Success status
     */
    public function autoSnapshotDiff(): bool
    {
        return DB::statement('SELECT public.auto_snapshot_diff()');
    }

    /**
     * Cognitive console report
     * Corresponds to: public.cognitive_console_report()
     *
     * @param string $mode Report mode (default: 'summary')
     * @return Collection Collection of cognitive reports
     */
    public function cognitiveConsoleReport(string $mode = 'summary'): Collection
    {
        $results = DB::select(
            'SELECT * FROM public.cognitive_console_report(?)',
            [$mode]
        );

        return collect($results);
    }

    /**
     * Cognitive feedback loop
     * Corresponds to: public.cognitive_feedback_loop()
     *
     * @return bool Success status
     */
    public function cognitiveFeedbackLoop(): bool
    {
        return DB::statement('SELECT public.cognitive_feedback_loop()');
    }

    /**
     * Cognitive learning loop
     * Corresponds to: public.cognitive_learning_loop()
     *
     * @return bool Success status
     */
    public function cognitiveLearningLoop(): bool
    {
        return DB::statement('SELECT public.cognitive_learning_loop()');
    }

    /**
     * Compute epistemic delta
     * Corresponds to: public.compute_epistemic_delta()
     *
     * @return bool Success status
     */
    public function computeEpistemicDelta(): bool
    {
        return DB::statement('SELECT public.compute_epistemic_delta()');
    }

    /**
     * Create development task (public schema version)
     * Corresponds to: public.create_dev_task()
     *
     * @param string $name Task name
     * @param string $description Task description
     * @param string $scopeCode Scope code
     * @param array $executionPlan Execution plan
     * @param int $priority Priority level (default: 3)
     * @return string UUID of created task
     */
    public function createDevTask(
        string $name,
        string $description,
        string $scopeCode,
        array $executionPlan,
        int $priority = 3
    ): string {
        $result = DB::select(
            'SELECT public.create_dev_task(?, ?, ?, ?::jsonb, ?) as task_id',
            [$name, $description, $scopeCode, json_encode($executionPlan), $priority]
        );

        return $result[0]->task_id;
    }

    /**
     * Generate cognitive health report
     * Corresponds to: public.generate_cognitive_health_report()
     *
     * @return bool Success status
     */
    public function generateCognitiveHealthReport(): bool
    {
        return DB::statement('SELECT public.generate_cognitive_health_report()');
    }

    /**
     * Get all report summaries
     * Corresponds to: public.get_all_report_summaries()
     *
     * @param int $length Summary length (default: 500)
     * @return Collection Collection of report summaries
     */
    public function getAllReportSummaries(int $length = 500): Collection
    {
        $results = DB::select(
            'SELECT * FROM public.get_all_report_summaries(?)',
            [$length]
        );

        return collect($results);
    }

    /**
     * Get latest official report for a domain
     * Corresponds to: public.get_latest_official_report()
     *
     * @param string $domain Domain name
     * @return Collection Collection containing the latest official report
     */
    public function getLatestOfficialReport(string $domain): Collection
    {
        $results = DB::select(
            'SELECT * FROM public.get_latest_official_report(?)',
            [$domain]
        );

        return collect($results);
    }

    /**
     * Get latest reports by all phases
     * Corresponds to: public.get_latest_reports_by_all_phases()
     *
     * @return Collection Collection of latest reports by phase
     */
    public function getLatestReportsByAllPhases(): Collection
    {
        $results = DB::select('SELECT * FROM public.get_latest_reports_by_all_phases()');

        return collect($results);
    }

    /**
     * Get official reports
     * Corresponds to: public.get_official_reports()
     *
     * @return Collection Collection of official reports
     */
    public function getOfficialReports(): Collection
    {
        $results = DB::select('SELECT * FROM public.get_official_reports()');

        return collect($results);
    }

    /**
     * Get report summary by phase
     * Corresponds to: public.get_report_summary_by_phase()
     *
     * @param string $phase Report phase
     * @param int $length Summary length (default: 500)
     * @return Collection Collection containing report summary
     */
    public function getReportSummaryByPhase(string $phase, int $length = 500): Collection
    {
        $results = DB::select(
            'SELECT * FROM public.get_report_summary_by_phase(?, ?)',
            [$phase, $length]
        );

        return collect($results);
    }

    /**
     * Get reports by phase
     * Corresponds to: public.get_reports_by_phase()
     *
     * @param string $phase Report phase
     * @return Collection Collection of reports for the specified phase
     */
    public function getReportsByPhase(string $phase): Collection
    {
        $results = DB::select(
            'SELECT * FROM public.get_reports_by_phase(?)',
            [$phase]
        );

        return collect($results);
    }

    /**
     * Load context by priority
     * Corresponds to: public.load_context_by_priority()
     *
     * @param string $domain Domain name
     * @param string|null $category Category filter (optional)
     * @param int $maxTokens Maximum tokens to load (default: 5000)
     * @return Collection Collection of knowledge entries by priority
     */
    public function loadContextByPriority(
        string $domain,
        ?string $category = null,
        int $maxTokens = 5000
    ): Collection {
        $results = DB::select(
            'SELECT * FROM public.load_context_by_priority(?, ?, ?)',
            [$domain, $category, $maxTokens]
        );

        return collect($results);
    }

    /**
     * Log cognitive vitality
     * Corresponds to: public.log_cognitive_vitality()
     *
     * @return bool Success status
     */
    public function logCognitiveVitality(): bool
    {
        return DB::statement('SELECT public.log_cognitive_vitality()');
    }

    /**
     * Reconstruct knowledge from chunks
     * Corresponds to: public.reconstruct_knowledge()
     *
     * @param string $parentId Parent knowledge UUID
     * @return string Reconstructed content
     */
    public function reconstructKnowledge(string $parentId): string
    {
        $result = DB::select(
            'SELECT public.reconstruct_knowledge(?) as content',
            [$parentId]
        );

        return $result[0]->content ?? '';
    }

    /**
     * Register chunked knowledge
     * Corresponds to: public.register_chunked_knowledge()
     *
     * @param string $domain Domain name
     * @param string $category Category
     * @param string $topic Topic
     * @param string $content Content text
     * @param int $chunkSize Chunk size (default: 2000)
     * @return string UUID of registered knowledge
     */
    public function registerChunkedKnowledge(
        string $domain,
        string $category,
        string $topic,
        string $content,
        int $chunkSize = 2000
    ): string {
        $result = DB::select(
            'SELECT public.register_chunked_knowledge(?, ?, ?, ?, ?) as knowledge_id',
            [$domain, $category, $topic, $content, $chunkSize]
        );

        return $result[0]->knowledge_id;
    }

    /**
     * Register knowledge (public schema version)
     * Corresponds to: public.register_knowledge()
     *
     * @param string $domain Domain name
     * @param string $category Category
     * @param string $topic Topic
     * @param string $content Content text
     * @param int $tier Priority tier (default: 2)
     * @param array $keywords Array of keywords (optional)
     * @return string UUID of registered knowledge
     */
    public function registerKnowledge(
        string $domain,
        string $category,
        string $topic,
        string $content,
        int $tier = 2,
        array $keywords = []
    ): string {
        $result = DB::select(
            'SELECT public.register_knowledge(?, ?, ?, ?, ?, ?) as knowledge_id',
            [
                $domain,
                $category,
                $topic,
                $content,
                $tier,
                DB::raw("ARRAY['" . implode("','", $keywords) . "']")
            ]
        );

        return $result[0]->knowledge_id;
    }

    /**
     * Run auto predictive trigger
     * Corresponds to: public.run_auto_predictive_trigger()
     *
     * @return bool Success status
     */
    public function runAutoPredictiveTrigger(): bool
    {
        return DB::statement('SELECT public.run_auto_predictive_trigger()');
    }

    /**
     * Scheduled cognitive trend update
     * Corresponds to: public.scheduled_cognitive_trend_update()
     *
     * @return bool Success status
     */
    public function scheduledCognitiveTrendUpdate(): bool
    {
        return DB::statement('SELECT public.scheduled_cognitive_trend_update()');
    }

    /**
     * Search cognitive knowledge
     * Corresponds to: public.search_cognitive_knowledge()
     *
     * @param string $query Search query
     * @param string|null $domain Domain filter (optional)
     * @param string $category Category (default: 'dev')
     * @param int $batchLimit Results per batch (default: 20)
     * @param int $offset Offset for pagination (default: 0)
     * @return Collection Collection of search results
     */
    public function searchCognitiveKnowledge(
        string $query,
        ?string $domain = null,
        string $category = 'dev',
        int $batchLimit = 20,
        int $offset = 0
    ): Collection {
        $results = DB::select(
            'SELECT * FROM public.search_cognitive_knowledge(?, ?, ?, ?, ?)',
            [$query, $domain, $category, $batchLimit, $offset]
        );

        return collect($results);
    }

    /**
     * Search cognitive knowledge (simple version)
     * Corresponds to: public.search_cognitive_knowledge_simple()
     *
     * @param string $query Search query
     * @param int $batchLimit Results per batch (default: 25)
     * @param int $offset Offset for pagination (default: 0)
     * @return Collection Collection of search results
     */
    public function searchCognitiveKnowledgeSimple(
        string $query,
        int $batchLimit = 25,
        int $offset = 0
    ): Collection {
        $results = DB::select(
            'SELECT * FROM public.search_cognitive_knowledge_simple(?, ?, ?)',
            [$query, $batchLimit, $offset]
        );

        return collect($results);
    }

    /**
     * Update cognitive trends
     * Corresponds to: public.update_cognitive_trends()
     *
     * @return bool Success status
     */
    public function updateCognitiveTrends(): bool
    {
        return DB::statement('SELECT public.update_cognitive_trends()');
    }

    /**
     * Update knowledge chunk
     * Corresponds to: public.update_knowledge_chunk()
     *
     * @param string $parentId Parent knowledge UUID
     * @param int $partIndex Part index
     * @param string $newContent New content
     * @return bool Success status
     */
    public function updateKnowledgeChunk(
        string $parentId,
        int $partIndex,
        string $newContent
    ): bool {
        return DB::statement(
            'SELECT public.update_knowledge_chunk(?, ?, ?)',
            [$parentId, $partIndex, $newContent]
        );
    }
}
