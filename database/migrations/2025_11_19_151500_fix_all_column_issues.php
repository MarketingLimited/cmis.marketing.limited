<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET search_path TO cmis,public');
        
        // Fix team_members table - add team_member_id as alias to member_id (226 errors)
        if ($this->tableExists('cmis.team_members')) {
            if (!$this->columnExists('cmis', 'team_members', 'team_member_id')) {
                DB::statement("ALTER TABLE cmis.team_members ADD COLUMN team_member_id UUID");
                DB::statement("UPDATE cmis.team_members SET team_member_id = member_id WHERE team_member_id IS NULL");
                DB::statement("ALTER TABLE cmis.team_members ALTER COLUMN team_member_id SET NOT NULL");
                echo "✓ Added team_member_id to team_members\n";
            }
        }
        
        // Add updated_at to api_logs (38 errors)
        if ($this->tableExists('cmis.api_logs')) {
            if (!$this->columnExists('cmis', 'api_logs', 'updated_at')) {
                DB::statement("ALTER TABLE cmis.api_logs ADD COLUMN updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()");
                echo "✓ Added updated_at to api_logs\n";
            }
        }
        
        // Add name to markets (28 errors) - through view
        if (!$this->columnExists('public', 'markets', 'name')) {
            DB::statement("ALTER TABLE public.markets ADD COLUMN name VARCHAR(255)");
            DB::statement("UPDATE public.markets SET name = market_name WHERE name IS NULL");
            
            // Recreate view
            DB::statement("DROP VIEW IF EXISTS cmis.markets CASCADE");
            DB::statement("CREATE OR REPLACE VIEW cmis.markets AS
                SELECT market_id, market_name, language_code, currency_code, text_direction, code, name
                FROM public.markets");
            echo "✓ Added name to markets\n";
        }
        
        // Add knowledge_id to knowledge_index (28 errors)
        if ($this->tableExists('cmis.knowledge_index')) {
            if (!$this->columnExists('cmis', 'knowledge_index', 'knowledge_id')) {
                DB::statement("ALTER TABLE cmis.knowledge_index ADD COLUMN knowledge_id UUID");
                DB::statement("UPDATE cmis.knowledge_index SET knowledge_id = index_id WHERE knowledge_id IS NULL");
                echo "✓ Added knowledge_id to knowledge_index\n";
            }
        }
        
        // Add platform to posts (26 errors)
        if ($this->tableExists('cmis.posts')) {
            if (!$this->columnExists('cmis', 'posts', 'platform')) {
                DB::statement("ALTER TABLE cmis.posts ADD COLUMN platform VARCHAR(50)");
                echo "✓ Added platform to posts\n";
            }
        }
        
        // Add commentable_type to comments (26 errors)
        if ($this->tableExists('cmis.comments')) {
            if (!$this->columnExists('cmis', 'comments', 'commentable_type')) {
                DB::statement("ALTER TABLE cmis.comments ADD COLUMN commentable_type VARCHAR(100)");
                DB::statement("UPDATE cmis.comments SET commentable_type = entity_type WHERE commentable_type IS NULL");
                echo "✓ Added commentable_type to comments\n";
            }
            
            if (!$this->columnExists('cmis', 'comments', 'commentable_id')) {
                DB::statement("ALTER TABLE cmis.comments ADD COLUMN commentable_id UUID");
                DB::statement("UPDATE cmis.comments SET commentable_id = entity_id WHERE commentable_id IS NULL");
                echo "✓ Added commentable_id to comments\n";
            }
        }
        
        // Add total_amount to budgets (22 errors)
        if ($this->tableExists('cmis.budgets')) {
            if (!$this->columnExists('cmis', 'budgets', 'total_amount')) {
                DB::statement("ALTER TABLE cmis.budgets ADD COLUMN total_amount DECIMAL(15,2)");
                DB::statement("UPDATE cmis.budgets SET total_amount = amount WHERE total_amount IS NULL");
                echo "✓ Added total_amount to budgets\n";
            }
        }
        
        // Add title to knowledge_index (20 errors)
        if ($this->tableExists('cmis.knowledge_index')) {
            if (!$this->columnExists('cmis', 'knowledge_index', 'title')) {
                DB::statement("ALTER TABLE cmis.knowledge_index ADD COLUMN title VARCHAR(500)");
                echo "✓ Added title to knowledge_index\n";
            }
        }
        
        // Add score to leads (16 errors)
        if ($this->tableExists('cmis.leads')) {
            if (!$this->columnExists('cmis', 'leads', 'score')) {
                DB::statement("ALTER TABLE cmis.leads ADD COLUMN score INTEGER DEFAULT 0");
                echo "✓ Added score to leads\n";
            }
        }
        
        // Add log_id to audit_logs (16 errors)
        if ($this->tableExists('cmis.audit_logs')) {
            if (!$this->columnExists('cmis', 'audit_logs', 'log_id')) {
                DB::statement("ALTER TABLE cmis.audit_logs ADD COLUMN log_id UUID");
                DB::statement("UPDATE cmis.audit_logs SET log_id = audit_id WHERE log_id IS NULL");
                echo "✓ Added log_id to audit_logs\n";
            }
        }
        
        echo "\n✅ All column fixes applied successfully!\n";
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
