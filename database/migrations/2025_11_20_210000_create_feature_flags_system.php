<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Helper to check if table exists
     */
    private function tableExists(string $schema, string $table): bool
    {
        $result = DB::selectOne("
            SELECT COUNT(*) as count
            FROM information_schema.tables
            WHERE table_schema = ?
            AND table_name = ?
        ", [$schema, $table]);
        return $result->count > 0;
    }

    /**
     * Run the migrations.
     */
    public function up()
    {
        // Create feature_flags table if not exists
        if (!$this->tableExists('cmis', 'feature_flags')) {
            DB::statement("
                CREATE TABLE cmis.feature_flags (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    feature_key VARCHAR(255) NOT NULL,
                    scope_type VARCHAR(20) NOT NULL DEFAULT 'system' CHECK (scope_type IN ('system', 'organization', 'platform', 'user')),
                    scope_id UUID NULL,
                    value BOOLEAN NOT NULL DEFAULT FALSE,
                    description TEXT NULL,
                    metadata JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT feature_flags_unique_scope UNIQUE (feature_key, scope_type, scope_id)
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_flags_key_scope ON cmis.feature_flags(feature_key, scope_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_flags_scope_id ON cmis.feature_flags(scope_id)");

            // Add comments
            DB::statement("COMMENT ON TABLE cmis.feature_flags IS 'Feature toggle/flag configuration for multi-tenant control'");

            // Enable Row-Level Security
            DB::statement("ALTER TABLE cmis.feature_flags ENABLE ROW LEVEL SECURITY");

            // Create RLS policies
            DB::statement("DROP POLICY IF EXISTS feature_flags_system_visible ON cmis.feature_flags");
            DB::statement("
                CREATE POLICY feature_flags_system_visible ON cmis.feature_flags
                FOR SELECT USING (scope_type = 'system')
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flags_org_isolation ON cmis.feature_flags");
            DB::statement("
                CREATE POLICY feature_flags_org_isolation ON cmis.feature_flags
                FOR SELECT USING (
                    scope_type = 'organization'
                    AND scope_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                )
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flags_user_isolation ON cmis.feature_flags");
            DB::statement("
                CREATE POLICY feature_flags_user_isolation ON cmis.feature_flags
                FOR SELECT USING (
                    scope_type = 'user'
                    AND scope_id = NULLIF(current_setting('app.current_user_id', true), '')::uuid
                )
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flags_platform_visible ON cmis.feature_flags");
            DB::statement("
                CREATE POLICY feature_flags_platform_visible ON cmis.feature_flags
                FOR SELECT USING (scope_type = 'platform')
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flags_admin_modify ON cmis.feature_flags");
            DB::statement("
                CREATE POLICY feature_flags_admin_modify ON cmis.feature_flags
                FOR ALL USING (current_setting('app.is_admin', true)::boolean = true)
                WITH CHECK (current_setting('app.is_admin', true)::boolean = true)
            ");
        }

        // Create feature_flag_overrides table if not exists
        if (!$this->tableExists('cmis', 'feature_flag_overrides')) {
            DB::statement("
                CREATE TABLE cmis.feature_flag_overrides (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    feature_flag_id UUID NOT NULL REFERENCES cmis.feature_flags(id) ON DELETE CASCADE,
                    target_id UUID NOT NULL,
                    target_type VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (target_type IN ('user', 'organization')),
                    value BOOLEAN NOT NULL,
                    reason TEXT NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT feature_flag_overrides_unique UNIQUE (feature_flag_id, target_id, target_type)
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_overrides_target ON cmis.feature_flag_overrides(target_id, target_type)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_overrides_expiry ON cmis.feature_flag_overrides(expires_at)");

            DB::statement("COMMENT ON TABLE cmis.feature_flag_overrides IS 'User or org-specific overrides for feature flags'");

            // Enable RLS
            DB::statement("ALTER TABLE cmis.feature_flag_overrides ENABLE ROW LEVEL SECURITY");

            DB::statement("DROP POLICY IF EXISTS feature_flag_overrides_user_visible ON cmis.feature_flag_overrides");
            DB::statement("
                CREATE POLICY feature_flag_overrides_user_visible ON cmis.feature_flag_overrides
                FOR SELECT USING (
                    target_type = 'user'
                    AND target_id = NULLIF(current_setting('app.current_user_id', true), '')::uuid
                )
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flag_overrides_org_visible ON cmis.feature_flag_overrides");
            DB::statement("
                CREATE POLICY feature_flag_overrides_org_visible ON cmis.feature_flag_overrides
                FOR SELECT USING (
                    target_type = 'organization'
                    AND target_id = NULLIF(current_setting('app.current_org_id', true), '')::uuid
                )
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flag_overrides_admin_modify ON cmis.feature_flag_overrides");
            DB::statement("
                CREATE POLICY feature_flag_overrides_admin_modify ON cmis.feature_flag_overrides
                FOR ALL USING (current_setting('app.is_admin', true)::boolean = true)
                WITH CHECK (current_setting('app.is_admin', true)::boolean = true)
            ");
        }

        // Create feature_flag_audit_log table if not exists
        if (!$this->tableExists('cmis', 'feature_flag_audit_log')) {
            DB::statement("
                CREATE TABLE cmis.feature_flag_audit_log (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    feature_flag_id UUID NULL REFERENCES cmis.feature_flags(id) ON DELETE SET NULL,
                    feature_key VARCHAR(255) NOT NULL,
                    action VARCHAR(20) NOT NULL DEFAULT 'updated' CHECK (action IN ('created', 'updated', 'deleted', 'accessed')),
                    old_value BOOLEAN NULL,
                    new_value BOOLEAN NULL,
                    changed_by_user_id UUID NULL,
                    changed_by_user_email VARCHAR(255) NULL,
                    metadata JSONB NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )
            ");

            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_audit_flag_id ON cmis.feature_flag_audit_log(feature_flag_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_audit_key ON cmis.feature_flag_audit_log(feature_key)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_audit_user ON cmis.feature_flag_audit_log(changed_by_user_id)");
            DB::statement("CREATE INDEX IF NOT EXISTS idx_feature_audit_timestamp ON cmis.feature_flag_audit_log(created_at)");

            DB::statement("COMMENT ON TABLE cmis.feature_flag_audit_log IS 'Audit trail for all feature flag changes'");

            // Enable RLS
            DB::statement("ALTER TABLE cmis.feature_flag_audit_log ENABLE ROW LEVEL SECURITY");

            DB::statement("DROP POLICY IF EXISTS feature_flag_audit_visible ON cmis.feature_flag_audit_log");
            DB::statement("
                CREATE POLICY feature_flag_audit_visible ON cmis.feature_flag_audit_log
                FOR SELECT USING (true)
            ");

            DB::statement("DROP POLICY IF EXISTS feature_flag_audit_system_insert ON cmis.feature_flag_audit_log");
            DB::statement("
                CREATE POLICY feature_flag_audit_system_insert ON cmis.feature_flag_audit_log
                FOR INSERT WITH CHECK (true)
            ");
        }

        // Create trigger function
        DB::statement("
            CREATE OR REPLACE FUNCTION cmis.feature_flag_audit_trigger()
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF (TG_OP = 'INSERT') THEN
                    INSERT INTO cmis.feature_flag_audit_log (
                        id, feature_flag_id, feature_key, action, old_value, new_value, metadata, created_at
                    ) VALUES (
                        gen_random_uuid(),
                        NEW.id,
                        NEW.feature_key,
                        'created',
                        NULL,
                        NEW.value,
                        jsonb_build_object('scope_type', NEW.scope_type, 'scope_id', NEW.scope_id),
                        NOW()
                    );
                    RETURN NEW;
                ELSIF (TG_OP = 'UPDATE') THEN
                    IF (OLD.value IS DISTINCT FROM NEW.value) THEN
                        INSERT INTO cmis.feature_flag_audit_log (
                            id, feature_flag_id, feature_key, action, old_value, new_value, metadata, created_at
                        ) VALUES (
                            gen_random_uuid(),
                            NEW.id,
                            NEW.feature_key,
                            'updated',
                            OLD.value,
                            NEW.value,
                            jsonb_build_object('old_metadata', OLD.metadata, 'new_metadata', NEW.metadata),
                            NOW()
                        );
                    END IF;
                    RETURN NEW;
                ELSIF (TG_OP = 'DELETE') THEN
                    INSERT INTO cmis.feature_flag_audit_log (
                        id, feature_flag_id, feature_key, action, old_value, new_value, metadata, created_at
                    ) VALUES (
                        gen_random_uuid(),
                        OLD.id,
                        OLD.feature_key,
                        'deleted',
                        OLD.value,
                        NULL,
                        jsonb_build_object('scope_type', OLD.scope_type, 'scope_id', OLD.scope_id),
                        NOW()
                    );
                    RETURN OLD;
                END IF;
            END;
            \$\$ LANGUAGE plpgsql SECURITY DEFINER
        ");

        // Create trigger if not exists
        DB::statement("DROP TRIGGER IF EXISTS feature_flag_audit_changes ON cmis.feature_flags");
        DB::statement("
            CREATE TRIGGER feature_flag_audit_changes
            AFTER INSERT OR UPDATE OR DELETE ON cmis.feature_flags
            FOR EACH ROW
            EXECUTE FUNCTION cmis.feature_flag_audit_trigger()
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement("DROP TRIGGER IF EXISTS feature_flag_audit_changes ON cmis.feature_flags");
        DB::statement("DROP FUNCTION IF EXISTS cmis.feature_flag_audit_trigger()");
        DB::statement("DROP TABLE IF EXISTS cmis.feature_flag_audit_log CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.feature_flag_overrides CASCADE");
        DB::statement("DROP TABLE IF EXISTS cmis.feature_flags CASCADE");
    }
};
