<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Main orchestrator seeder for the Marketing Website.
 * Seeds all website-related tables in the cmis_website schema.
 */
class WebsiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding Marketing Website data...');

        // Clear existing data (in reverse dependency order to handle FK constraints)
        $this->command->info('Clearing existing website data...');
        $this->clearTables();

        // Seed in dependency order
        $this->call([
            WebsiteSettingsSeeder::class,
            FeatureCategoriesSeeder::class,
            FaqCategoriesSeeder::class,
            BlogCategoriesSeeder::class,
            NavigationMenusSeeder::class,
            PagesSeeder::class,
            HeroSlidesSeeder::class,
            FeaturesSeeder::class,
            FaqItemsSeeder::class,
            TeamMembersSeeder::class,
            PartnersSeeder::class,
            TestimonialsSeeder::class,
            CaseStudiesSeeder::class,
            BlogPostsSeeder::class,
            NavigationItemsSeeder::class,
        ]);

        $this->command->info('Marketing Website data seeded successfully!');
    }

    /**
     * Clear all website tables in reverse dependency order.
     */
    private function clearTables(): void
    {
        // Tables with foreign key dependencies first
        DB::table('cmis_website.navigation_items')->truncate();
        DB::table('cmis_website.page_sections')->truncate();
        DB::table('cmis_website.seo_metadata')->truncate();
        DB::table('cmis_website.blog_posts')->truncate();
        DB::table('cmis_website.features')->truncate();
        DB::table('cmis_website.faq_items')->truncate();

        // Then parent tables
        DB::table('cmis_website.navigation_menus')->truncate();
        DB::table('cmis_website.case_studies')->truncate();
        DB::table('cmis_website.testimonials')->truncate();
        DB::table('cmis_website.partners')->truncate();
        DB::table('cmis_website.team_members')->truncate();
        DB::table('cmis_website.hero_slides')->truncate();
        DB::table('cmis_website.pages')->truncate();
        DB::table('cmis_website.blog_categories')->truncate();
        DB::table('cmis_website.faq_categories')->truncate();
        DB::table('cmis_website.feature_categories')->truncate();
        DB::table('cmis_website.website_settings')->truncate();
    }
}
