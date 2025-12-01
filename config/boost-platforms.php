<?php

/**
 * Platform-specific boost configuration for ad campaigns.
 *
 * Each platform has unique objectives, placements, targeting options,
 * and budget requirements based on their respective APIs.
 *
 * @see https://developers.facebook.com/docs/marketing-api/
 * @see https://ads.tiktok.com/marketing_api/docs
 * @see https://developers.google.com/google-ads/api
 * @see https://marketingapi.snapchat.com/docs
 * @see https://developer.twitter.com/en/docs/twitter-ads-api
 * @see https://learn.microsoft.com/en-us/linkedin/marketing/
 * @see https://developers.pinterest.com/docs/ads/
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Meta (Facebook/Instagram) Ads Platform
    |--------------------------------------------------------------------------
    |
    | Marketing API v18.0 - ODAX objectives (Outcome-Driven Ad Experiences)
    | Budget is in cents (multiply by 100)
    |
    */
    'meta' => [
        'name' => 'Meta (Facebook/Instagram)',
        'objectives' => [
            ['id' => 'OUTCOME_AWARENESS', 'name' => 'Brand Awareness', 'name_ar' => 'الوعي بالعلامة التجارية', 'description' => 'Show ads to people most likely to remember them'],
            ['id' => 'OUTCOME_ENGAGEMENT', 'name' => 'Engagement', 'name_ar' => 'التفاعل', 'description' => 'Get more likes, comments, shares, and event responses'],
            ['id' => 'OUTCOME_TRAFFIC', 'name' => 'Traffic', 'name_ar' => 'الزيارات', 'description' => 'Send people to your website or app'],
            ['id' => 'OUTCOME_LEADS', 'name' => 'Lead Generation', 'name_ar' => 'جذب العملاء', 'description' => 'Collect leads for your business'],
            ['id' => 'OUTCOME_SALES', 'name' => 'Sales', 'name_ar' => 'المبيعات', 'description' => 'Find people likely to purchase your product'],
            ['id' => 'OUTCOME_APP_PROMOTION', 'name' => 'App Promotion', 'name_ar' => 'ترويج التطبيق', 'description' => 'Get more app installs and activity'],
        ],
        'placements' => [
            ['id' => 'facebook_feed', 'name' => 'Facebook Feed', 'name_ar' => 'آخر أخبار فيسبوك'],
            ['id' => 'facebook_stories', 'name' => 'Facebook Stories', 'name_ar' => 'قصص فيسبوك'],
            ['id' => 'facebook_reels', 'name' => 'Facebook Reels', 'name_ar' => 'ريلز فيسبوك'],
            ['id' => 'facebook_right_column', 'name' => 'Facebook Right Column', 'name_ar' => 'العمود الأيمن'],
            ['id' => 'instagram_feed', 'name' => 'Instagram Feed', 'name_ar' => 'آخر أخبار إنستغرام'],
            ['id' => 'instagram_stories', 'name' => 'Instagram Stories', 'name_ar' => 'قصص إنستغرام'],
            ['id' => 'instagram_reels', 'name' => 'Instagram Reels', 'name_ar' => 'ريلز إنستغرام'],
            ['id' => 'instagram_explore', 'name' => 'Instagram Explore', 'name_ar' => 'استكشاف إنستغرام'],
            ['id' => 'messenger_inbox', 'name' => 'Messenger Inbox', 'name_ar' => 'صندوق الرسائل'],
            ['id' => 'messenger_stories', 'name' => 'Messenger Stories', 'name_ar' => 'قصص المسنجر'],
            ['id' => 'audience_network', 'name' => 'Audience Network', 'name_ar' => 'شبكة الجمهور'],
        ],
        'special_features' => [
            'advantage_plus' => true,
            'advantage_plus_audience' => true,
            'dynamic_creative' => true,
            'custom_audiences' => true,
            'lookalike_audiences' => true,
            'page_post_boost' => true,
        ],
        'optimization_goals' => [
            ['id' => 'REACH', 'name' => 'Reach', 'name_ar' => 'الوصول'],
            ['id' => 'IMPRESSIONS', 'name' => 'Impressions', 'name_ar' => 'مرات الظهور'],
            ['id' => 'LINK_CLICKS', 'name' => 'Link Clicks', 'name_ar' => 'النقرات على الرابط'],
            ['id' => 'LANDING_PAGE_VIEWS', 'name' => 'Landing Page Views', 'name_ar' => 'مشاهدات الصفحة'],
            ['id' => 'POST_ENGAGEMENT', 'name' => 'Post Engagement', 'name_ar' => 'تفاعل المنشور'],
            ['id' => 'THRUPLAY', 'name' => 'ThruPlay', 'name_ar' => 'المشاهدة الكاملة'],
            ['id' => 'CONVERSIONS', 'name' => 'Conversions', 'name_ar' => 'التحويلات'],
            ['id' => 'VALUE', 'name' => 'Value', 'name_ar' => 'القيمة'],
        ],
        'budget_multiplier' => 100, // Convert to cents
        'min_budget' => 1,
        'min_audience_size' => 1000,
        'currency_symbol' => '$',
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Ads (YouTube) Platform
    |--------------------------------------------------------------------------
    |
    | Google Ads API - Video campaigns for YouTube
    | Budget in standard currency units
    |
    */
    'google' => [
        'name' => 'Google Ads (YouTube)',
        'objectives' => [
            ['id' => 'VIDEO_VIEWS', 'name' => 'Video Views', 'name_ar' => 'مشاهدات الفيديو', 'description' => 'Get more views on your video content'],
            ['id' => 'REACH', 'name' => 'Reach', 'name_ar' => 'الوصول', 'description' => 'Show ads to a large number of people'],
            ['id' => 'CONVERSIONS', 'name' => 'Conversions', 'name_ar' => 'التحويلات', 'description' => 'Drive actions on your website'],
            ['id' => 'BRAND_AWARENESS', 'name' => 'Brand Awareness', 'name_ar' => 'الوعي بالعلامة', 'description' => 'Increase brand recognition'],
            ['id' => 'WEBSITE_TRAFFIC', 'name' => 'Website Traffic', 'name_ar' => 'زيارات الموقع', 'description' => 'Drive traffic to your website'],
            ['id' => 'PRODUCT_CONSIDERATION', 'name' => 'Product Consideration', 'name_ar' => 'الاهتمام بالمنتج', 'description' => 'Encourage people to explore your products'],
        ],
        'placements' => [
            ['id' => 'youtube_instream', 'name' => 'YouTube In-Stream', 'name_ar' => 'داخل الفيديو'],
            ['id' => 'youtube_infeed', 'name' => 'YouTube In-Feed', 'name_ar' => 'في الخلاصة'],
            ['id' => 'youtube_shorts', 'name' => 'YouTube Shorts', 'name_ar' => 'يوتيوب شورتس'],
            ['id' => 'youtube_bumper', 'name' => 'Bumper Ads (6s)', 'name_ar' => 'إعلانات قصيرة (6 ثوان)'],
            ['id' => 'youtube_masthead', 'name' => 'YouTube Masthead', 'name_ar' => 'رأس الصفحة'],
            ['id' => 'discovery', 'name' => 'Google Discovery', 'name_ar' => 'اكتشف جوجل'],
            ['id' => 'display_network', 'name' => 'Display Network', 'name_ar' => 'شبكة العرض'],
        ],
        'ad_formats' => [
            ['id' => 'skippable', 'name' => 'Skippable In-Stream', 'name_ar' => 'قابل للتخطي', 'description' => 'Skip after 5 seconds'],
            ['id' => 'non_skippable', 'name' => 'Non-Skippable (15s max)', 'name_ar' => 'غير قابل للتخطي', 'description' => 'Up to 15 seconds, no skip'],
            ['id' => 'bumper', 'name' => 'Bumper (6s)', 'name_ar' => 'قصير (6 ثوان)', 'description' => '6-second non-skippable'],
            ['id' => 'infeed', 'name' => 'In-Feed/Discovery', 'name_ar' => 'في الخلاصة', 'description' => 'Appears in search and discovery'],
            ['id' => 'outstream', 'name' => 'Outstream', 'name_ar' => 'خارج البث', 'description' => 'Mobile-only, partner sites'],
        ],
        'bidding_strategies' => [
            ['id' => 'MAXIMIZE_CONVERSIONS', 'name' => 'Maximize Conversions', 'name_ar' => 'أكبر عدد من التحويلات'],
            ['id' => 'TARGET_CPA', 'name' => 'Target CPA', 'name_ar' => 'تكلفة الإجراء المستهدفة'],
            ['id' => 'TARGET_CPV', 'name' => 'Target CPV', 'name_ar' => 'تكلفة المشاهدة المستهدفة'],
            ['id' => 'TARGET_ROAS', 'name' => 'Target ROAS', 'name_ar' => 'عائد الإنفاق المستهدف'],
            ['id' => 'MAXIMIZE_CLICKS', 'name' => 'Maximize Clicks', 'name_ar' => 'أكبر عدد من النقرات'],
            ['id' => 'VIEWABLE_CPM', 'name' => 'Viewable CPM', 'name_ar' => 'التكلفة لكل ألف ظهور مرئي'],
        ],
        'special_features' => [
            'smart_bidding' => true,
            'audience_expansion' => true,
            'optimized_targeting' => true,
        ],
        'budget_multiplier' => 1,
        'min_budget' => 10,
        'currency_symbol' => '$',
    ],

    /*
    |--------------------------------------------------------------------------
    | TikTok Ads Platform
    |--------------------------------------------------------------------------
    |
    | TikTok Marketing API - In-Feed Ads and Spark Ads
    | Budget in standard currency units
    |
    */
    'tiktok' => [
        'name' => 'TikTok',
        'objectives' => [
            ['id' => 'REACH', 'name' => 'Reach', 'name_ar' => 'الوصول', 'description' => 'Show ads to maximum number of people'],
            ['id' => 'TRAFFIC', 'name' => 'Traffic', 'name_ar' => 'الزيارات', 'description' => 'Drive visits to your destination'],
            ['id' => 'VIDEO_VIEWS', 'name' => 'Video Views', 'name_ar' => 'مشاهدات الفيديو', 'description' => 'Get more video views'],
            ['id' => 'ENGAGEMENT', 'name' => 'Community Interaction', 'name_ar' => 'التفاعل المجتمعي', 'description' => 'Get followers, profile visits, and interactions'],
            ['id' => 'LEAD_GENERATION', 'name' => 'Lead Generation', 'name_ar' => 'جذب العملاء', 'description' => 'Collect leads in-app'],
            ['id' => 'CONVERSIONS', 'name' => 'Website Conversions', 'name_ar' => 'تحويلات الموقع', 'description' => 'Drive actions on your website'],
            ['id' => 'APP_PROMOTION', 'name' => 'App Promotion', 'name_ar' => 'ترويج التطبيق', 'description' => 'Get app installs and events'],
            ['id' => 'PRODUCT_SALES', 'name' => 'Product Sales', 'name_ar' => 'مبيعات المنتج', 'description' => 'Sell products from your catalog'],
        ],
        'placements' => [
            ['id' => 'tiktok_feed', 'name' => 'TikTok For You Page', 'name_ar' => 'صفحة لك'],
            ['id' => 'tiktok_stories', 'name' => 'TikTok Stories', 'name_ar' => 'قصص تيك توك'],
            ['id' => 'pangle', 'name' => 'Pangle Network', 'name_ar' => 'شبكة بانجل'],
            ['id' => 'global_app_bundle', 'name' => 'Global App Bundle', 'name_ar' => 'حزمة التطبيقات العالمية'],
        ],
        'special_features' => [
            'spark_ads' => true, // Boost organic TikTok posts
            'smart_plus' => true,
            'interactive_addons' => true,
            'branded_effects' => true,
        ],
        'bid_types' => [
            ['id' => 'BID_TYPE_NO_BID', 'name' => 'Automatic Bidding', 'name_ar' => 'مزايدة تلقائية'],
            ['id' => 'BID_TYPE_CUSTOM', 'name' => 'Manual Bidding', 'name_ar' => 'مزايدة يدوية'],
        ],
        'optimization_goals' => [
            ['id' => 'CLICK', 'name' => 'Clicks', 'name_ar' => 'النقرات'],
            ['id' => 'VIDEO_VIEW', 'name' => '2s/6s Video Views', 'name_ar' => 'مشاهدات 2-6 ثوان'],
            ['id' => 'REACH', 'name' => 'Reach', 'name_ar' => 'الوصول'],
            ['id' => 'CONVERSION', 'name' => 'Conversions', 'name_ar' => 'التحويلات'],
            ['id' => 'INSTALL', 'name' => 'App Installs', 'name_ar' => 'تثبيت التطبيق'],
            ['id' => 'LEAD', 'name' => 'Leads', 'name_ar' => 'العملاء المحتملين'],
        ],
        'budget_multiplier' => 1,
        'min_budget' => 20,
        'min_budget_per_day' => 50, // Daily minimum
        'currency_symbol' => '$',
    ],

    /*
    |--------------------------------------------------------------------------
    | Snapchat Ads Platform
    |--------------------------------------------------------------------------
    |
    | Snapchat Marketing API - Simplified objectives (2024 update)
    | Budget in micros (multiply by 1,000,000)
    |
    */
    'snapchat' => [
        'name' => 'Snapchat',
        'objectives' => [
            ['id' => 'AWARENESS_ENGAGEMENT', 'name' => 'Awareness & Engagement', 'name_ar' => 'الوعي والتفاعل', 'description' => 'Reach and engage with your audience'],
            ['id' => 'TRAFFIC', 'name' => 'Traffic', 'name_ar' => 'الزيارات', 'description' => 'Send people to your website or app'],
            ['id' => 'CONVERSIONS', 'name' => 'Conversions', 'name_ar' => 'التحويلات', 'description' => 'Drive actions on your website'],
            ['id' => 'APP_PROMOTION', 'name' => 'App Promotion', 'name_ar' => 'ترويج التطبيق', 'description' => 'Get app installs'],
            ['id' => 'CATALOG_SALES', 'name' => 'Catalog Sales', 'name_ar' => 'مبيعات الكتالوج', 'description' => 'Sell products from your catalog'],
        ],
        'ad_types' => [
            ['id' => 'SNAP_AD', 'name' => 'Snap Ad', 'name_ar' => 'إعلان سناب', 'description' => 'Full-screen vertical video'],
            ['id' => 'STORY_AD', 'name' => 'Story Ad', 'name_ar' => 'إعلان القصة', 'description' => 'Branded tile in Discover'],
            ['id' => 'COLLECTION_AD', 'name' => 'Collection Ad', 'name_ar' => 'إعلان المجموعة', 'description' => 'Showcase multiple products'],
            ['id' => 'AR_LENS', 'name' => 'AR Lens', 'name_ar' => 'عدسة الواقع المعزز', 'description' => 'Interactive AR experience'],
            ['id' => 'FILTER', 'name' => 'Filter', 'name_ar' => 'فلتر', 'description' => 'Branded filter overlay'],
            ['id' => 'COMMERCIAL', 'name' => 'Commercial', 'name_ar' => 'إعلان تجاري', 'description' => 'Non-skippable 6s ad'],
        ],
        'placements' => [
            ['id' => 'user_stories', 'name' => 'Between Stories', 'name_ar' => 'بين القصص'],
            ['id' => 'discover', 'name' => 'Discover Feed', 'name_ar' => 'خلاصة اكتشف'],
            ['id' => 'camera', 'name' => 'Camera (AR)', 'name_ar' => 'الكاميرا'],
            ['id' => 'spotlight', 'name' => 'Spotlight', 'name_ar' => 'سبوت لايت'],
        ],
        'special_features' => [
            'lifestyle_categories' => true, // 150+ lifestyle targeting
            'snap_pixel' => true,
            'ar_try_on' => true,
            'profile_linking' => true, // Required 2024
        ],
        'optimization_goals' => [
            ['id' => 'SWIPES', 'name' => 'Swipe-Ups', 'name_ar' => 'السحب للأعلى'],
            ['id' => 'IMPRESSIONS', 'name' => 'Impressions', 'name_ar' => 'مرات الظهور'],
            ['id' => 'VIDEO_VIEWS', 'name' => 'Video Views', 'name_ar' => 'مشاهدات الفيديو'],
            ['id' => 'USES', 'name' => 'Lens/Filter Uses', 'name_ar' => 'استخدامات العدسة/الفلتر'],
            ['id' => 'STORY_OPENS', 'name' => 'Story Opens', 'name_ar' => 'فتح القصص'],
        ],
        'budget_multiplier' => 1000000, // Convert to micros
        'min_budget' => 5,
        'currency_symbol' => '$',
    ],

    /*
    |--------------------------------------------------------------------------
    | X (Twitter) Ads Platform
    |--------------------------------------------------------------------------
    |
    | Twitter Ads API - Promoted Tweets and Accounts
    | Budget in micros (multiply by 1,000,000)
    |
    */
    'twitter' => [
        'name' => 'X (Twitter)',
        'objectives' => [
            ['id' => 'REACH', 'name' => 'Reach', 'name_ar' => 'الوصول', 'description' => 'Maximize ad impressions'],
            ['id' => 'TWEET_ENGAGEMENTS', 'name' => 'Engagements', 'name_ar' => 'التفاعلات', 'description' => 'Get likes, retweets, and replies'],
            ['id' => 'VIDEO_VIEWS', 'name' => 'Video Views', 'name_ar' => 'مشاهدات الفيديو', 'description' => 'Get more video views'],
            ['id' => 'FOLLOWERS', 'name' => 'Followers', 'name_ar' => 'المتابعين', 'description' => 'Grow your follower count'],
            ['id' => 'WEBSITE_CLICKS', 'name' => 'Website Traffic', 'name_ar' => 'زيارات الموقع', 'description' => 'Drive traffic to your website'],
            ['id' => 'WEBSITE_CONVERSIONS', 'name' => 'Website Conversions', 'name_ar' => 'تحويلات الموقع', 'description' => 'Drive actions on your website'],
            ['id' => 'APP_INSTALLS', 'name' => 'App Installs', 'name_ar' => 'تثبيت التطبيق', 'description' => 'Get app downloads'],
            ['id' => 'APP_ENGAGEMENTS', 'name' => 'App Re-engagements', 'name_ar' => 'إعادة تفاعل التطبيق', 'description' => 'Re-engage existing app users'],
            ['id' => 'PRE_ROLL_VIEWS', 'name' => 'Pre-Roll Views', 'name_ar' => 'مشاهدات ما قبل التشغيل', 'description' => 'In-stream video ads'],
        ],
        'placements' => [
            ['id' => 'ALL_ON_TWITTER', 'name' => 'All on X', 'name_ar' => 'الكل على X'],
            ['id' => 'TIMELINE', 'name' => 'Timeline', 'name_ar' => 'الخط الزمني'],
            ['id' => 'SEARCH', 'name' => 'Search Results', 'name_ar' => 'نتائج البحث'],
            ['id' => 'PROFILES', 'name' => 'Profiles', 'name_ar' => 'الملفات الشخصية'],
            ['id' => 'PUBLISHER_NETWORK', 'name' => 'Publisher Network', 'name_ar' => 'شبكة الناشرين'],
        ],
        'targeting_types' => [
            'follower_lookalikes' => true, // Similar to followers of @handles
            'keywords' => true,
            'conversation_topics' => true,
            'events' => true,
            'tweet_engager_retargeting' => true,
            'tailored_audiences' => true, // Custom audiences
        ],
        'ad_formats' => [
            ['id' => 'promoted_tweet', 'name' => 'Promoted Post', 'name_ar' => 'منشور مروج'],
            ['id' => 'promoted_account', 'name' => 'Promoted Account', 'name_ar' => 'حساب مروج'],
            ['id' => 'promoted_trend', 'name' => 'Promoted Trend', 'name_ar' => 'ترند مروج'],
            ['id' => 'video_ad', 'name' => 'Video Ad', 'name_ar' => 'إعلان فيديو'],
            ['id' => 'carousel', 'name' => 'Carousel', 'name_ar' => 'كاروسيل'],
        ],
        'special_features' => [
            'promoted_only' => true, // Dark posts
            'conversation_targeting' => true,
            'event_targeting' => true,
            'twitter_pixel' => true,
        ],
        'budget_multiplier' => 1000000, // Convert to micros
        'min_budget' => 10,
        'currency_symbol' => '$',
    ],

    /*
    |--------------------------------------------------------------------------
    | LinkedIn Ads Platform
    |--------------------------------------------------------------------------
    |
    | LinkedIn Marketing API - B2B focused advertising
    | Budget in standard currency units
    |
    */
    'linkedin' => [
        'name' => 'LinkedIn',
        'objectives' => [
            ['id' => 'BRAND_AWARENESS', 'name' => 'Brand Awareness', 'name_ar' => 'الوعي بالعلامة', 'description' => 'Increase brand recognition'],
            ['id' => 'WEBSITE_VISITS', 'name' => 'Website Visits', 'name_ar' => 'زيارات الموقع', 'description' => 'Drive traffic to your website'],
            ['id' => 'ENGAGEMENT', 'name' => 'Engagement', 'name_ar' => 'التفاعل', 'description' => 'Get reactions, comments, and shares'],
            ['id' => 'VIDEO_VIEWS', 'name' => 'Video Views', 'name_ar' => 'مشاهدات الفيديو', 'description' => 'Get more video views'],
            ['id' => 'LEAD_GENERATION', 'name' => 'Lead Generation', 'name_ar' => 'جذب العملاء', 'description' => 'Collect leads with Lead Gen Forms'],
            ['id' => 'WEBSITE_CONVERSIONS', 'name' => 'Website Conversions', 'name_ar' => 'تحويلات الموقع', 'description' => 'Drive actions on your website'],
            ['id' => 'JOB_APPLICANTS', 'name' => 'Job Applicants', 'name_ar' => 'المتقدمين للوظائف', 'description' => 'Attract qualified job candidates'],
        ],
        'placements' => [
            ['id' => 'linkedin_feed', 'name' => 'LinkedIn Feed', 'name_ar' => 'آخر أخبار لينكدإن'],
            ['id' => 'linkedin_right_rail', 'name' => 'Right Rail', 'name_ar' => 'العمود الأيمن'],
            ['id' => 'linkedin_messaging', 'name' => 'Messaging (InMail)', 'name_ar' => 'الرسائل'],
            ['id' => 'linkedin_audience_network', 'name' => 'Audience Network', 'name_ar' => 'شبكة الجمهور'],
        ],
        'ad_formats' => [
            ['id' => 'single_image', 'name' => 'Single Image', 'name_ar' => 'صورة واحدة'],
            ['id' => 'carousel', 'name' => 'Carousel', 'name_ar' => 'كاروسيل'],
            ['id' => 'video', 'name' => 'Video', 'name_ar' => 'فيديو'],
            ['id' => 'text_ad', 'name' => 'Text Ad', 'name_ar' => 'إعلان نصي'],
            ['id' => 'spotlight', 'name' => 'Spotlight', 'name_ar' => 'سبوت لايت'],
            ['id' => 'message_ad', 'name' => 'Message Ad', 'name_ar' => 'إعلان رسالة'],
            ['id' => 'conversation_ad', 'name' => 'Conversation Ad', 'name_ar' => 'إعلان محادثة'],
            ['id' => 'event_ad', 'name' => 'Event Ad', 'name_ar' => 'إعلان فعالية'],
            ['id' => 'document_ad', 'name' => 'Document Ad', 'name_ar' => 'إعلان مستند'],
        ],
        'b2b_targeting' => [
            'job_title' => true,
            'job_function' => true,
            'job_seniority' => true,
            'company_name' => true,
            'company_size' => true,
            'company_industry' => true,
            'skills' => true,
            'groups' => true,
            'member_interests' => true,
            'years_of_experience' => true,
            'degrees' => true,
            'fields_of_study' => true,
        ],
        'company_sizes' => [
            ['id' => '1-10', 'name' => '1-10 employees', 'name_ar' => '1-10 موظفين'],
            ['id' => '11-50', 'name' => '11-50 employees', 'name_ar' => '11-50 موظف'],
            ['id' => '51-200', 'name' => '51-200 employees', 'name_ar' => '51-200 موظف'],
            ['id' => '201-500', 'name' => '201-500 employees', 'name_ar' => '201-500 موظف'],
            ['id' => '501-1000', 'name' => '501-1,000 employees', 'name_ar' => '501-1000 موظف'],
            ['id' => '1001-5000', 'name' => '1,001-5,000 employees', 'name_ar' => '1001-5000 موظف'],
            ['id' => '5001-10000', 'name' => '5,001-10,000 employees', 'name_ar' => '5001-10000 موظف'],
            ['id' => '10001+', 'name' => '10,001+ employees', 'name_ar' => 'أكثر من 10000 موظف'],
        ],
        'seniority_levels' => [
            ['id' => 'entry', 'name' => 'Entry', 'name_ar' => 'مبتدئ'],
            ['id' => 'senior', 'name' => 'Senior', 'name_ar' => 'خبير'],
            ['id' => 'manager', 'name' => 'Manager', 'name_ar' => 'مدير'],
            ['id' => 'director', 'name' => 'Director', 'name_ar' => 'مدير إداري'],
            ['id' => 'vp', 'name' => 'VP', 'name_ar' => 'نائب رئيس'],
            ['id' => 'cxo', 'name' => 'CXO', 'name_ar' => 'رئيس تنفيذي'],
            ['id' => 'partner', 'name' => 'Partner', 'name_ar' => 'شريك'],
            ['id' => 'owner', 'name' => 'Owner', 'name_ar' => 'مالك'],
        ],
        'special_features' => [
            'lead_gen_forms' => true,
            'matched_audiences' => true, // LinkedIn's custom audiences
            'lookalike_audiences' => true,
            'insight_tag' => true, // LinkedIn pixel
        ],
        'budget_multiplier' => 1,
        'min_budget' => 10,
        'min_audience_size' => 300,
        'currency_symbol' => '$',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pinterest Ads Platform
    |--------------------------------------------------------------------------
    |
    | Pinterest Ads API v5 - Visual discovery and shopping
    | Budget in standard currency units
    |
    */
    'pinterest' => [
        'name' => 'Pinterest',
        'objectives' => [
            ['id' => 'AWARENESS', 'name' => 'Brand Awareness', 'name_ar' => 'الوعي بالعلامة', 'description' => 'Reach people exploring Pinterest'],
            ['id' => 'VIDEO_VIEW', 'name' => 'Video Views', 'name_ar' => 'مشاهدات الفيديو', 'description' => 'Get more video views'],
            ['id' => 'CONSIDERATION', 'name' => 'Consideration (Clicks)', 'name_ar' => 'الاهتمام', 'description' => 'Drive traffic and engagement'],
            ['id' => 'CONVERSIONS', 'name' => 'Conversions', 'name_ar' => 'التحويلات', 'description' => 'Drive actions on your website'],
            ['id' => 'CATALOG_SALES', 'name' => 'Catalog Sales', 'name_ar' => 'مبيعات الكتالوج', 'description' => 'Sell products from your catalog'],
        ],
        'placements' => [
            ['id' => 'ALL', 'name' => 'All Placements', 'name_ar' => 'كل المواضع'],
            ['id' => 'BROWSE', 'name' => 'Home Feed', 'name_ar' => 'الصفحة الرئيسية'],
            ['id' => 'SEARCH', 'name' => 'Search Results', 'name_ar' => 'نتائج البحث'],
            ['id' => 'RELATED_PINS', 'name' => 'Related Pins', 'name_ar' => 'دبابيس مشابهة'],
        ],
        'ad_formats' => [
            ['id' => 'standard_pin', 'name' => 'Standard Pin', 'name_ar' => 'دبوس قياسي', 'description' => 'Single image or video'],
            ['id' => 'video_pin', 'name' => 'Video Pin', 'name_ar' => 'دبوس فيديو', 'description' => 'Full-width video ad'],
            ['id' => 'carousel', 'name' => 'Carousel', 'name_ar' => 'كاروسيل', 'description' => '2-5 swipeable images'],
            ['id' => 'shopping', 'name' => 'Shopping Ad', 'name_ar' => 'إعلان تسوق', 'description' => 'Product catalog ads'],
            ['id' => 'collections', 'name' => 'Collections', 'name_ar' => 'مجموعات', 'description' => 'Featured image with products'],
            ['id' => 'idea_pin', 'name' => 'Idea Pin', 'name_ar' => 'دبوس فكرة', 'description' => 'Multi-page story format'],
        ],
        'special_features' => [
            'actalike_audiences' => true, // Pinterest's lookalike
            'interest_targeting' => true,
            'keyword_targeting' => true,
            'pinterest_tag' => true, // Conversion tracking
            'shopping_api' => true,
            'catalogs' => true,
        ],
        'optimization_goals' => [
            ['id' => 'IMPRESSION', 'name' => 'Impressions', 'name_ar' => 'مرات الظهور'],
            ['id' => 'OUTBOUND_CLICK', 'name' => 'Outbound Clicks', 'name_ar' => 'النقرات الخارجية'],
            ['id' => 'PIN_CLICK', 'name' => 'Pin Clicks', 'name_ar' => 'نقرات الدبوس'],
            ['id' => 'SAVE', 'name' => 'Saves', 'name_ar' => 'الحفظ'],
            ['id' => 'VIDEO_V50', 'name' => 'Video Views (50%)', 'name_ar' => 'مشاهدات 50%'],
            ['id' => 'CONVERSION', 'name' => 'Conversions', 'name_ar' => 'التحويلات'],
        ],
        'budget_multiplier' => 1,
        'min_budget' => 5,
        'currency_symbol' => '$',
    ],
];
