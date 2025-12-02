<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make platform-connections and profiles (Profile Management) core apps.
     * Core apps are always enabled and visible for all organizations.
     */
    public function up(): void
    {
        // 1. Make platform-connections a core app
        DB::table('cmis.marketplace_apps')
            ->where('slug', 'platform-connections')
            ->update(['is_core' => true, 'updated_at' => now()]);

        // 2. Add profiles (Profile Management) as a core app if it doesn't exist
        $exists = DB::table('cmis.marketplace_apps')
            ->where('slug', 'profiles')
            ->exists();

        if (!$exists) {
            DB::table('cmis.marketplace_apps')->insert([
                'app_id' => \Illuminate\Support\Str::uuid()->toString(),
                'slug' => 'profiles',
                'name_key' => 'marketplace.apps.profiles.name',
                'description_key' => 'marketplace.apps.profiles.description',
                'category' => 'system',
                'icon' => 'fa-id-card',
                'route_prefix' => 'settings/profiles',
                'is_core' => true,
                'sort_order' => 115,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            // If exists, just make sure it's a core app
            DB::table('cmis.marketplace_apps')
                ->where('slug', 'profiles')
                ->update(['is_core' => true, 'updated_at' => now()]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert platform-connections to non-core
        DB::table('cmis.marketplace_apps')
            ->where('slug', 'platform-connections')
            ->update(['is_core' => false, 'updated_at' => now()]);

        // Revert profiles to non-core (don't delete, just revert)
        DB::table('cmis.marketplace_apps')
            ->where('slug', 'profiles')
            ->update(['is_core' => false, 'updated_at' => now()]);
    }
};
