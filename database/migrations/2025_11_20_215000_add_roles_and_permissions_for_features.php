<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;
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

        // Track if roles table was created by this migration
        $rolesTableCreated = false;

        // Create roles table if it doesn't exist
        if (!Schema::hasTable('cmis.roles')) {
            $rolesTableCreated = true;
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

            // Enable RLS with public read access (all users can read roles)
            $this->enablePublicRLS('cmis.roles');

            // RLS Policy: Only admins can modify roles (additional custom policy)
            DB::statement("
                CREATE POLICY roles_admin_modify ON cmis.roles
                FOR INSERT
                USING (current_setting('app.is_admin', true)::boolean = true)
                WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
            ");

            DB::statement("
                CREATE POLICY roles_admin_update ON cmis.roles
                FOR UPDATE
                USING (current_setting('app.is_admin', true)::boolean = true)
                WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
            ");

            DB::statement("
                CREATE POLICY roles_admin_delete ON cmis.roles
                FOR DELETE
                USING (current_setting('app.is_admin', true)::boolean = true);
            ");
        }

        // Create feature_permissions table (for granular platform/feature permissions)
        Schema::create('cmis.feature_permissions', function (Blueprint $table) use ($rolesTableCreated) {
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

            // Foreign keys - only add if we created the roles table in this migration
            // The existing cmis.roles table has a different structure (role_id vs id)
            if ($rolesTableCreated) {
                $table->foreign('role_id')->references('id')->on('cmis.roles')->onDelete('cascade');
            }

            // Indexes
            $table->index(['user_id', 'feature_key'], 'idx_feature_perms_user_feature');
            $table->index(['role_id', 'feature_key'], 'idx_feature_perms_role_feature');
            $table->index('expires_at', 'idx_feature_perms_expiry');
        });

        DB::statement("COMMENT ON TABLE cmis.feature_permissions IS 'Granular permissions for features and platforms per user or role'");

        // Enable RLS (using trait to enable, then add custom policies)
        DB::statement("ALTER TABLE cmis.feature_permissions ENABLE ROW LEVEL SECURITY");

        // RLS Policy: Users can see their own permissions (custom logic)
        // Note: This policy assumes the roles table has the expected schema
        // If using the existing cmis.roles table, this policy may need adjustment
        if ($rolesTableCreated) {
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
        } else {
            // Simpler policy when using existing roles table with different schema
            DB::statement("
                CREATE POLICY feature_permissions_user_read ON cmis.feature_permissions
                FOR SELECT
                USING (
                    user_id = current_setting('app.current_user_id', true)::uuid
                );
            ");
        }

        // RLS Policy: Admins can modify (custom policies for INSERT/UPDATE/DELETE)
        DB::statement("
            CREATE POLICY feature_permissions_admin_insert ON cmis.feature_permissions
            FOR INSERT
            WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
        ");

        DB::statement("
            CREATE POLICY feature_permissions_admin_update ON cmis.feature_permissions
            FOR UPDATE
            USING (current_setting('app.is_admin', true)::boolean = true)
            WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
        ");

        DB::statement("
            CREATE POLICY feature_permissions_admin_delete ON cmis.feature_permissions
            FOR DELETE
            USING (current_setting('app.is_admin', true)::boolean = true);
        ");

        // Grant permissions (only if role exists)
        try {
            DB::statement("GRANT SELECT ON cmis.roles TO cmis_app_role");
            DB::statement("GRANT SELECT ON cmis.feature_permissions TO cmis_app_role");
        } catch (\Exception $e) {
            // cmis_app_role may not exist in test environments
            // This is non-critical, so we can safely ignore
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Disable RLS and drop tables (trait handles policy cleanup)
        if (Schema::hasTable('cmis.feature_permissions')) {
            $this->disableRLS('cmis.feature_permissions');
        }
        if (Schema::hasTable('cmis.roles')) {
            $this->disableRLS('cmis.roles');
        }

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
