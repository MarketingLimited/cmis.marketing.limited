<?php

namespace App\Repositories\Dev;

use Illuminate\Support\Facades\DB;

/**
 * Repository for CMIS Dev Task Functions
 * Encapsulates PostgreSQL functions related to development tasks and automation
 */
class DevTaskRepository
{
    /**
     * Create a new development task
     * Corresponds to: cmis_dev.create_dev_task()
     *
     * @param string $name Task name
     * @param string $description Task description
     * @param string $scopeCode Scope code
     * @param array $executionPlan Execution plan (will be converted to JSONB)
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
            'SELECT cmis_dev.create_dev_task(?, ?, ?, ?::jsonb, ?) as task_id',
            [$name, $description, $scopeCode, json_encode($executionPlan), $priority]
        );

        return $result[0]->task_id;
    }

    /**
     * Auto context task loader - loads context and creates task
     * Corresponds to: cmis_dev.auto_context_task_loader()
     *
     * @param string $prompt Task prompt
     * @param string|null $domain Domain filter (optional)
     * @param string $category Category (default: 'dev')
     * @param string $scopeCode Scope code (default: 'system_dev')
     * @param int $priority Priority level (default: 3)
     * @param int $tokenLimit Token limit (default: 5000)
     * @return object|null JSON object containing task details and context
     */
    public function autoContextTaskLoader(
        string $prompt,
        ?string $domain = null,
        string $category = 'dev',
        string $scopeCode = 'system_dev',
        int $priority = 3,
        int $tokenLimit = 5000
    ): ?object {
        $results = DB::select(
            'SELECT cmis_dev.auto_context_task_loader(?, ?, ?, ?, ?, ?) as result',
            [$prompt, $domain, $category, $scopeCode, $priority, $tokenLimit]
        );

        return $results[0]->result ?? null;
    }

    /**
     * Prepare context for execution
     * Corresponds to: cmis_dev.prepare_context_execution()
     *
     * @param string $prompt Task prompt
     * @param string|null $domain Domain filter (optional)
     * @param string $category Category (default: 'dev')
     * @param string $scopeCode Scope code (default: 'system_dev')
     * @param int $priority Priority level (default: 3)
     * @return object|null JSON object containing execution context
     */
    public function prepareContextExecution(
        string $prompt,
        ?string $domain = null,
        string $category = 'dev',
        string $scopeCode = 'system_dev',
        int $priority = 3
    ): ?object {
        $results = DB::select(
            'SELECT cmis_dev.prepare_context_execution(?, ?, ?, ?, ?) as context',
            [$prompt, $domain, $category, $scopeCode, $priority]
        );

        return $results[0]->context ?? null;
    }

    /**
     * Run a development task
     * Corresponds to: cmis_dev.run_dev_task()
     *
     * @param string $prompt Task prompt
     * @return object|null JSON object containing task execution results
     */
    public function runDevTask(string $prompt): ?object
    {
        $results = DB::select(
            'SELECT cmis_dev.run_dev_task(?) as result',
            [$prompt]
        );

        return $results[0]->result ?? null;
    }

    /**
     * Run a marketing task
     * Corresponds to: cmis_dev.run_marketing_task()
     *
     * @param string $prompt Task prompt
     * @return object|null JSON object containing task execution results
     */
    public function runMarketingTask(string $prompt): ?object
    {
        $results = DB::select(
            'SELECT cmis_dev.run_marketing_task(?) as result',
            [$prompt]
        );

        return $results[0]->result ?? null;
    }

    /**
     * Run an improved marketing task
     * Corresponds to: cmis_dev.run_marketing_task_improved()
     *
     * @param string $prompt Task prompt
     * @return object|null JSON object containing task execution results
     */
    public function runMarketingTaskImproved(string $prompt): ?object
    {
        $results = DB::select(
            'SELECT cmis_dev.run_marketing_task_improved(?) as result',
            [$prompt]
        );

        return $results[0]->result ?? null;
    }

    /**
     * Search marketing knowledge
     * Corresponds to: cmis_dev.search_marketing_knowledge()
     *
     * @param string $prompt Search prompt
     * @return object|null JSON object containing search results
     */
    public function searchMarketingKnowledge(string $prompt): ?object
    {
        $results = DB::select(
            'SELECT cmis_dev.search_marketing_knowledge(?) as knowledge',
            [$prompt]
        );

        return $results[0]->knowledge ?? null;
    }
}
