<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add updated_at to orgs table if it doesn't exist
        if (!Schema::hasColumn('cmis.orgs', 'updated_at')) {
            Schema::table('cmis.orgs', function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            });
            echo "✅ Added updated_at to cmis.orgs\n";
        }

        // Add campaign_name to campaigns table if it doesn't exist
        if (!Schema::hasColumn('cmis.campaigns', 'campaign_name')) {
            Schema::table('cmis.campaigns', function (Blueprint $table) {
                $table->string('campaign_name')->nullable()->after('name');
            });
            echo "✅ Added campaign_name to cmis.campaigns\n";
        }

        // Add integration_id to ad_sets if it doesn't exist (fix NULL constraint)
        if (!Schema::hasColumn('cmis.ad_sets', 'integration_id')) {
            Schema::table('cmis.ad_sets', function (Blueprint $table) {
                $table->uuid('integration_id')->nullable()->after('org_id');
            });
            echo "✅ Added integration_id to cmis.ad_sets\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('cmis.orgs', 'updated_at')) {
            Schema::table('cmis.orgs', function (Blueprint $table) {
                $table->dropColumn('updated_at');
            });
        }

        if (Schema::hasColumn('cmis.campaigns', 'campaign_name')) {
            Schema::table('cmis.campaigns', function (Blueprint $table) {
                $table->dropColumn('campaign_name');
            });
        }

        if (Schema::hasColumn('cmis.ad_sets', 'integration_id')) {
            Schema::table('cmis.ad_sets', function (Blueprint $table) {
                $table->dropColumn('integration_id');
            });
        }
    }
};
