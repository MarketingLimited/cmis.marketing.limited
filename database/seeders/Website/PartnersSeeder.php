<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnersSeeder extends Seeder
{
    public function run(): void
    {
        $partners = [
            [
                'name_en' => 'Meta',
                'name_ar' => 'Meta',
                'logo_url' => '/images/partners/meta.svg',
                'website_url' => 'https://meta.com',
                'is_featured' => true,
                'sort_order' => 1,
            ],
            [
                'name_en' => 'Google',
                'name_ar' => 'Google',
                'logo_url' => '/images/partners/google.svg',
                'website_url' => 'https://google.com',
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name_en' => 'TikTok',
                'name_ar' => 'TikTok',
                'logo_url' => '/images/partners/tiktok.svg',
                'website_url' => 'https://tiktok.com',
                'is_featured' => true,
                'sort_order' => 3,
            ],
            [
                'name_en' => 'LinkedIn',
                'name_ar' => 'LinkedIn',
                'logo_url' => '/images/partners/linkedin.svg',
                'website_url' => 'https://linkedin.com',
                'is_featured' => true,
                'sort_order' => 4,
            ],
            [
                'name_en' => 'Twitter/X',
                'name_ar' => 'Twitter/X',
                'logo_url' => '/images/partners/twitter.svg',
                'website_url' => 'https://twitter.com',
                'is_featured' => true,
                'sort_order' => 5,
            ],
            [
                'name_en' => 'Snapchat',
                'name_ar' => 'Snapchat',
                'logo_url' => '/images/partners/snapchat.svg',
                'website_url' => 'https://snapchat.com',
                'is_featured' => true,
                'sort_order' => 6,
            ],
            [
                'name_en' => 'HubSpot',
                'name_ar' => 'HubSpot',
                'logo_url' => '/images/partners/hubspot.svg',
                'website_url' => 'https://hubspot.com',
                'is_featured' => false,
                'sort_order' => 7,
            ],
            [
                'name_en' => 'Salesforce',
                'name_ar' => 'Salesforce',
                'logo_url' => '/images/partners/salesforce.svg',
                'website_url' => 'https://salesforce.com',
                'is_featured' => false,
                'sort_order' => 8,
            ],
            [
                'name_en' => 'Shopify',
                'name_ar' => 'Shopify',
                'logo_url' => '/images/partners/shopify.svg',
                'website_url' => 'https://shopify.com',
                'is_featured' => false,
                'sort_order' => 9,
            ],
            [
                'name_en' => 'Stripe',
                'name_ar' => 'Stripe',
                'logo_url' => '/images/partners/stripe.svg',
                'website_url' => 'https://stripe.com',
                'is_featured' => false,
                'sort_order' => 10,
            ],
        ];

        foreach ($partners as $partner) {
            DB::table('cmis_website.partners')->insert([
                'id' => Str::uuid(),
                'name_en' => $partner['name_en'],
                'name_ar' => $partner['name_ar'],
                'logo_url' => $partner['logo_url'],
                'website_url' => $partner['website_url'],
                'is_featured' => $partner['is_featured'],
                'sort_order' => $partner['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
