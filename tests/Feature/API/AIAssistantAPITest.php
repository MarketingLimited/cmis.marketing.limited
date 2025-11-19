<?php

namespace Tests\Feature\API;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * AI Assistant API Feature Tests
 */
class AIAssistantAPITest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_generate_content_suggestions()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-suggestions', [
            'prompt' => 'اقترح أفكار لحملة تسويقية لمنتج جديد',
            'context' => 'منتج تقني للشباب',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['suggestions'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-suggestions',
        ]);
    }

    /** @test */
    public function it_can_generate_campaign_brief()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-brief', [
            'product_description' => 'قميص صيفي قطني عالي الجودة',
            'target_audience' => 'شباب 18-35',
            'campaign_goal' => 'زيادة المبيعات',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['brief'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-brief',
        ]);
    }

    /** @test */
    public function it_can_generate_visual_description()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-visual', [
            'product_info' => 'قميص أزرق، قطني، عصري',
            'mood' => 'صيفي، مفعم بالحيوية',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['description'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-visual',
        ]);
    }

    /** @test */
    public function it_can_extract_keywords()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/extract-keywords', [
            'content' => 'التسويق الرقمي هو أحد أهم أدوات النمو في العصر الحديث',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['keywords'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/extract-keywords',
        ]);
    }

    /** @test */
    public function it_can_generate_hashtags()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-hashtags', [
            'caption' => 'خصومات الصيف على جميع المنتجات!',
            'platform' => 'instagram',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['hashtags'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-hashtags',
        ]);
    }

    /** @test */
    public function it_can_analyze_sentiment()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/analyze-sentiment', [
            'text' => 'منتج رائع! أنا سعيد جداً بالشراء',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['sentiment', 'score'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/analyze-sentiment',
        ]);
    }

    /** @test */
    public function it_can_translate_content()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/translate', [
            'text' => 'Summer sale up to 50% off',
            'target_language' => 'ar',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['translated_text'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/translate',
        ]);
    }

    /** @test */
    public function it_can_generate_content_variations()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-variations', [
            'original_content' => 'خصومات الصيف - تسوق الآن!',
            'count' => 5,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['variations'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-variations',
        ]);
    }

    /** @test */
    public function it_can_generate_content_calendar()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-calendar', [
            'campaign_name' => 'حملة الصيف',
            'start_date' => '2024-06-01',
            'end_date' => '2024-08-31',
            'posts_per_week' => 7,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['calendar'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-calendar',
        ]);
    }

    /** @test */
    public function it_can_auto_categorize_content()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/categorize', [
            'content' => 'نصائح لزيادة التفاعل على فيسبوك وإنستقرام',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['category', 'confidence'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/categorize',
        ]);
    }

    /** @test */
    public function it_can_generate_meta_description()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-meta', [
            'content' => 'دليل شامل للتسويق عبر السوشيال ميديا في 2024',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['meta_description'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-meta',
        ]);
    }

    /** @test */
    public function it_can_suggest_improvements()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('success');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/suggest-improvements', [
            'content' => 'منتج جيد بسعر مناسب',
            'context' => 'caption',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['suggestions'],
        ]);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/suggest-improvements',
        ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-suggestions', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('prompt');

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-suggestions',
            'validation' => 'required_fields',
        ]);
    }

    /** @test */
    public function it_handles_ai_api_errors()
    {
        $setup = $this->createUserWithOrg();
        $user = $setup['user'];

        $this->mockGeminiAPI('error');

        $this->actingAs($user, 'sanctum');

        $response = $this->postJson('/api/ai/generate-suggestions', [
            'prompt' => 'Test prompt',
        ]);

        $response->assertStatus(500);

        $this->logTestResult('passed', [
            'endpoint' => 'POST /api/ai/generate-suggestions',
            'test' => 'error_handling',
        ]);
    }
}
