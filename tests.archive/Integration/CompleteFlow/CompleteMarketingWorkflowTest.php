<?php

namespace Tests\Integration\CompleteFlow;

use Tests\TestCase;
use Tests\Traits\CreatesTestData;
use Tests\Traits\MocksExternalAPIs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

use PHPUnit\Framework\Attributes\Test;
/**
 * Complete Marketing Workflow Integration Test
 *
 * اختبار شامل للـ workflow الكامل من البداية للنهاية
 */
class CompleteMarketingWorkflowTest extends TestCase
{
    use RefreshDatabase, CreatesTestData, MocksExternalAPIs;

    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function it_executes_complete_marketing_workflow_with_all_details()
    {
        // ========== Setup: إنشاء المستخدم والمنظمة ==========
        $setup = $this->createUserWithOrg();
        $org = $setup['org'];
        $user = $setup['user'];

        $this->actingAsUserInOrg($user, $org);
        $this->mockAllAPIs();

        // ========== Step 1: إنشاء المنتجات والخدمات ==========

        // منتج 1
        $product1 = \App\Models\Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'قميص صيفي قطني',
            'description' => 'قميص صيفي مريح مصنوع من القطن 100%',
            'price' => 25.00,
            'currency' => 'BHD',
            'details' => [
                'features' => [
                    'قطن 100% عالي الجودة',
                    'تصميم عصري ومريح',
                    'متوفر بألوان متعددة',
                ],
                'benefits' => [
                    'يبقيك باردًا طوال اليوم',
                    'سهل العناية والغسيل',
                ],
                'transformational_benefits' => [
                    'ثقة أكبر في المظهر',
                    'راحة استثنائية في الطقس الحار',
                ],
                'usps' => [
                    'أفضل سعر في السوق',
                    'ضمان الجودة',
                ],
            ],
        ]);

        // منتج 2
        $product2 = \App\Models\Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'product',
            'name' => 'بنطال صيفي خفيف',
            'description' => 'بنطال صيفي خفيف الوزن مثالي للطقس الحار',
            'price' => 35.00,
            'currency' => 'BHD',
        ]);

        // خدمة
        $service = \App\Models\Offering::create([
            'offering_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'type' => 'service',
            'name' => 'استشارة أزياء شخصية',
            'description' => 'استشارة مخصصة مع خبير أزياء',
            'price' => 50.00,
            'currency' => 'BHD',
        ]);

        $this->assertDatabaseHas('cmis.offerings', [
            'org_id' => $org->org_id,
            'name' => 'قميص صيفي قطني',
        ]);

        // ========== Step 2: إنشاء الحملة ==========

        $campaign = $this->createTestCampaign($org->org_id, [
            'name' => 'حملة الصيف 2024',
            'objective' => 'conversions',
            'status' => 'draft',
            'budget' => 5000.00,
            'currency' => 'BHD',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(37)->format('Y-m-d'),
            'description' => 'حملة تسويقية لمنتجات الصيف',
        ]);

        // ربط المنتجات بالحملة
        \App\Models\Campaign\CampaignOffering::create([
            'campaign_id' => $campaign->campaign_id,
            'offering_id' => $product1->offering_id,
        ]);

        \App\Models\Campaign\CampaignOffering::create([
            'campaign_id' => $campaign->campaign_id,
            'offering_id' => $product2->offering_id,
        ]);

        // ========== Step 3: إنشاء Creative Brief الكامل ==========

        $brief = $this->createTestCreativeBrief($org->org_id, [
            'name' => 'Creative Brief - حملة الصيف 2024',
            'brief_data' => [
                // Marketing Objectives
                'marketing_objective' => 'drive_sales',
                'emotional_trigger' => 'desire',

                // Hooks
                'hooks' => [
                    'خصومات تصل إلى 50% على كل شيء!',
                    'مجموعة صيفية محدودة - لا تفوت الفرصة!',
                ],

                // Channels
                'channels' => ['facebook', 'instagram', 'twitter'],

                // Segments
                'segments' => [
                    'الشباب 18-35',
                    'محبي الموضة',
                    'العائلات',
                ],

                // Pains
                'pains' => [
                    'صعوبة إيجاد ملابس صيفية بجودة عالية',
                    'الأسعار المرتفعة في المتاجر التقليدية',
                ],

                // Frameworks & Strategies
                'marketing_framework' => 'aida',
                'marketing_strategies' => ['content_marketing', 'social_media'],
                'awareness_stage' => 'solution_aware',
                'funnel_stage' => 'consideration',

                // Tone & Style
                'tone' => 'friendly',
                'style' => 'عصري، نظيف، ملون، جذاب',

                // Message Map
                'message_map' => [
                    'primary' => 'جودة عالية بأسعار معقولة',
                    'secondary' => 'تشكيلة واسعة ومتنوعة',
                    'cta' => 'تسوق الآن واستفد من العروض',
                ],

                // Proofs
                'proofs' => [
                    'تقييمات العملاء 4.8/5',
                    'أكثر من 10,000 عميل راضٍ',
                    'ضمان استرجاع لمدة 30 يوم',
                ],

                // Brand
                'brand' => [
                    'name' => 'Summer Style',
                    'values' => ['الجودة', 'الأصالة', 'الابتكار'],
                ],

                // Guardrails
                'guardrails' => [
                    'عدم استخدام صور مبالغ فيها',
                    'تجنب الوعود غير الواقعية',
                    'الالتزام بالقيم الثقافية المحلية',
                ],

                // Seasonality & Offer
                'seasonality' => 'صيف 2024',
                'offer' => 'خصم 50% على المجموعة الصيفية + توصيل مجاني',
                'pricing' => '15-75 BHD',
                'cta' => 'تسوق الآن',

                // Content Formats
                'content_formats' => ['image', 'video', 'carousel'],

                // Art Direction
                'art_direction' => [
                    'mood' => 'مفعم بالحيوية، صيفي، منعش',
                    'visual_message' => 'صور مشرقة تعكس أجواء الصيف',
                    'look_feel' => 'نظيف، مشرق، عصري، بسيط',

                    // Color Palette
                    'color_palette' => [
                        'primary' => '#FF6B35',
                        'secondary' => '#F7F7F7',
                        'accent' => '#004E89',
                    ],

                    // Typography
                    'typography' => [
                        'primary_font' => 'Montserrat',
                        'secondary_font' => 'Open Sans',
                    ],

                    // Imagery & Graphics
                    'imagery' => 'صور حقيقية للمنتجات، صور لأشخاص يرتدون الملابس',
                    'icons_symbols' => 'أيقونات بسيطة، رموز صيفية',

                    // Composition & Layout
                    'composition' => 'تصميم متوازن مع مساحات بيضاء',

                    // Amplify
                    'amplify' => ['الجودة', 'الأسعار الممتازة', 'التشكيلة الواسعة'],

                    // Story/Solution
                    'story' => 'نحن نقدم الحل المثالي للباحثين عن ملابس صيفية عصرية',

                    // Design Elements
                    'design_description' => 'تصميم نابض بالحياة يعكس روح الصيف',
                    'background' => 'خلفية بيضاء نظيفة أو ألوان صيفية فاتحة',
                    'lighting' => 'إضاءة طبيعية ساطعة',
                    'highlight' => 'المنتجات الرئيسية والعروض الخاصة',
                    'de_emphasize' => 'العناصر الثانوية والخلفية',

                    // Element Positions
                    'element_positions' => [
                        'logo' => 'أعلى اليسار',
                        'product' => 'المركز',
                        'cta' => 'أسفل اليمين',
                        'price' => 'بجوار المنتج',
                    ],

                    // Ratio & Motion
                    'ratio' => '1:1',
                    'motion' => 'حركات سلسة وبطيئة، تكبير تدريجي',
                ],
            ],
        ]);

        $this->assertDatabaseHas('cmis.creative_briefs', [
            'brief_id' => $brief->brief_id,
            'org_id' => $org->org_id,
        ]);

        // ========== Step 4: إنشاء خطة المحتوى ==========

        $contentPlan = \App\Models\Creative\ContentPlan::create([
            'plan_id' => Str::uuid(),
            'org_id' => $org->org_id,
            'campaign_id' => $campaign->campaign_id,
            'brief_id' => $brief->brief_id,
            'name' => 'خطة محتوى - حملة الصيف',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'end_date' => now()->addDays(37)->format('Y-m-d'),
        ]);

        // ========== Step 5: إنشاء قطع المحتوى ==========

        $contentItems = [];
        $items = [
            ['title' => 'إطلاق المجموعة الصيفية', 'channel_id' => 1, 'format_id' => 1],
            ['title' => 'شهادة عميل', 'channel_id' => 2, 'format_id' => 2],
            ['title' => 'عرض المنتج بالفيديو', 'channel_id' => 2, 'format_id' => 3],
        ];

        foreach ($items as $index => $itemData) {
            $item = \App\Models\Creative\ContentItem::create([
                'item_id' => Str::uuid(),
                'plan_id' => $contentPlan->plan_id,
                'org_id' => $org->org_id,
                'channel_id' => $itemData['channel_id'],
                'format_id' => $itemData['format_id'],
                'title' => $itemData['title'],
                'scheduled_at' => now()->addDays(7 + ($index * 2))->setHour(10),
                'status' => 'draft',
                'brief' => [
                    'objective' => 'engagement',
                    'description' => "محتوى {$itemData['title']}",
                ],
            ]);
            $contentItems[] = $item;
        }

        $this->assertCount(3, $contentItems);

        // ========== Step 6: توليد الأصول الإبداعية ==========

        foreach ($contentItems as $contentItem) {
            $asset = $this->createTestCreativeAsset($org->org_id, $campaign->campaign_id, [
                'brief_id' => $brief->brief_id,
                'channel_id' => $contentItem->channel_id,
                'format_id' => $contentItem->format_id,
                'status' => 'draft',
                'final_copy' => [
                    'headline' => $contentItem->title,
                    'body' => 'محتوى مولد بالـ AI يشرح ' . $contentItem->title,
                    'cta' => 'تسوق الآن',
                ],
            ]);
        }

        // ========== Step 7: الموافقة والنشر ==========

        // تفعيل الحملة
        $campaign->update(['status' => 'active']);

        // جدولة المنشورات
        foreach ($contentItems as $contentItem) {
            $this->createTestScheduledPost($org->org_id, $user->user_id, [
                'campaign_id' => $campaign->campaign_id,
                'platforms' => ['facebook', 'instagram'],
                'content' => $contentItem->title,
                'scheduled_at' => $contentItem->scheduled_at,
                'status' => 'scheduled',
            ]);
        }

        // ========== التحقق النهائي ==========

        // التحقق من الحملة
        $this->assertDatabaseHas('cmis.campaigns', [
            'campaign_id' => $campaign->campaign_id,
            'status' => 'active',
        ]);

        // التحقق من المنتجات (2) والخدمة (1)
        $this->assertEquals(3, \App\Models\Offering::where('org_id', $org->org_id)->count());

        // التحقق من Creative Brief
        $this->assertNotNull($brief->brief_data);
        $this->assertArrayHasKey('art_direction', $brief->brief_data);
        $this->assertArrayHasKey('color_palette', $brief->brief_data['art_direction']);

        // التحقق من قطع المحتوى (3)
        $this->assertEquals(3, \App\Models\Creative\ContentItem::where('org_id', $org->org_id)->count());

        // التحقق من الأصول الإبداعية (3)
        $this->assertEquals(3, \App\Models\Creative\CreativeAsset::where('org_id', $org->org_id)->count());

        // التحقق من المنشورات المجدولة (3)
        $this->assertEquals(3, \App\Models\ScheduledSocialPost::where('org_id', $org->org_id)->count());

        $this->logTestResult('passed', [
            'workflow' => 'complete_marketing_workflow',
            'steps_completed' => 7,
            'products_created' => 2,
            'services_created' => 1,
            'content_items' => 3,
            'creative_assets' => 3,
            'scheduled_posts' => 3,
        ]);
    }
}
