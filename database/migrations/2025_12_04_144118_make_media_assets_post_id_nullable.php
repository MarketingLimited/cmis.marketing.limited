<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Make post_id nullable to allow uploading media before a post is created.
     * Media will be linked to a post when the post is published.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE cmis.media_assets ALTER COLUMN post_id DROP NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, delete any orphaned media assets without a post_id
        DB::statement('DELETE FROM cmis.media_assets WHERE post_id IS NULL');

        // Then add back the NOT NULL constraint
        DB::statement('ALTER TABLE cmis.media_assets ALTER COLUMN post_id SET NOT NULL');
    }
};
