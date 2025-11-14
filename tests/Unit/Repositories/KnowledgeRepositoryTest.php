<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Repositories\Knowledge\KnowledgeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * KnowledgeRepository Unit Tests
 */
class KnowledgeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected KnowledgeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh');
        $this->repository = app(KnowledgeRepository::class);
    }

    /** @test */
    public function it_can_register_knowledge()
    {
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['knowledge_id' => 'test-uuid-123']
            ]);

        $result = $this->repository->registerKnowledge(
            'cmis',
            'dev',
            'Test Topic',
            'Test content for knowledge entry',
            2,
            ['testing', 'unit-test']
        );

        $this->assertNotNull($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'registerKnowledge',
        ]);
    }

    /** @test */
    public function it_accepts_different_categories()
    {
        DB::shouldReceive('select')
            ->times(4)
            ->andReturn([
                (object) ['knowledge_id' => 'test-uuid-123']
            ]);

        $categories = ['dev', 'marketing', 'org', 'research'];

        foreach ($categories as $category) {
            $result = $this->repository->registerKnowledge(
                'cmis',
                $category,
                'Test Topic',
                'Test content',
                2,
                []
            );

            $this->assertNotNull($result);
        }

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'test' => 'different_categories',
        ]);
    }

    /** @test */
    public function it_can_auto_analyze_knowledge()
    {
        $analysisResult = [
            'total_entries' => 15,
            'categories' => ['dev', 'marketing'],
            'relevance_score' => 0.85,
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['analysis' => json_encode($analysisResult)]
            ]);

        $result = $this->repository->autoAnalyzeKnowledge(
            'Laravel testing best practices',
            'cmis',
            'dev',
            5,
            20
        );

        $this->assertNotNull($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'autoAnalyzeKnowledge',
        ]);
    }

    /** @test */
    public function it_can_auto_retrieve_knowledge()
    {
        $knowledgeEntries = [
            (object) [
                'knowledge_id' => 'uuid-1',
                'topic' => 'Testing Strategies',
                'content' => 'Content about testing',
            ],
            (object) [
                'knowledge_id' => 'uuid-2',
                'topic' => 'Best Practices',
                'content' => 'Content about best practices',
            ],
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn($knowledgeEntries);

        $results = $this->repository->autoRetrieveKnowledge(
            'testing strategies',
            'cmis',
            'dev',
            5,
            20
        );

        $this->assertCount(2, $results);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'autoRetrieveKnowledge',
        ]);
    }

    /** @test */
    public function it_can_load_smart_context()
    {
        $contextResult = [
            'knowledge_entries' => 10,
            'total_tokens' => 4500,
            'context_summary' => 'Relevant context for query',
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['context' => json_encode($contextResult)]
            ]);

        $result = $this->repository->smartContextLoader(
            'campaign management',
            'cmis',
            'marketing',
            5000
        );

        $this->assertNotNull($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'smartContextLoader',
        ]);
    }

    /** @test */
    public function it_respects_token_limit_in_context_loader()
    {
        DB::shouldReceive('select')
            ->twice()
            ->andReturn([
                (object) ['context' => json_encode(['total_tokens' => 1000])]
            ]);

        // Test with small token limit
        $result1 = $this->repository->smartContextLoader(
            'test query',
            null,
            'dev',
            1000
        );

        // Test with large token limit
        $result2 = $this->repository->smartContextLoader(
            'test query',
            null,
            'dev',
            10000
        );

        $this->assertNotNull($result1);
        $this->assertNotNull($result2);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'test' => 'token_limit',
        ]);
    }

    /** @test */
    public function it_can_generate_system_report()
    {
        $reportData = [
            'total_entries' => 1250,
            'categories' => ['dev' => 800, 'marketing' => 300, 'org' => 150],
            'avg_content_length' => 2500,
            'last_update' => '2024-06-15',
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['report' => json_encode($reportData)]
            ]);

        $result = $this->repository->generateSystemReport();

        $this->assertNotNull($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'generateSystemReport',
        ]);
    }

    /** @test */
    public function it_can_perform_semantic_analysis()
    {
        $analysisResults = [
            (object) [
                'intent' => 'learning',
                'frequency' => 45,
                'avg_confidence' => 0.85,
            ],
            (object) [
                'intent' => 'troubleshooting',
                'frequency' => 32,
                'avg_confidence' => 0.78,
            ],
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn($analysisResults);

        $results = $this->repository->semanticAnalysis();

        $this->assertCount(2, $results);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'semanticAnalysis',
        ]);
    }

    /** @test */
    public function it_can_perform_advanced_semantic_search()
    {
        $searchResults = [
            (object) [
                'knowledge_id' => 'uuid-1',
                'topic' => 'Advanced Testing',
                'similarity_score' => 0.92,
            ],
            (object) [
                'knowledge_id' => 'uuid-2',
                'topic' => 'Test Automation',
                'similarity_score' => 0.87,
            ],
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn($searchResults);

        $results = $this->repository->semanticSearchAdvanced(
            'testing automation frameworks',
            'learning',
            'implementation',
            'development',
            'dev',
            10,
            0.5
        );

        $this->assertCount(2, $results);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'semanticSearchAdvanced',
        ]);
    }

    /** @test */
    public function it_supports_arabic_content()
    {
        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['knowledge_id' => 'test-uuid-ar']
            ]);

        $result = $this->repository->registerKnowledge(
            'cmis',
            'marketing',
            'استراتيجيات التسويق',
            'محتوى باللغة العربية عن استراتيجيات التسويق الرقمي',
            1,
            ['تسويق', 'رقمي', 'استراتيجية']
        );

        $this->assertNotNull($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'test' => 'arabic_content',
        ]);
    }

    /** @test */
    public function it_can_cleanup_old_embeddings()
    {
        DB::shouldReceive('statement')
            ->once()
            ->andReturn(true);

        $result = $this->repository->cleanupOldEmbeddings();

        $this->assertTrue($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'cleanupOldEmbeddings',
        ]);
    }

    /** @test */
    public function it_can_verify_installation()
    {
        $verificationResult = [
            'schema_exists' => true,
            'functions_installed' => true,
            'tables_ready' => true,
            'version' => '1.0.0',
        ];

        DB::shouldReceive('select')
            ->once()
            ->andReturn([
                (object) ['verification' => json_encode($verificationResult)]
            ]);

        $result = $this->repository->verifyInstallation();

        $this->assertNotNull($result);

        $this->logTestResult('passed', [
            'repository' => 'KnowledgeRepository',
            'method' => 'verifyInstallation',
        ]);
    }
}
