<?php

namespace Database\Seeders;

use App\Models\Social\BrandKnowledgeConfig;
use App\Models\Social\BrandKnowledgeDimension;
use App\Models\Social\MediaAsset;
use App\Models\Social\SocialPost;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Historical Content Seeder
 *
 * Seeds comprehensive test data for:
 * - Historical social posts from multiple platforms
 * - Media assets with visual analysis data
 * - Brand knowledge dimensions (marketing DNA)
 * - Brand knowledge configuration records
 */
class HistoricalContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first org and profile group for seeding
        $org = DB::table('cmis.orgs')->first();
        $profileGroup = DB::table('cmis.profile_groups')->where('org_id', $org->org_id)->first();

        if (!$org || !$profileGroup) {
            $this->command->error('No organization or profile group found. Please seed base data first.');
            return;
        }

        $this->command->info('Seeding historical content for org: ' . $org->name);

        // Set organization context for RLS
        DB::statement("SET app.current_org_id = '{$org->org_id}'");

        // 1. Seed Brand Knowledge Config
        $this->seedBrandKnowledgeConfig($org->org_id, $profileGroup->group_id);

        // 2. Seed Historical Social Posts
        $posts = $this->seedHistoricalPosts($org->org_id, $profileGroup->group_id);

        // 3. Seed Media Assets
        $this->seedMediaAssets($org->org_id, $posts);

        // 4. Seed Brand Knowledge Dimensions
        $this->seedBrandKnowledgeDimensions($org->org_id, $profileGroup->group_id, $posts);

        $this->command->info('Historical content seeding completed successfully!');
    }

    /**
     * Seed brand knowledge configuration
     */
    private function seedBrandKnowledgeConfig(string $orgId, string $profileGroupId): void
    {
        $this->command->info('Seeding brand knowledge config...');

        BrandKnowledgeConfig::create([
            'org_id' => $orgId,
            'profile_group_id' => $profileGroupId,
            'auto_build_enabled' => true,
            'auto_build_min_posts' => 50,
            'auto_build_min_days' => 7,
            'auto_analyze_new_posts' => true,
            'enabled_dimensions' => [
                'marketing_objectives', 'emotional_triggers', 'hooks',
                'tones', 'cta', 'color_palette', 'typography',
            ],
            'analysis_platforms' => ['instagram', 'facebook', 'tiktok', 'linkedin'],
            'min_success_percentile' => 75,
            'analyze_visual_content' => true,
            'analyze_video_content' => true,
            'total_posts_imported' => 0,
            'total_posts_analyzed' => 0,
            'total_success_posts' => 0,
            'total_dimensions_extracted' => 0,
            'notify_on_kb_ready' => true,
            'notify_on_analysis_complete' => true,
            'notify_on_import_milestone' => true,
            'max_concurrent_analysis' => 5,
            'daily_analysis_limit' => 100,
            'monthly_ai_budget' => 500.00,
            'current_month_spend' => 0.00,
        ]);

        $this->command->info('✓ Brand knowledge config created');
    }

    /**
     * Seed historical social posts
     */
    private function seedHistoricalPosts(string $orgId, string $profileGroupId): array
    {
        $this->command->info('Seeding historical social posts...');

        $posts = [];
        $platforms = ['instagram', 'facebook', 'tiktok', 'linkedin'];
        $mediaTypes = ['image', 'video', 'carousel'];

        // Create integration for posts
        $integration = DB::table('cmis.integrations')
            ->where('org_id', $orgId)
            ->first();

        if (!$integration) {
            $this->command->warn('No integration found, skipping posts');
            return [];
        }

        for ($i = 0; $i < 100; $i++) {
            $platform = $platforms[array_rand($platforms)];
            $mediaType = $mediaTypes[array_rand($mediaTypes)];
            $isSuccess = rand(1, 100) <= 30; // 30% are success posts

            $post = DB::table('cmis.social_posts')->insertGetId([
                'id' => DB::raw('gen_random_uuid()'),
                'org_id' => $orgId,
                'integration_id' => $integration->integration_id,
                'profile_group_id' => $profileGroupId,
                'post_external_id' => 'hist_' . $platform . '_' . uniqid(),
                'caption' => $this->generateCaption($platform, $i),
                'media_type' => $mediaType,
                'provider' => $platform,
                'source' => 'imported',
                'is_historical' => true,
                'is_schedulable' => false,
                'is_editable' => false,
                'is_analyzed' => rand(0, 1) === 1,
                'analysis_status' => rand(0, 1) === 1 ? 'completed' : 'pending',
                'is_in_knowledge_base' => $isSuccess,
                'success_score' => $isSuccess ? rand(70, 100) / 100 : rand(10, 69) / 100,
                'success_label' => $isSuccess ? 'high_performer' : 'average',
                'platform_metrics' => json_encode([
                    'likes' => rand(100, 10000),
                    'comments' => rand(10, 500),
                    'shares' => rand(5, 200),
                    'reach' => rand(1000, 50000),
                    'engagement_rate' => rand(200, 1500) / 100,
                ]),
                'posted_at' => now()->subDays(rand(1, 90)),
                'created_at' => now(),
                'updated_at' => now(),
            ], 'id');

            $posts[] = [
                'id' => $post,
                'platform' => $platform,
                'media_type' => $mediaType,
                'is_success' => $isSuccess,
            ];
        }

        $this->command->info('✓ ' . count($posts) . ' historical posts created');

        return $posts;
    }

    /**
     * Seed media assets with visual analysis
     */
    private function seedMediaAssets(string $orgId, array $posts): void
    {
        $this->command->info('Seeding media assets...');

        $count = 0;
        foreach ($posts as $post) {
            // Skip some posts (not all have media)
            if (rand(1, 10) > 8) {
                continue;
            }

            MediaAsset::create([
                'org_id' => $orgId,
                'post_id' => $post['id'],
                'media_type' => $post['media_type'],
                'original_url' => 'https://example.com/media/' . uniqid() . '.jpg',
                'storage_path' => 'historical/' . uniqid() . '.jpg',
                'file_name' => 'media_' . uniqid() . '.jpg',
                'mime_type' => $post['media_type'] === 'video' ? 'video/mp4' : 'image/jpeg',
                'file_size' => rand(500000, 5000000),
                'width' => 1080,
                'height' => 1080,
                'aspect_ratio' => 1.00,
                'is_analyzed' => true,
                'analysis_status' => 'completed',
                'analyzed_at' => now(),
                'visual_caption' => $this->generateVisualCaption(),
                'scene_description' => $this->generateSceneDescription(),
                'detected_objects' => $this->generateDetectedObjects(),
                'color_palette' => $this->generateColorPalette(),
                'typography' => $this->generateTypography(),
                'design_prompt' => $this->generateDesignPrompt(),
                'art_direction' => $this->generateArtDirection(),
                'mood' => $this->generateMood(),
                'brand_consistency_score' => rand(70, 95) / 100,
            ]);

            $count++;
        }

        $this->command->info('✓ ' . $count . ' media assets created');
    }

    /**
     * Seed brand knowledge dimensions
     */
    private function seedBrandKnowledgeDimensions(string $orgId, string $profileGroupId, array $posts): void
    {
        $this->command->info('Seeding brand knowledge dimensions...');

        $count = 0;
        $dimensionTypes = [
            ['category' => 'strategy', 'type' => 'marketing_objectives', 'values' => ['Brand Awareness', 'Lead Generation', 'Engagement', 'Conversion']],
            ['category' => 'messaging', 'type' => 'emotional_triggers', 'values' => ['FOMO', 'Curiosity', 'Inspiration', 'Trust']],
            ['category' => 'messaging', 'type' => 'hooks', 'values' => ['Question', 'Stat', 'Bold Statement', 'Story']],
            ['category' => 'messaging', 'type' => 'tones', 'values' => ['Professional', 'Casual', 'Humorous', 'Inspirational']],
            ['category' => 'creative', 'type' => 'cta', 'values' => ['Learn More', 'Shop Now', 'Sign Up', 'Download']],
            ['category' => 'visual', 'type' => 'color_palette', 'values' => ['Warm', 'Cool', 'Monochrome', 'Vibrant']],
        ];

        foreach ($dimensionTypes as $dimType) {
            foreach ($dimType['values'] as $value) {
                $isCoreDNA = rand(1, 100) <= 40; // 40% are core DNA

                BrandKnowledgeDimension::create([
                    'org_id' => $orgId,
                    'profile_group_id' => $profileGroupId,
                    'dimension_category' => $dimType['category'],
                    'dimension_type' => $dimType['type'],
                    'dimension_value' => $value,
                    'confidence_score' => rand(60, 100) / 100,
                    'is_core_dna' => $isCoreDNA,
                    'frequency_count' => rand(5, 50),
                    'first_seen_at' => now()->subDays(rand(30, 90)),
                    'last_seen_at' => now()->subDays(rand(1, 10)),
                    'avg_success_score' => rand(60, 95) / 100,
                    'success_post_count' => rand(3, 20),
                    'total_post_count' => rand(10, 50),
                    'co_occurring_dimensions' => $this->generateCoOccurringDimensions(),
                    'performance_context' => $this->generatePerformanceContext(),
                    'platform' => ['instagram', 'facebook', 'tiktok'][array_rand(['instagram', 'facebook', 'tiktok'])],
                    'status' => 'active',
                ]);

                $count++;
            }
        }

        $this->command->info('✓ ' . $count . ' brand knowledge dimensions created');
    }

    // ===== Helper Methods for Generating Sample Data =====

    private function generateCaption(string $platform, int $index): string
    {
        $captions = [
            "Transform your business with our latest innovation! 🚀 #Innovation #Business",
            "Behind the scenes of our creative process ✨ What do you think?",
            "5 tips to boost your productivity today 💪 Save this for later!",
            "New product alert! 🎉 Limited time offer - shop now!",
            "Customer success story: How we helped @brand achieve 10x growth",
            "Weekend vibes 🌅 What are your plans?",
            "Expert insights on industry trends you need to know",
            "Join us for an exclusive webinar this Thursday! Link in bio",
        ];

        return $captions[$index % count($captions)];
    }

    private function generateVisualCaption(): string
    {
        $captions = [
            "Modern workspace with laptop, coffee, and plants",
            "Product showcase on clean white background",
            "Team collaboration in bright office space",
            "Close-up of hands using smartphone with app interface",
            "Flat lay composition of colorful products",
        ];

        return $captions[array_rand($captions)];
    }

    private function generateSceneDescription(): string
    {
        return "Professional photography with good lighting, clean composition, and brand colors";
    }

    private function generateDetectedObjects(): array
    {
        return [
            ['object' => 'laptop', 'confidence' => 0.95],
            ['object' => 'person', 'confidence' => 0.88],
            ['object' => 'product', 'confidence' => 0.92],
        ];
    }

    private function generateColorPalette(): array
    {
        return [
            'dominant' => ['#FF6B6B', '#4ECDC4', '#45B7D1'],
            'accent' => ['#FFA07A', '#98D8C8'],
            'scheme' => 'vibrant',
        ];
    }

    private function generateTypography(): array
    {
        return [
            'fonts' => ['Sans-serif', 'Modern'],
            'sizes' => ['large_headline', 'medium_body'],
            'style' => 'bold_clean',
        ];
    }

    private function generateDesignPrompt(): string
    {
        return "Create a modern, minimalist design with vibrant colors. Use clean typography and focus on product. Include subtle shadows for depth.";
    }

    private function generateArtDirection(): string
    {
        $styles = ['minimalist', 'vibrant', 'professional', 'playful', 'elegant'];
        return $styles[array_rand($styles)];
    }

    private function generateMood(): string
    {
        $moods = ['energetic', 'calm', 'inspiring', 'professional', 'friendly'];
        return $moods[array_rand($moods)];
    }

    private function generateCoOccurringDimensions(): array
    {
        return [
            ['dimension_type' => 'tones', 'dimension_value' => 'Professional', 'co_occurrence_rate' => 0.75],
            ['dimension_type' => 'hooks', 'dimension_value' => 'Question', 'co_occurrence_rate' => 0.60],
        ];
    }

    private function generatePerformanceContext(): array
    {
        return [
            'best_time' => 'morning',
            'best_day' => 'tuesday',
            'avg_engagement' => 0.045,
        ];
    }
}
