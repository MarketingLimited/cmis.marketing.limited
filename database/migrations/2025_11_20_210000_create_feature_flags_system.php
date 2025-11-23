<?php

use Illuminate\Database\Migrations\Migration;
use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
        // Create feature_flags table in cmis schema
        Schema::create('cmis.feature_flags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('feature_key', 255)->comment('Unique feature key (e.g., scheduling.meta.enabled)');
            $table->enum('scope_type', ['system', 'organization', 'platform', 'user'])->default('system');
            $table->uuid('scope_id')->nullable()->comment('References org_id, platform_id, or user_id depending on scope_type');
            $table->boolean('value')->default(false)->comment('Feature enabled (true) or disabled (false)');
            $table->text('description')->nullable()->comment('Human-readable description');
            $table->jsonb('metadata')->nullable()->comment('Additional metadata (rollout percentage, conditions, etc.)');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Unique constraint: one flag per scope
            $table->unique(['feature_key', 'scope_type', 'scope_id'], 'feature_flags_unique_scope');

            // Index for fast lookups
            $table->index(['feature_key', 'scope_type'], 'idx_feature_flags_key_scope');
            $table->index('scope_id', 'idx_feature_flags_scope_id');
        });

        // Add comments
        DB::statement("COMMENT ON TABLE cmis.feature_flags IS 'Feature toggle/flag configuration for multi-tenant control'");
        DB::statement("COMMENT ON COLUMN cmis.feature_flags.feature_key IS 'Hierarchical key: {category}.{platform}.{action} (e.g., scheduling.meta.enabled)'");
        DB::statement("COMMENT ON COLUMN cmis.feature_flags.scope_type IS 'Level of the flag: system (global), organization (per-tenant), platform (per-integration), user (per-user)'");
        DB::statement("COMMENT ON COLUMN cmis.feature_flags.value IS 'Boolean flag status: true = enabled, false = disabled'");

        // Enable Row-Level Security (without default policy - using custom policies below)
        DB::statement("ALTER TABLE cmis.feature_flags ENABLE ROW LEVEL SECURITY");

        // RLS Policy: System-level flags are visible to all
        DB::statement("
            CREATE POLICY feature_flags_system_visible ON cmis.feature_flags
            FOR SELECT
            USING (scope_type = 'system');
        ");

        // RLS Policy: Organization-level flags visible to members of that org
        DB::statement("
            CREATE POLICY feature_flags_org_isolation ON cmis.feature_flags
            FOR SELECT
            USING (
                scope_type = 'organization'
                AND scope_id = current_setting('app.current_org_id', true)::uuid
            );
        ");

        // RLS Policy: User-level flags visible to that user only
        DB::statement("
            CREATE POLICY feature_flags_user_isolation ON cmis.feature_flags
            FOR SELECT
            USING (
                scope_type = 'user'
                AND scope_id = current_setting('app.current_user_id', true)::uuid
            );
        ");

        // RLS Policy: Platform-level flags visible to all (managed by system admins)
        DB::statement("
            CREATE POLICY feature_flags_platform_visible ON cmis.feature_flags
            FOR SELECT
            USING (scope_type = 'platform');
        ");

        // RLS Policy: Allow insert/update/delete for system admins only
        DB::statement("
            CREATE POLICY feature_flags_admin_modify ON cmis.feature_flags
            FOR ALL
            USING (current_setting('app.is_admin', true)::boolean = true)
            WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
        ");

        // Create feature_flag_overrides table for temporary or user-specific overrides
        Schema::create('cmis.feature_flag_overrides', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feature_flag_id')->comment('References cmis.feature_flags.id');
            $table->uuid('target_id')->comment('User ID or Org ID being overridden');
            $table->enum('target_type', ['user', 'organization'])->default('user');
            $table->boolean('value')->comment('Override value');
            $table->text('reason')->nullable()->comment('Reason for override (beta testing, premium feature, etc.)');
            $table->timestamp('expires_at')->nullable()->comment('Optional expiration for temporary overrides');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign key
            $table->foreign('feature_flag_id')->references('id')->on('cmis.feature_flags')->onDelete('cascade');

            // Unique constraint
            $table->unique(['feature_flag_id', 'target_id', 'target_type'], 'feature_flag_overrides_unique');

            // Indexes
            $table->index(['target_id', 'target_type'], 'idx_feature_overrides_target');
            $table->index('expires_at', 'idx_feature_overrides_expiry');
        });

        DB::statement("COMMENT ON TABLE cmis.feature_flag_overrides IS 'User or org-specific overrides for feature flags (beta access, premium features)'");

        // Enable RLS on overrides (without default policy - using custom policies below)
        DB::statement("ALTER TABLE cmis.feature_flag_overrides ENABLE ROW LEVEL SECURITY");

        // RLS Policy: Users can see their own overrides
        DB::statement("
            CREATE POLICY feature_flag_overrides_user_visible ON cmis.feature_flag_overrides
            FOR SELECT
            USING (
                target_type = 'user'
                AND target_id = current_setting('app.current_user_id', true)::uuid
            );
        ");

        // RLS Policy: Org overrides visible to org members
        DB::statement("
            CREATE POLICY feature_flag_overrides_org_visible ON cmis.feature_flag_overrides
            FOR SELECT
            USING (
                target_type = 'organization'
                AND target_id = current_setting('app.current_org_id', true)::uuid
            );
        ");

        // RLS Policy: Only admins can modify
        DB::statement("
            CREATE POLICY feature_flag_overrides_admin_modify ON cmis.feature_flag_overrides
            FOR ALL
            USING (current_setting('app.is_admin', true)::boolean = true)
            WITH CHECK (current_setting('app.is_admin', true)::boolean = true);
        ");

        // Create audit log table for feature flag changes
        Schema::create('cmis.feature_flag_audit_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('feature_flag_id')->nullable();
            $table->string('feature_key', 255);
            $table->enum('action', ['created', 'updated', 'deleted', 'accessed'])->default('updated');
            $table->boolean('old_value')->nullable();
            $table->boolean('new_value')->nullable();
            $table->uuid('changed_by_user_id')->nullable()->comment('User who made the change');
            $table->string('changed_by_user_email', 255)->nullable();
            $table->jsonb('metadata')->nullable()->comment('Additional context about the change');
            $table->timestamp('created_at')->useCurrent();

            // Foreign key (nullable for deleted flags)
            $table->foreign('feature_flag_id')->references('id')->on('cmis.feature_flags')->onDelete('set null');

            // Indexes
            $table->index('feature_flag_id', 'idx_feature_audit_flag_id');
            $table->index('feature_key', 'idx_feature_audit_key');
            $table->index('changed_by_user_id', 'idx_feature_audit_user');
            $table->index('created_at', 'idx_feature_audit_timestamp');
        });

        DB::statement("COMMENT ON TABLE cmis.feature_flag_audit_log IS 'Audit trail for all feature flag changes (compliance and debugging)'");

        // Enable RLS on audit log (without default policy - using custom policies below)
        DB::statement("ALTER TABLE cmis.feature_flag_audit_log ENABLE ROW LEVEL SECURITY");

        // RLS Policy: All authenticated users can read audit logs (for transparency)
        DB::statement("
            CREATE POLICY feature_flag_audit_visible ON cmis.feature_flag_audit_log
            FOR SELECT
            USING (true);
        ");

        // RLS Policy: Only system can insert audit logs (via triggers or service layer)
        DB::statement("
            CREATE POLICY feature_flag_audit_system_insert ON cmis.feature_flag_audit_log
            FOR INSERT
            WITH CHECK (true);
        ");

        // Create trigger function to automatically log changes
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.feature_flag_audit_trigger()
            RETURNS TRIGGER AS $$
            BEGIN
                IF (TG_OP = 'INSERT') THEN
                    INSERT INTO cmis.feature_flag_audit_log (
                        id, feature_flag_id, feature_key, action, old_value, new_value, changed_by_user_id, metadata, created_at
                    ) VALUES (
                        gen_random_uuid(),
                        NEW.id,
                        NEW.feature_key,
                        'created',
                        NULL,
                        NEW.value,
                        current_setting('app.current_user_id', true)::uuid,
                        jsonb_build_object('scope_type', NEW.scope_type, 'scope_id', NEW.scope_id),
                        NOW()
                    );
                    RETURN NEW;
                ELSIF (TG_OP = 'UPDATE') THEN
                    IF (OLD.value IS DISTINCT FROM NEW.value) THEN
                        INSERT INTO cmis.feature_flag_audit_log (
                            id, feature_flag_id, feature_key, action, old_value, new_value, changed_by_user_id, metadata, created_at
                        ) VALUES (
                            gen_random_uuid(),
                            NEW.id,
                            NEW.feature_key,
                            'updated',
                            OLD.value,
                            NEW.value,
                            current_setting('app.current_user_id', true)::uuid,
                            jsonb_build_object('old_metadata', OLD.metadata, 'new_metadata', NEW.metadata),
                            NOW()
                        );
                    END IF;
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO cmis.feature_flag_audit_log (
                        id, feature_flag_id, feature_key, action, old_value, new_value, changed_by_user_id, metadata, created_at
                    ) VALUES (
                        gen_random_uuid(),
                        OLD.id,
                        OLD.feature_key,
                        'deleted',
                        OLD.value,
                        NULL,
                        current_setting('app.current_user_id', true)::uuid,
                        jsonb_build_object('scope_type', OLD.scope_type, 'scope_id', OLD.scope_id),
                        NOW()
                    );
                    RETURN OLD;
                END IF;
            END;
            $$ LANGUAGE plpgsql SECURITY DEFINER;
        ");

        // Create trigger on feature_flags table
        DB::statement("
            CREATE TRIGGER feature_flag_audit_changes
            AFTER INSERT OR UPDATE OR DELETE ON cmis.feature_flags
            FOR EACH ROW
            EXECUTE FUNCTION cmis.feature_flag_audit_trigger();
        ");

        // Grant permissions (only if role exists)
        $roleExists = DB::select("SELECT 1 FROM pg_roles WHERE rolname = 'cmis_app_role'");
        if (!empty($roleExists)) {
            DB::statement("GRANT SELECT ON cmis.feature_flags TO cmis_app_role");
            DB::statement("GRANT SELECT ON cmis.feature_flag_overrides TO cmis_app_role");
            DB::statement("GRANT SELECT, INSERT ON cmis.feature_flag_audit_log TO cmis_app_role");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop trigger first
        DB::statement("DROP TRIGGER IF EXISTS feature_flag_audit_changes ON cmis.feature_flags");
        DB::statement("DROP FUNCTION IF EXISTS cmis.feature_flag_audit_trigger()");

        // Drop tables in reverse order
        Schema::dropIfExists('cmis.feature_flag_audit_log');
        Schema::dropIfExists('cmis.feature_flag_overrides');
        Schema::dropIfExists('cmis.feature_flags');
    }
};
