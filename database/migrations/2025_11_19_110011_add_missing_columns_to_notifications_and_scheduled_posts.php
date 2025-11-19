<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add notifiable_type and notifiable_id columns to notifications for Laravel's polymorphic notifications
        $hasNotifiableType = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'notifications'
                AND column_name = 'notifiable_type'
            ) as exists
        ");

        if (!$hasNotifiableType->exists) {
            DB::statement("
                ALTER TABLE cmis.notifications
                ADD COLUMN notifiable_type VARCHAR(255) NULL,
                ADD COLUMN notifiable_id UUID NULL
            ");

            echo "✓ Added notifiable_type and notifiable_id to cmis.notifications\n";
        }

        // Add post_id column to scheduled_social_posts
        $hasPostId = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'scheduled_social_posts'
                AND column_name = 'post_id'
            ) as exists
        ");

        if (!$hasPostId->exists) {
            DB::statement("
                ALTER TABLE cmis.scheduled_social_posts
                ADD COLUMN post_id UUID NULL
            ");

            echo "✓ Added post_id to cmis.scheduled_social_posts\n";
        }

        // Add metadata column to users table
        $hasMetadata = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'metadata'
            ) as exists
        ");

        if (!$hasMetadata->exists) {
            DB::statement("
                ALTER TABLE cmis.users
                ADD COLUMN metadata JSONB DEFAULT '{}'::jsonb
            ");

            echo "✓ Added metadata to cmis.users\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasNotifiableType = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'notifications'
                AND column_name = 'notifiable_type'
            ) as exists
        ");

        if ($hasNotifiableType->exists) {
            DB::statement("
                ALTER TABLE cmis.notifications
                DROP COLUMN IF EXISTS notifiable_type,
                DROP COLUMN IF EXISTS notifiable_id
            ");
        }

        $hasPostId = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'scheduled_social_posts'
                AND column_name = 'post_id'
            ) as exists
        ");

        if ($hasPostId->exists) {
            DB::statement("ALTER TABLE cmis.scheduled_social_posts DROP COLUMN IF EXISTS post_id");
        }

        $hasMetadata = DB::selectOne("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = 'cmis'
                AND table_name = 'users'
                AND column_name = 'metadata'
            ) as exists
        ");

        if ($hasMetadata->exists) {
            DB::statement("ALTER TABLE cmis.users DROP COLUMN IF EXISTS metadata");
        }
    }
};
