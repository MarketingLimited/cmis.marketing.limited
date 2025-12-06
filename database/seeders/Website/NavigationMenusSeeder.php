<?php

namespace Database\Seeders\Website;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NavigationMenusSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            [
                'name' => 'header',
                'location' => 'header',
            ],
            [
                'name' => 'footer_company',
                'location' => 'footer',
            ],
            [
                'name' => 'footer_resources',
                'location' => 'footer',
            ],
            [
                'name' => 'footer_legal',
                'location' => 'footer',
            ],
            [
                'name' => 'mobile',
                'location' => 'mobile',
            ],
        ];

        foreach ($menus as $menu) {
            DB::table('cmis_website.navigation_menus')->insert([
                'id' => Str::uuid(),
                'name' => $menu['name'],
                'location' => $menu['location'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
