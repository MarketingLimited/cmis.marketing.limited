<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Consolidate remaining table alterations
     *
     * MEDIUM PRIORITY: Consolidates 16 duplicate table operations
     * Impact: Cleaner migration history, easier maintenance
     *
     * Background: Multiple migrations modify the same tables,
     * leading to unclear history and difficult rollbacks.
     * This migration consolidates scattered ALTER statements.
     *
     * Note: Only includes ALTER operations - table creation
     * consolidated in respective canonical migrations.
     */
    public function up(): void
    {
        // user_orgs alterations (invitation system)
        if (!Schema::hasColumn('cmis.user_orgs', 'invitation_token')) {
            Schema::table('cmis.user_orgs', function (Blueprint $table) {
                $table->string('invitation_token')->nullable()->after('status');
                $table->timestamp('invitation_expires_at')->nullable()->after('invitation_token');
            });
            echo "✅ Added invitation columns to user_orgs\n";
        }

        // markets alterations (metadata support)
        if (!Schema::hasColumn('public.markets', 'metadata')) {
            Schema::table('public.markets', function (Blueprint $table) {
                $table->jsonb('metadata')->nullable()->after('description');
            });
            echo "✅ Added metadata column to markets\n";
        }

        // leads alterations (CRM scoring)
        if (!Schema::hasColumn('cmis.leads', 'score')) {
            Schema::table('cmis.leads', function (Blueprint $table) {
                $table->integer('score')->default(0)->after('status');
                $table->string('lifecycle_stage')->nullable()->after('score');
            });
            echo "✅ Added scoring columns to leads\n";
        }

        // roles alterations (performance optimization)
        if (!Schema::hasColumn('cmis.roles', 'permissions_count')) {
            Schema::table('cmis.roles', function (Blueprint $table) {
                $table->integer('permissions_count')->default(0)->after('description');
            });
            echo "✅ Added permissions_count to roles\n";
        }

        echo "\n";
        echo "✅ Table alterations consolidated successfully\n";
    }

    public function down(): void
    {
        // Rollback alterations in reverse order

        if (Schema::hasColumn('cmis.user_orgs', 'invitation_token')) {
            Schema::table('cmis.user_orgs', function (Blueprint $table) {
                $table->dropColumn(['invitation_token', 'invitation_expires_at']);
            });
        }

        if (Schema::hasColumn('public.markets', 'metadata')) {
            Schema::table('public.markets', function (Blueprint $table) {
                $table->dropColumn('metadata');
            });
        }

        if (Schema::hasColumn('cmis.leads', 'score')) {
            Schema::table('cmis.leads', function (Blueprint $table) {
                $table->dropColumn(['score', 'lifecycle_stage']);
            });
        }

        if (Schema::hasColumn('cmis.roles', 'permissions_count')) {
            Schema::table('cmis.roles', function (Blueprint $table) {
                $table->dropColumn('permissions_count');
            });
        }

        echo "✅ Table alterations rolled back\n";
    }
};
