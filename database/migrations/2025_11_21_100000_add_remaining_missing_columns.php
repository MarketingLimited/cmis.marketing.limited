<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add missing columns identified in test error analysis
     * Fixes approximately 50+ test failures
     */
    public function up(): void
    {
        // Fix scheduled_at column (8 test failures)
        // Check if column exists in scheduled_posts table
        if (Schema::hasTable('cmis.scheduled_posts')) {
            if (!Schema::hasColumn('cmis.scheduled_posts', 'scheduled_at')) {
                Schema::table('cmis.scheduled_posts', function (Blueprint $table) {
                    $table->timestamp('scheduled_at')->nullable()->after('status');
                });
                echo "✓ Added scheduled_at to cmis.scheduled_posts\n";
            }
        }

        // Fix campaign_analytics columns (10+ test failures)
        if (Schema::hasTable('cmis.campaign_analytics')) {
            if (!Schema::hasColumn('cmis.campaign_analytics', 'impressions')) {
                Schema::table('cmis.campaign_analytics', function (Blueprint $table) {
                    $table->bigInteger('impressions')->default(0)->after('campaign_id');
                });
                echo "✓ Added impressions to cmis.campaign_analytics\n";
            }

            if (!Schema::hasColumn('cmis.campaign_analytics', 'spend')) {
                Schema::table('cmis.campaign_analytics', function (Blueprint $table) {
                    $table->decimal('spend', 15, 2)->default(0)->after('impressions');
                });
                echo "✓ Added spend to cmis.campaign_analytics\n";
            }

            if (!Schema::hasColumn('cmis.campaign_analytics', 'clicks')) {
                Schema::table('cmis.campaign_analytics', function (Blueprint $table) {
                    $table->bigInteger('clicks')->default(0)->after('impressions');
                });
                echo "✓ Added clicks to cmis.campaign_analytics\n";
            }
        }

        // Fix platform_connections.access_token (4 test failures)
        if (Schema::hasTable('cmis_platform.platform_connections')) {
            if (!Schema::hasColumn('cmis_platform.platform_connections', 'access_token')) {
                Schema::table('cmis_platform.platform_connections', function (Blueprint $table) {
                    $table->text('access_token')->nullable()->after('platform_name');
                });
                echo "✓ Added access_token to cmis_platform.platform_connections\n";
            }
        }

        // Fix budgets columns (6 test failures)
        if (Schema::hasTable('cmis.budgets')) {
            if (!Schema::hasColumn('cmis.budgets', 'spent_amount')) {
                Schema::table('cmis.budgets', function (Blueprint $table) {
                    $table->decimal('spent_amount', 15, 2)->default(0)->after('amount');
                });
                echo "✓ Added spent_amount to cmis.budgets\n";
            }

            if (!Schema::hasColumn('cmis.budgets', 'campaign_id')) {
                Schema::table('cmis.budgets', function (Blueprint $table) {
                    $table->uuid('campaign_id')->nullable()->after('org_id');
                });
                echo "✓ Added campaign_id to cmis.budgets\n";
            }
        }

        // Fix leads.deleted_at (2 test failures)
        if (Schema::hasTable('cmis.leads')) {
            if (!Schema::hasColumn('cmis.leads', 'deleted_at')) {
                Schema::table('cmis.leads', function (Blueprint $table) {
                    $table->timestamp('deleted_at')->nullable();
                });
                echo "✓ Added deleted_at to cmis.leads\n";
            }
        }

        // Fix assets.deleted_at (2 test failures)
        if (Schema::hasTable('cmis.assets')) {
            if (!Schema::hasColumn('cmis.assets', 'deleted_at')) {
                Schema::table('cmis.assets', function (Blueprint $table) {
                    $table->timestamp('deleted_at')->nullable();
                });
                echo "✓ Added deleted_at to cmis.assets\n";
            }
        }

        // Fix team_members.is_active (2 test failures)
        if (Schema::hasTable('cmis.team_members')) {
            if (!Schema::hasColumn('cmis.team_members', 'is_active')) {
                Schema::table('cmis.team_members', function (Blueprint $table) {
                    $table->boolean('is_active')->default(true)->after('role_id');
                });
                echo "✓ Added is_active to cmis.team_members\n";
            }
        }

        // Fix markets.created_at (14 test failures)
        // First add created_at and updated_at to the public.markets source table
        if (!Schema::hasColumn('public.markets', 'created_at')) {
            Schema::table('public.markets', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->timestamp('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP'));
            });
            echo "✓ Added created_at and updated_at to public.markets\n";
        }

        // Then recreate the cmis.markets view to include these columns
        DB::statement("
            CREATE OR REPLACE VIEW cmis.markets AS
            SELECT
                market_id,
                market_name,
                language_code,
                currency_code,
                text_direction,
                created_at,
                updated_at
            FROM public.markets
        ");
        echo "✓ Updated cmis.markets view to include created_at and updated_at\n";

        // Fix templates columns (3 test failures)
        if (Schema::hasTable('cmis.templates')) {
            if (!Schema::hasColumn('cmis.templates', 'usage_count')) {
                Schema::table('cmis.templates', function (Blueprint $table) {
                    $table->integer('usage_count')->default(0)->after('template_type');
                });
                echo "✓ Added usage_count to cmis.templates\n";
            }
        }

        // Fix comments columns (need body for validation)
        if (Schema::hasTable('cmis.comments')) {
            if (!Schema::hasColumn('cmis.comments', 'body')) {
                Schema::table('cmis.comments', function (Blueprint $table) {
                    $table->text('body')->nullable()->after('comment_id');
                });
                echo "✓ Added body to cmis.comments\n";
            }
        }

        // Fix roles.priority (for role ordering)
        if (Schema::hasTable('cmis.roles')) {
            if (!Schema::hasColumn('cmis.roles', 'priority')) {
                Schema::table('cmis.roles', function (Blueprint $table) {
                    $table->integer('priority')->default(100)->after('role_name');
                });
                echo "✓ Added priority to cmis.roles\n";
            }
        }

        // Fix scheduled_posts.retry_count
        if (Schema::hasTable('cmis.scheduled_posts')) {
            if (!Schema::hasColumn('cmis.scheduled_posts', 'retry_count')) {
                Schema::table('cmis.scheduled_posts', function (Blueprint $table) {
                    $table->integer('retry_count')->default(0)->after('status');
                });
                echo "✓ Added retry_count to cmis.scheduled_posts\n";
            }
        }

        echo "\n✅ All missing columns migration completed successfully!\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns in reverse order
        if (Schema::hasColumn('cmis.scheduled_posts', 'retry_count')) {
            Schema::table('cmis.scheduled_posts', function (Blueprint $table) {
                $table->dropColumn('retry_count');
            });
        }

        if (Schema::hasColumn('cmis.roles', 'priority')) {
            Schema::table('cmis.roles', function (Blueprint $table) {
                $table->dropColumn('priority');
            });
        }

        if (Schema::hasColumn('cmis.comments', 'body')) {
            Schema::table('cmis.comments', function (Blueprint $table) {
                $table->dropColumn('body');
            });
        }

        if (Schema::hasColumn('cmis.templates', 'usage_count')) {
            Schema::table('cmis.templates', function (Blueprint $table) {
                $table->dropColumn('usage_count');
            });
        }

        if (Schema::hasColumn('cmis.team_members', 'is_active')) {
            Schema::table('cmis.team_members', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }

        if (Schema::hasColumn('cmis.assets', 'deleted_at')) {
            Schema::table('cmis.assets', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasColumn('cmis.leads', 'deleted_at')) {
            Schema::table('cmis.leads', function (Blueprint $table) {
                $table->dropColumn('deleted_at');
            });
        }

        if (Schema::hasColumn('cmis.budgets', 'campaign_id')) {
            Schema::table('cmis.budgets', function (Blueprint $table) {
                $table->dropColumn('campaign_id');
            });
        }

        if (Schema::hasColumn('cmis.budgets', 'spent_amount')) {
            Schema::table('cmis.budgets', function (Blueprint $table) {
                $table->dropColumn('spent_amount');
            });
        }

        if (Schema::hasColumn('cmis_platform.platform_connections', 'access_token')) {
            Schema::table('cmis_platform.platform_connections', function (Blueprint $table) {
                $table->dropColumn('access_token');
            });
        }

        if (Schema::hasColumn('cmis.campaign_analytics', 'clicks')) {
            Schema::table('cmis.campaign_analytics', function (Blueprint $table) {
                $table->dropColumn('clicks');
            });
        }

        if (Schema::hasColumn('cmis.campaign_analytics', 'spend')) {
            Schema::table('cmis.campaign_analytics', function (Blueprint $table) {
                $table->dropColumn('spend');
            });
        }

        if (Schema::hasColumn('cmis.campaign_analytics', 'impressions')) {
            Schema::table('cmis.campaign_analytics', function (Blueprint $table) {
                $table->dropColumn('impressions');
            });
        }

        if (Schema::hasColumn('cmis.scheduled_posts', 'scheduled_at')) {
            Schema::table('cmis.scheduled_posts', function (Blueprint $table) {
                $table->dropColumn('scheduled_at');
            });
        }
    }
};
