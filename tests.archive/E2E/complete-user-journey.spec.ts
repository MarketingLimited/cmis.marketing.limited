import { test, expect } from '@playwright/test';

/**
 * Complete User Journey - من التسجيل إلى إنشاء حملة كاملة
 *
 * هذا الاختبار يغطي السيناريو الكامل:
 * 1. تسجيل مستخدم جديد
 * 2. تسجيل الدخول
 * 3. إنشاء منظمة/شركة
 * 4. إنشاء حملة
 * 5. إنشاء خطة محتوى
 * 6. إنشاء قطع المحتوى
 * 7. توليد النصوص التوضيحية
 * 8. إضافة المنتجات والخدمات
 * 9. إضافة جميع تفاصيل Creative Brief
 */
test.describe('Complete User Journey - الرحلة الكاملة', () => {
  test('should complete full user journey from registration to campaign creation', async ({ page }) => {
    const timestamp = Date.now();
    const userEmail = `user${timestamp}@example.com`;
    const orgName = `Test Organization ${timestamp}`;
    const campaignName = `Test Campaign ${timestamp}`;

    // ==================== المرحلة 1: التسجيل ====================
    console.log('Step 1: User Registration');

    await page.goto('/register');

    await page.fill('input[name="name"]', 'Test User');
    await page.fill('input[name="email"]', userEmail);
    await page.fill('input[name="password"]', 'Password123!');
    await page.fill('input[name="password_confirmation"]', 'Password123!');

    await page.click('button[type="submit"]');

    // Verify redirected to dashboard or verification page
    await page.waitForURL(/.*\/(dashboard|verify-email)/);

    // ==================== المرحلة 2: تسجيل الدخول ====================
    console.log('Step 2: User Login');

    // If on verification page, skip to login
    if (page.url().includes('verify-email')) {
      await page.goto('/login');
    }

    await page.fill('input[name="email"]', userEmail);
    await page.fill('input[name="password"]', 'Password123!');
    await page.click('button[type="submit"]');

    await page.waitForURL(/.*\/dashboard/);
    await expect(page.locator('[data-testid="user-menu"]')).toBeVisible();

    // ==================== المرحلة 3: إنشاء منظمة/شركة ====================
    console.log('Step 3: Create Organization');

    await page.click('[data-testid="create-organization"]');

    await page.fill('input[name="name"]', orgName);
    await page.selectOption('[name="currency"]', 'BHD');
    await page.selectOption('[name="timezone"]', 'Asia/Bahrain');
    await page.selectOption('[name="default_locale"]', 'ar-BH');

    // إضافة معلومات الشركة
    await page.fill('input[name="industry"]', 'E-commerce');
    await page.fill('textarea[name="description"]', 'شركة تجارة إلكترونية متخصصة في المنتجات الصيفية');

    await page.click('button[type="submit"]');

    await expect(page.locator(`text=${orgName}`)).toBeVisible();

    // ==================== المرحلة 4: إنشاء حملة ====================
    console.log('Step 4: Create Campaign');

    await page.click('[data-testid="nav-campaigns"]');
    await page.click('[data-testid="create-campaign-button"]');

    await page.fill('input[name="name"]', campaignName);
    await page.selectOption('[name="objective"]', 'conversions');
    await page.fill('input[name="budget"]', '5000');
    await page.selectOption('[name="currency"]', 'BHD');
    await page.fill('input[name="start_date"]', '2024-06-01');
    await page.fill('input[name="end_date"]', '2024-06-30');
    await page.fill('textarea[name="description"]', 'حملة تسويقية لمنتجات الصيف');

    await page.click('button[type="submit"]');

    await expect(page.locator(`text=${campaignName}`)).toBeVisible();

    // ==================== المرحلة 5: إنشاء خطة محتوى ====================
    console.log('Step 5: Create Content Plan');

    await page.click('[data-testid="nav-creative"]');
    await page.click('[data-testid="content-plans-tab"]');
    await page.click('[data-testid="create-content-plan"]');

    await page.fill('input[name="plan_name"]', `Content Plan for ${campaignName}`);
    await page.selectOption('[name="campaign_id"]', { label: campaignName });
    await page.fill('input[name="start_date"]', '2024-06-01');
    await page.fill('input[name="end_date"]', '2024-06-30');

    // إضافة أيام النشر
    await page.check('[name="publish_days"][value="monday"]');
    await page.check('[name="publish_days"][value="wednesday"]');
    await page.check('[name="publish_days"][value="friday"]');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Content plan created')).toBeVisible();

    // ==================== المرحلة 6: إنشاء قطع المحتوى ====================
    console.log('Step 6: Create Content Items');

    const contentItems = [
      {
        title: 'Summer Collection Launch',
        channel: 'facebook',
        format: 'image_post',
        scheduledDate: '2024-06-03',
      },
      {
        title: 'Customer Testimonial',
        channel: 'instagram',
        format: 'carousel',
        scheduledDate: '2024-06-05',
      },
      {
        title: 'Product Showcase Video',
        channel: 'instagram',
        format: 'video',
        scheduledDate: '2024-06-07',
      },
    ];

    for (const item of contentItems) {
      await page.click('[data-testid="add-content-item"]');

      await page.fill('input[name="title"]', item.title);
      await page.selectOption('[name="channel"]', item.channel);
      await page.selectOption('[name="format"]', item.format);
      await page.fill('input[name="scheduled_at"]', `${item.scheduledDate}T10:00`);

      await page.click('button[data-testid="save-content-item"]');

      await expect(page.locator(`text=${item.title}`)).toBeVisible();
    }

    // ==================== المرحلة 7: توليد النصوص التوضيحية بالـ AI ====================
    console.log('Step 7: Generate Content Text with AI');

    // اختيار أول قطعة محتوى
    await page.click('[data-testid="content-item"]:first-child');
    await page.click('[data-testid="generate-copy-button"]');

    await page.fill('textarea[name="brief"]', 'أنشئ نص إعلاني جذاب لإطلاق مجموعة الصيف الجديدة');
    await page.click('button[data-testid="generate-ai-copy"]');

    // انتظار توليد النص
    await expect(page.locator('[data-testid="generated-copy"]')).toBeVisible({ timeout: 10000 });

    // قبول النص المولد
    await page.click('[data-testid="accept-generated-copy"]');

    await expect(page.locator('text=Copy saved successfully')).toBeVisible();

    // ==================== المرحلة 8: إضافة المنتجات والخدمات ====================
    console.log('Step 8: Add Products and Services');

    await page.click('[data-testid="nav-offerings"]');

    // إضافة منتج
    await page.click('[data-testid="add-product"]');

    await page.fill('input[name="product_name"]', 'قميص صيفي قطني');
    await page.fill('textarea[name="description"]', 'قميص صيفي مريح مصنوع من القطن 100%');
    await page.fill('input[name="price"]', '25.00');
    await page.selectOption('[name="currency"]', 'BHD');
    await page.fill('input[name="sku"]', 'SHIRT-001');

    // Features
    await page.click('[data-testid="add-feature"]');
    await page.fill('input[name="features[0]"]', 'قطن 100% عالي الجودة');
    await page.click('[data-testid="add-feature"]');
    await page.fill('input[name="features[1]"]', 'تصميم عصري ومريح');
    await page.click('[data-testid="add-feature"]');
    await page.fill('input[name="features[2]"]', 'متوفر بألوان متعددة');

    // Benefits
    await page.click('[data-testid="add-benefit"]');
    await page.fill('input[name="benefits[0]"]', 'يبقيك باردًا طوال اليوم');
    await page.click('[data-testid="add-benefit"]');
    await page.fill('input[name="benefits[1]"]', 'سهل العناية والغسيل');

    // Transformational Benefits
    await page.click('[data-testid="add-transformational-benefit"]');
    await page.fill('input[name="transformational_benefits[0]"]', 'ثقة أكبر في المظهر');
    await page.click('[data-testid="add-transformational-benefit"]');
    await page.fill('input[name="transformational_benefits[1]"]', 'راحة استثنائية في الطقس الحار');

    // USPs
    await page.click('[data-testid="add-usp"]');
    await page.fill('input[name="usps[0]"]', 'أفضل سعر في السوق');
    await page.click('[data-testid="add-usp"]');
    await page.fill('input[name="usps[1]"]', 'توصيل مجاني لطلبات فوق 50 دينار');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Product added successfully')).toBeVisible();

    // إضافة خدمة
    await page.click('[data-testid="add-service"]');

    await page.fill('input[name="service_name"]', 'استشارة أزياء شخصية');
    await page.fill('textarea[name="description"]', 'استشارة مخصصة مع خبير أزياء لاختيار الملابس المناسبة');
    await page.fill('input[name="price"]', '50.00');

    await page.click('button[type="submit"]');

    await expect(page.locator('text=Service added successfully')).toBeVisible();

    // ==================== المرحلة 9: إضافة Creative Brief الكامل ====================
    console.log('Step 9: Create Complete Creative Brief');

    await page.click('[data-testid="nav-creative"]');
    await page.click('[data-testid="creative-briefs-tab"]');
    await page.click('[data-testid="create-brief"]');

    // Basic Info
    await page.fill('input[name="brief_name"]', `Creative Brief - ${campaignName}`);
    await page.selectOption('[name="campaign_id"]', { label: campaignName });

    // Marketing Objective
    await page.selectOption('[name="marketing_objective"]', 'drive_sales');

    // Emotional Trigger
    await page.selectOption('[name="emotional_trigger"]', 'desire');

    // Hooks
    await page.fill('textarea[name="hooks"]', 'خصومات تصل إلى 50% على كل شيء!\nمجموعة صيفية محدودة - لا تفوت الفرصة!');

    // Channels
    await page.check('[name="channels"][value="facebook"]');
    await page.check('[name="channels"][value="instagram"]');
    await page.check('[name="channels"][value="twitter"]');

    // Segments
    await page.fill('[data-testid="segment-input"]', 'الشباب 18-35');
    await page.click('[data-testid="add-segment"]');
    await page.fill('[data-testid="segment-input"]', 'محبي الموضة');
    await page.click('[data-testid="add-segment"]');

    // Pains
    await page.fill('textarea[name="pains"]', 'صعوبة إيجاد ملابس صيفية بجودة عالية وأسعار معقولة\nمحدودية الخيارات في المتاجر المحلية');

    // Marketing Framework
    await page.selectOption('[name="marketing_framework"]', 'aida');

    // Marketing Strategy
    await page.selectOption('[name="marketing_strategy"]', 'content_marketing');

    // Awareness Stage
    await page.selectOption('[name="awareness_stage"]', 'solution_aware');

    // Funnel Stage
    await page.selectOption('[name="funnel_stage"]', 'consideration');

    // Tone
    await page.selectOption('[name="tone"]', 'friendly');

    // Message Map
    await page.fill('textarea[name="message_map"]', 'الرسالة الرئيسية: جودة عالية بأسعار معقولة\nالرسالة الثانوية: تشكيلة واسعة ومتنوعة\nالدعوة للعمل: تسوق الآن واستفد من العروض');

    // Proofs
    await page.fill('textarea[name="proofs"]', '- تقييمات العملاء 4.8/5\n- أكثر من 10,000 عميل راضٍ\n- ضمان استرجاع لمدة 30 يوم');

    // Brand
    await page.fill('input[name="brand_name"]', 'Summer Style');
    await page.fill('textarea[name="brand_values"]', 'الجودة، الأصالة، الابتكار');

    // Guardrails
    await page.fill('textarea[name="guardrails"]', '- عدم استخدام صور مبالغ فيها\n- تجنب الوعود غير الواقعية\n- الالتزام بالقيم الثقافية المحلية');

    // Seasonality
    await page.fill('input[name="seasonality"]', 'صيف 2024');

    // Style
    await page.fill('textarea[name="style"]', 'عصري، نظيف، ملون، جذاب');

    // Offer
    await page.fill('textarea[name="offer"]', 'خصم 50% على المجموعة الصيفية + توصيل مجاني');

    // Pricing
    await page.fill('input[name="pricing"]', '15-75 BHD');

    // CTA
    await page.fill('input[name="cta"]', 'تسوق الآن');

    // Content Formats
    await page.check('[name="content_formats"][value="image"]');
    await page.check('[name="content_formats"][value="video"]');
    await page.check('[name="content_formats"][value="carousel"]');

    // === Art Direction ===

    // Mood
    await page.fill('input[name="mood"]', 'مفعم بالحيوية، صيفي، منعش');

    // Visual Message
    await page.fill('textarea[name="visual_message"]', 'صور مشرقة تعكس أجواء الصيف والحرية والمرح');

    // Look & Feel
    await page.fill('textarea[name="look_feel"]', 'نظيف، مشرق، عصري، بسيط');

    // Color Palette
    await page.fill('input[name="primary_color"]', '#FF6B35');
    await page.fill('input[name="secondary_color"]', '#F7F7F7');
    await page.fill('input[name="accent_color"]', '#004E89');

    // Typography
    await page.fill('input[name="primary_font"]', 'Montserrat');
    await page.fill('input[name="secondary_font"]', 'Open Sans');

    // Imagery & Graphics
    await page.fill('textarea[name="imagery_guidelines"]', 'صور حقيقية للمنتجات\nصور لأشخاص يرتدون الملابس في أجواء صيفية\nتجنب الصور المبالغ فيها');

    // Icons & Symbols
    await page.fill('textarea[name="icons_symbols"]', 'أيقونات بسيطة وواضحة\nرموز تعبر عن الصيف (شمس، بحر، إلخ)');

    // Composition & Layout
    await page.fill('textarea[name="composition"]', 'تصميم متوازن مع مساحات بيضاء كافية\nالتركيز على المنتج الرئيسي\nCTA واضح وبارز');

    // Amplify
    await page.fill('textarea[name="amplify"]', 'الجودة، الأسعار الممتازة، التشكيلة الواسعة');

    // Story/Solution
    await page.fill('textarea[name="story"]', 'نحن نقدم الحل المثالي للباحثين عن ملابس صيفية عصرية بجودة عالية وأسعار مناسبة');

    // Design Description
    await page.fill('textarea[name="design_description"]', 'تصميم نابض بالحياة يعكس روح الصيف مع التركيز على البساطة والوضوح');

    // Background
    await page.fill('input[name="background"]', 'خلفية بيضاء نظيفة أو ألوان صيفية فاتحة');

    // Lighting
    await page.fill('input[name="lighting"]', 'إضاءة طبيعية ساطعة');

    // Highlight
    await page.fill('input[name="highlight"]', 'المنتجات الرئيسية والعروض الخاصة');

    // De-emphasize
    await page.fill('input[name="de_emphasize"]', 'العناصر الثانوية والخلفية');

    // Element Positions
    await page.fill('textarea[name="element_positions"]', 'الشعار: أعلى اليسار\nالمنتج: المركز\nCTA: أسفل اليمين\nالسعر: بارز بجوار المنتج');

    // Ratio
    await page.selectOption('[name="ratio"]', '1:1'); // For Instagram

    // Motion (للفيديو)
    await page.fill('textarea[name="motion"]', 'حركات سلسة وبطيئة\nتكبير تدريجي على المنتج\nانتقالات ناعمة بين المشاهد');

    // حفظ Creative Brief
    await page.click('button[type="submit"]');

    await expect(page.locator('text=Creative brief created successfully')).toBeVisible();

    // ==================== التحقق النهائي ====================
    console.log('Step 10: Final Verification');

    // العودة إلى Dashboard للتحقق من كل شيء
    await page.goto('/dashboard');

    // التحقق من وجود الحملة
    await expect(page.locator(`text=${campaignName}`)).toBeVisible();

    // التحقق من عدد المنتجات والخدمات
    const offeringsCount = await page.locator('[data-testid="offerings-count"]').textContent();
    expect(parseInt(offeringsCount || '0')).toBeGreaterThanOrEqual(2);

    // التحقق من عدد قطع المحتوى
    const contentItemsCount = await page.locator('[data-testid="content-items-count"]').textContent();
    expect(parseInt(contentItemsCount || '0')).toBeGreaterThanOrEqual(3);

    console.log('✅ Complete user journey test passed successfully!');
  });
});
