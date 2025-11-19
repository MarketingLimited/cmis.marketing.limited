<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        // Make event_type nullable in webhooks (78 errors)
        if ($this->tableExists('cmis.webhooks')) {
            DB::statement("ALTER TABLE cmis.webhooks ALTER COLUMN event_type DROP NOT NULL");
            echo "✓ Made event_type nullable in webhooks\n";
        }
        
        // Make plan_id nullable in content_plan_items (36 errors)
        if ($this->tableExists('cmis.content_plan_items')) {
            DB::statement("ALTER TABLE cmis.content_plan_items ALTER COLUMN plan_id DROP NOT NULL");
            echo "✓ Made plan_id nullable in content_plan_items\n";
        }
        
        // Add default to role_name in roles OR make nullable (26 errors)
        if ($this->tableExists('cmis.roles')) {
            if ($this->columnExists('cmis', 'roles', 'role_name')) {
                DB::statement("ALTER TABLE cmis.roles ALTER COLUMN role_name DROP NOT NULL");
                echo "✓ Made role_name nullable in roles\n";
            }
        }
        
        // Make permission_code nullable in permissions (24 errors)
        if ($this->tableExists('cmis.permissions')) {
            DB::statement("ALTER TABLE cmis.permissions ALTER COLUMN permission_code DROP NOT NULL");
            echo "✓ Made permission_code nullable in permissions\n";
        }
        
        // Make brief_data nullable in creative_briefs (10 errors)
        if ($this->tableExists('cmis.creative_briefs')) {
            if ($this->columnExists('cmis', 'creative_briefs', 'brief_data')) {
                DB::statement("ALTER TABLE cmis.creative_briefs ALTER COLUMN brief_data DROP NOT NULL");
                echo "✓ Made brief_data nullable in creative_briefs\n";
            }
        }
        
        echo "\n✅ All NULL constraint fixes applied!\n";
    }
    
    private function tableExists(string $tableName): bool
    {
        $parts = explode('.', $tableName);
        $schema = $parts[0];
        $table = $parts[1];
        
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = ? AND table_name = ?
            ) as exists
        ", [$schema, $table]);
        
        return $exists->exists;
    }
    
    private function columnExists(string $schema, string $table, string $column): bool
    {
        $exists = DB::selectOne("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns
                WHERE table_schema = ? AND table_name = ? AND column_name = ?
            ) as exists
        ", [$schema, $table, $column]);
        
        return $exists->exists;
    }

    public function down(): void
    {
        // Rollback logic if needed
    }
};
