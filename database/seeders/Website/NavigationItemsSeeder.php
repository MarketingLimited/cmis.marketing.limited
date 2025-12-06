<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NavigationItemsSeeder extends Seeder
{
    public function run(): void
    {
        // Get menu IDs
        $menus = DB::table('cmis_website.navigation_menus')
            ->pluck('id', 'name')
            ->toArray();

        // Header navigation
        $headerItems = [
            [
                'label_en' => 'Features',
                'label_ar' => 'الميزات',
                'url' => '/features',
                'sort_order' => 1,
            ],
            [
                'label_en' => 'Pricing',
                'label_ar' => 'الأسعار',
                'url' => '/pricing',
                'sort_order' => 2,
            ],
            [
                'label_en' => 'About',
                'label_ar' => 'عن المنصة',
                'url' => '/about',
                'sort_order' => 3,
            ],
            [
                'label_en' => 'Blog',
                'label_ar' => 'المدونة',
                'url' => '/blog',
                'sort_order' => 4,
            ],
            [
                'label_en' => 'Contact',
                'label_ar' => 'اتصل بنا',
                'url' => '/contact',
                'sort_order' => 5,
            ],
        ];

        // Footer Company navigation
        $footerCompanyItems = [
            [
                'label_en' => 'About Us',
                'label_ar' => 'عنا',
                'url' => '/about',
                'sort_order' => 1,
            ],
            [
                'label_en' => 'Careers',
                'label_ar' => 'الوظائف',
                'url' => '/careers',
                'sort_order' => 2,
            ],
            [
                'label_en' => 'Blog',
                'label_ar' => 'المدونة',
                'url' => '/blog',
                'sort_order' => 3,
            ],
            [
                'label_en' => 'Contact',
                'label_ar' => 'اتصل بنا',
                'url' => '/contact',
                'sort_order' => 4,
            ],
        ];

        // Footer Resources navigation
        $footerResourcesItems = [
            [
                'label_en' => 'Documentation',
                'label_ar' => 'التوثيق',
                'url' => '/docs',
                'sort_order' => 1,
            ],
            [
                'label_en' => 'Help Center',
                'label_ar' => 'مركز المساعدة',
                'url' => '/help',
                'sort_order' => 2,
            ],
            [
                'label_en' => 'Case Studies',
                'label_ar' => 'دراسات الحالة',
                'url' => '/case-studies',
                'sort_order' => 3,
            ],
            [
                'label_en' => 'FAQ',
                'label_ar' => 'الأسئلة الشائعة',
                'url' => '/faq',
                'sort_order' => 4,
            ],
        ];

        // Footer Legal navigation
        $footerLegalItems = [
            [
                'label_en' => 'Terms of Service',
                'label_ar' => 'شروط الخدمة',
                'url' => '/terms',
                'sort_order' => 1,
            ],
            [
                'label_en' => 'Privacy Policy',
                'label_ar' => 'سياسة الخصوصية',
                'url' => '/privacy',
                'sort_order' => 2,
            ],
            [
                'label_en' => 'Cookie Policy',
                'label_ar' => 'سياسة ملفات تعريف الارتباط',
                'url' => '/cookies',
                'sort_order' => 3,
            ],
        ];

        // Mobile navigation
        $mobileItems = [
            [
                'label_en' => 'Home',
                'label_ar' => 'الرئيسية',
                'url' => '/',
                'sort_order' => 1,
            ],
            [
                'label_en' => 'Features',
                'label_ar' => 'الميزات',
                'url' => '/features',
                'sort_order' => 2,
            ],
            [
                'label_en' => 'Pricing',
                'label_ar' => 'الأسعار',
                'url' => '/pricing',
                'sort_order' => 3,
            ],
            [
                'label_en' => 'About',
                'label_ar' => 'عن المنصة',
                'url' => '/about',
                'sort_order' => 4,
            ],
            [
                'label_en' => 'Blog',
                'label_ar' => 'المدونة',
                'url' => '/blog',
                'sort_order' => 5,
            ],
            [
                'label_en' => 'Contact',
                'label_ar' => 'اتصل بنا',
                'url' => '/contact',
                'sort_order' => 6,
            ],
            [
                'label_en' => 'Request Demo',
                'label_ar' => 'طلب عرض',
                'url' => '/demo',
                'sort_order' => 7,
            ],
        ];

        // Insert navigation items
        $this->insertItems($menus['header'] ?? null, $headerItems);
        $this->insertItems($menus['footer_company'] ?? null, $footerCompanyItems);
        $this->insertItems($menus['footer_resources'] ?? null, $footerResourcesItems);
        $this->insertItems($menus['footer_legal'] ?? null, $footerLegalItems);
        $this->insertItems($menus['mobile'] ?? null, $mobileItems);
    }

    private function insertItems(?string $menuId, array $items): void
    {
        if (!$menuId) return;

        foreach ($items as $item) {
            DB::table('cmis_website.navigation_items')->insert([
                'id' => Str::uuid(),
                'menu_id' => $menuId,
                'parent_id' => null,
                'label_en' => $item['label_en'],
                'label_ar' => $item['label_ar'],
                'url' => $item['url'],
                'target' => '_self',
                'sort_order' => $item['sort_order'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
