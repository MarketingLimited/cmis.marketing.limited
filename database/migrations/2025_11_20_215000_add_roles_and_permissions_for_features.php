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
     * @return void
     */
    public function up()
    {
        // Add is_admin column to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_admin')->default(false)->after('email');
            });
        }

        // Add role column to users table if it doesn't exist
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 50)->default('user')->after('is_admin');
                $table->index('role');
            });
        }

        // Create roles table
        Schema::create('cmis.roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100)->unique();
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->jsonb('permissions')->default('{}');
            $table->boolean('is_system')->default(false)->comment('System roles cannot be deleted');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            $table->index('slug');
        });

        DB::statement("COMMENT ON TABLE cmis.roles IS 'User roles with associated permissions'");

        // Enable RLS
        DB::statement("ALTER TABLE cmis.roles ENABLE ROW LEVEL SECURITY");

        // RLS Policy: All authenticated users can read roles
        DB::statement("
            CREATE POLICY roles_read_all ON cmis.roles
            FOR SELECT
            USING (true);
        ");

        // RLS Policy: Only admins can modify roles
        DB::statement("
            CREATE POLICY roles_admin_modify ON cmis.roles
            FOR ALL
            USING (current_setting('app.is_admin', true)::boolean = true)
            WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
        ");

        // Create feature_permissions table (for granular platform/feature permissions)
        Schema::create('cmis.feature_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable()->comment('Specific user (if user-level)');
            $table->uuid('role_id')->nullable()->comment('Role (if role-level)');
            $table->string('feature_key', 255)->comment('Feature key (e.g., paid_campaigns.meta.enabled)');
            $table->enum('permission_type', ['view', 'use', 'manage'])->default('use');
            $table->boolean('granted')->default(true)->comment('true = allow, false = deny');
            $table->text('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign keys
            $table->foreign('role_id')->references('id')->on('cmis.roles')->onDelete('cascade');

            // Indexes
            $table->index(['user_id', 'feature_key'], 'idx_feature_perms_user_feature');
            $table->index(['role_id', 'feature_key'], 'idx_feature_perms_role_feature');
            $table->index('expires_at', 'idx_feature_perms_expiry');
        });

        DB::statement("COMMENT ON TABLE cmis.feature_permissions IS 'Granular permissions for features and platforms per user or role'");

        // Enable RLS
        DB::statement("ALTER TABLE cmis.feature_permissions ENABLE ROW LEVEL SECURITY");

        // RLS Policy: Users can see their own permissions
        DB::statement("
            CREATE POLICY feature_permissions_user_read ON cmis.feature_permissions
            FOR SELECT
            USING (
                user_id = current_setting('app.current_user_id', true)::uuid
                OR role_id IN (
                    SELECT r.id FROM cmis.roles r
                    JOIN users u ON u.role = r.slug
                    WHERE u.id = current_setting('app.current_user_id', true)::uuid
                )
            );
        ");

        // RLS Policy: Admins can modify
        DB::statement("
            CREATE POLICY feature_permissions_admin_modify ON cmis.feature_permissions
            FOR ALL
            USING (current_setting('app.is_admin', true)::boolean = true)
            WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
        ");

        // Grant permissions
        DB::statement("GRANT SELECT ON cmis.roles TO cmis_app_role");
        DB::statement("GRANT SELECT ON cmis.feature_permissions TO cmis_app_role");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop tables
        Schema::dropIfExists('cmis.feature_permissions');
        Schema::dropIfExists('cmis.roles');

        // Remove columns from users table
        if (Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_admin');
            });
        }

        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
