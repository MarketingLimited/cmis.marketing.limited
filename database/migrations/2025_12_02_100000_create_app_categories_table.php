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
     */
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('cmis.app_categories')) {
            return;
        }

        Schema::create('cmis.app_categories', function (Blueprint $table) {
            $table->uuid('category_id')->primary();
            $table->string('slug', 30)->unique();
            $table->string('name_key', 100);
            $table->string('description_key', 100);
            $table->string('icon', 50);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->useCurrent();

            $table->index('slug');
            $table->index('sort_order');
        });

        // Enable public RLS (read-only for all, system-managed)
        $this->enablePublicRLS('cmis.app_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP POLICY IF EXISTS allow_all ON cmis.app_categories');
        DB::statement('ALTER TABLE cmis.app_categories DISABLE ROW LEVEL SECURITY');
        Schema::dropIfExists('cmis.app_categories');
    }
};
