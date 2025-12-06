<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CMIS Marketing Website Schema
 *
 * Creates the cmis_website schema with 17 tables for the public marketing website.
 * This is global content (NO multi-tenancy/RLS) - not organization-scoped.
 *
 * Tables:
 * 1. website_settings - Global site configuration
 * 2. feature_categories - Feature grouping
 * 3. faq_categories - FAQ grouping
 * 4. blog_categories - Blog categorization
 * 5. navigation_menus - Menu containers
 * 6. pages - CMS pages
 * 7. features - Platform features
 * 8. faq_items - FAQ entries
 * 9. team_members - Team directory
 * 10. partners - Partner logos
 * 11. testimonials - Customer testimonials
 * 12. case_studies - Success stories
 * 13. hero_slides - Homepage carousel
 * 14. blog_posts - Blog articles
 * 15. navigation_items - Menu items
 * 16. page_sections - Modular sections
 * 17. seo_metadata - Polymorphic SEO
 */
return new class extends Migration
{
    public function up(): void
    {
        // For fresh migrations: drop existing schema completely first
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_website CASCADE');

        // Create the cmis_website schema
        DB::unprepared('CREATE SCHEMA cmis_website');

        // 1. Website Settings (key-value store for site configuration)
        Schema::create('cmis_website.website_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('key')->unique();
            $table->string('group')->default('general'); // general, seo, social, analytics
            $table->text('value_en')->nullable();
            $table->text('value_ar')->nullable();
            $table->string('type')->default('text'); // text, boolean, json, image
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group', 'is_active']);
        });

        // 2. Feature Categories
        Schema::create('cmis_website.feature_categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('icon')->nullable(); // Font Awesome class
            $table->string('color')->nullable(); // Hex color code
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        // 3. FAQ Categories
        Schema::create('cmis_website.faq_categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        // 4. Blog Categories
        Schema::create('cmis_website.blog_categories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug')->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('color')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index(['is_featured']);
        });

        // 5. Navigation Menus
        Schema::create('cmis_website.navigation_menus', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name')->unique(); // header, footer, mobile, footer_legal
            $table->string('location'); // header, footer, mobile
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['location', 'is_active']);
        });

        // 6. CMS Pages
        Schema::create('cmis_website.pages', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('excerpt_en')->nullable();
            $table->text('excerpt_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->longText('content_ar')->nullable();
            $table->string('template')->default('default'); // default, full-width, sidebar
            $table->string('featured_image_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_in_navigation')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'sort_order']);
            $table->index(['template']);
        });

        // 7. Features
        Schema::create('cmis_website.features', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('category_id')->nullable();
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->longText('details_en')->nullable();
            $table->longText('details_ar')->nullable();
            $table->string('icon')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_new')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                  ->references('id')
                  ->on('cmis_website.feature_categories')
                  ->onDelete('set null');

            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['is_featured']);
        });

        // 8. FAQ Items
        Schema::create('cmis_website.faq_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('category_id')->nullable();
            $table->text('question_en');
            $table->text('question_ar');
            $table->longText('answer_en');
            $table->longText('answer_ar');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('views')->default(0);
            $table->integer('helpful_votes')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                  ->references('id')
                  ->on('cmis_website.faq_categories')
                  ->onDelete('set null');

            $table->index(['category_id', 'is_active', 'sort_order']);
            $table->index(['is_featured']);
        });

        // 9. Team Members
        Schema::create('cmis_website.team_members', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name_en');
            $table->string('name_ar');
            $table->string('role_en');
            $table->string('role_ar');
            $table->text('bio_en')->nullable();
            $table->text('bio_ar')->nullable();
            $table->string('image_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->jsonb('social_links')->nullable(); // {linkedin, twitter, etc.}
            $table->string('department')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_contact_info')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index(['department']);
            $table->index(['is_featured']);
        });

        // 10. Partners
        Schema::create('cmis_website.partners', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('website_url')->nullable();
            $table->string('type')->default('partner'); // partner, client, integration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'is_active', 'sort_order']);
            $table->index(['is_featured']);
        });

        // 11. Testimonials
        Schema::create('cmis_website.testimonials', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('author_name_en');
            $table->string('author_name_ar')->nullable();
            $table->string('author_role_en')->nullable();
            $table->string('author_role_ar')->nullable();
            $table->string('company_name_en')->nullable();
            $table->string('company_name_ar')->nullable();
            $table->text('quote_en');
            $table->text('quote_ar')->nullable();
            $table->string('author_image_url')->nullable();
            $table->string('company_logo_url')->nullable();
            $table->integer('rating')->default(5); // 1-5 stars
            $table->string('industry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_video')->default(false);
            $table->string('video_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
            $table->index(['is_featured']);
            $table->index(['industry']);
        });

        // 12. Case Studies
        Schema::create('cmis_website.case_studies', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('excerpt_en')->nullable();
            $table->text('excerpt_ar')->nullable();
            $table->string('client_name_en');
            $table->string('client_name_ar')->nullable();
            $table->string('client_logo_url')->nullable();
            $table->string('industry_en');
            $table->string('industry_ar')->nullable();
            $table->longText('challenge_en');
            $table->longText('challenge_ar')->nullable();
            $table->longText('solution_en');
            $table->longText('solution_ar')->nullable();
            $table->longText('results_en');
            $table->longText('results_ar')->nullable();
            $table->jsonb('metrics')->nullable(); // {roi: "300%", time_saved: "50%", etc.}
            $table->string('featured_image_url')->nullable();
            $table->jsonb('gallery_images')->nullable(); // Array of image URLs
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'sort_order']);
            $table->index(['is_featured']);
        });

        // 13. Hero Slides
        Schema::create('cmis_website.hero_slides', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('headline_en');
            $table->string('headline_ar');
            $table->text('subheadline_en')->nullable();
            $table->text('subheadline_ar')->nullable();
            $table->string('cta_text_en')->nullable();
            $table->string('cta_text_ar')->nullable();
            $table->string('cta_url')->nullable();
            $table->string('secondary_cta_text_en')->nullable();
            $table->string('secondary_cta_text_ar')->nullable();
            $table->string('secondary_cta_url')->nullable();
            $table->string('background_image_url')->nullable();
            $table->string('background_video_url')->nullable();
            $table->string('overlay_color')->default('rgba(0,0,0,0.5)');
            $table->string('text_color')->default('#ffffff');
            $table->string('text_alignment')->default('center'); // left, center, right
            $table->jsonb('stats')->nullable(); // [{label_en, label_ar, value}, ...]
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        // 14. Blog Posts
        Schema::create('cmis_website.blog_posts', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('category_id')->nullable();
            $table->uuid('author_id')->nullable(); // Reference to users table
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_ar');
            $table->text('excerpt_en')->nullable();
            $table->text('excerpt_ar')->nullable();
            $table->longText('content_en');
            $table->longText('content_ar')->nullable();
            $table->string('featured_image_url')->nullable();
            $table->jsonb('tags')->nullable(); // Array of tags
            $table->integer('reading_time_minutes')->default(5);
            $table->integer('views')->default(0);
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_comments')->default(true);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')
                  ->references('id')
                  ->on('cmis_website.blog_categories')
                  ->onDelete('set null');

            $table->index(['category_id', 'is_published']);
            $table->index(['is_published', 'published_at']);
            $table->index(['is_featured']);
            $table->index(['author_id']);
        });

        // 15. Navigation Items
        Schema::create('cmis_website.navigation_items', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('menu_id');
            $table->uuid('parent_id')->nullable();
            $table->string('label_en');
            $table->string('label_ar');
            $table->string('url')->nullable();
            $table->string('route_name')->nullable(); // Laravel route name
            $table->string('icon')->nullable();
            $table->string('target')->default('_self'); // _self, _blank
            $table->string('type')->default('link'); // link, dropdown, mega
            $table->jsonb('attributes')->nullable(); // Additional attributes
            $table->boolean('is_active')->default(true);
            $table->boolean('is_highlighted')->default(false);
            $table->string('highlight_color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('menu_id')
                  ->references('id')
                  ->on('cmis_website.navigation_menus')
                  ->onDelete('cascade');

            $table->index(['menu_id', 'parent_id', 'is_active', 'sort_order']);
        });

        // Add self-referencing foreign key after table creation
        Schema::table('cmis_website.navigation_items', function (Blueprint $table) {
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('cmis_website.navigation_items')
                  ->onDelete('cascade');
        });

        // 16. Page Sections
        Schema::create('cmis_website.page_sections', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('page_id');
            $table->string('type'); // hero, text, features, cta, testimonials, faq, contact
            $table->string('title_en')->nullable();
            $table->string('title_ar')->nullable();
            $table->text('subtitle_en')->nullable();
            $table->text('subtitle_ar')->nullable();
            $table->longText('content_en')->nullable();
            $table->longText('content_ar')->nullable();
            $table->jsonb('settings')->nullable(); // Section-specific settings
            $table->string('background_color')->nullable();
            $table->string('background_image_url')->nullable();
            $table->string('text_color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('page_id')
                  ->references('id')
                  ->on('cmis_website.pages')
                  ->onDelete('cascade');

            $table->index(['page_id', 'is_active', 'sort_order']);
        });

        // 17. SEO Metadata (Polymorphic)
        Schema::create('cmis_website.seo_metadata', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('seoable_id');
            $table->string('seoable_type'); // Page, BlogPost, CaseStudy
            $table->string('meta_title_en')->nullable();
            $table->string('meta_title_ar')->nullable();
            $table->text('meta_description_en')->nullable();
            $table->text('meta_description_ar')->nullable();
            $table->string('meta_keywords_en')->nullable();
            $table->string('meta_keywords_ar')->nullable();
            $table->string('og_title_en')->nullable();
            $table->string('og_title_ar')->nullable();
            $table->text('og_description_en')->nullable();
            $table->text('og_description_ar')->nullable();
            $table->string('og_image_url')->nullable();
            $table->string('twitter_card')->default('summary_large_image');
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index, follow');
            $table->jsonb('structured_data')->nullable(); // Schema.org JSON-LD
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seoable_type', 'seoable_id']);
            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        // Drop tables in reverse order (respecting foreign keys)
        Schema::dropIfExists('cmis_website.seo_metadata');
        Schema::dropIfExists('cmis_website.page_sections');
        Schema::dropIfExists('cmis_website.navigation_items');
        Schema::dropIfExists('cmis_website.blog_posts');
        Schema::dropIfExists('cmis_website.hero_slides');
        Schema::dropIfExists('cmis_website.case_studies');
        Schema::dropIfExists('cmis_website.testimonials');
        Schema::dropIfExists('cmis_website.partners');
        Schema::dropIfExists('cmis_website.team_members');
        Schema::dropIfExists('cmis_website.faq_items');
        Schema::dropIfExists('cmis_website.features');
        Schema::dropIfExists('cmis_website.pages');
        Schema::dropIfExists('cmis_website.navigation_menus');
        Schema::dropIfExists('cmis_website.blog_categories');
        Schema::dropIfExists('cmis_website.faq_categories');
        Schema::dropIfExists('cmis_website.feature_categories');
        Schema::dropIfExists('cmis_website.website_settings');

        // Drop the schema
        DB::unprepared('DROP SCHEMA IF EXISTS cmis_website CASCADE');
    }
};
