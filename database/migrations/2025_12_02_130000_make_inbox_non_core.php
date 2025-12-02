<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Make Inbox a non-core app that users can enable/disable.
     */
    public function up(): void
    {
        DB::table('cmis.marketplace_apps')
            ->where('slug', 'inbox')
            ->update([
                'is_core' => false,
                'category' => 'social',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('cmis.marketplace_apps')
            ->where('slug', 'inbox')
            ->update([
                'is_core' => true,
                'category' => 'core',
                'updated_at' => now(),
            ]);
    }
};
