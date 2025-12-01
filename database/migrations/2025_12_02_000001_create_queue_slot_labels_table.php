<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * Creates the queue_slot_labels table for organizing time slots by category.
     * Labels are organization-wide and can be used across all profiles.
     * Supports solid colors and gradients for visual customization.
     */
    public function up(): void
    {
        Schema::create('cmis.queue_slot_labels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->string('name', 100);
            $table->string('slug', 100);
            $table->string('background_color', 100)->default('#3B82F6');
            $table->string('text_color', 20)->default('#FFFFFF');
            $table->string('color_type', 10)->default('solid'); // 'solid' or 'gradient'
            $table->string('gradient_start', 20)->nullable();
            $table->string('gradient_end', 20)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['org_id', 'deleted_at']);
            $table->index(['org_id', 'sort_order']);

            // Unique constraint for slug within organization (excluding deleted)
            $table->unique(['org_id', 'slug']);
        });

        // Add foreign key safely - check if orgs table exists
        DB::statement("
            DO \$\$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.table_constraints
                    WHERE constraint_name = 'queue_slot_labels_org_id_foreign'
                    AND table_name = 'queue_slot_labels'
                ) THEN
                    IF EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'cmis' AND table_name = 'orgs') THEN
                        ALTER TABLE cmis.queue_slot_labels
                        ADD CONSTRAINT queue_slot_labels_org_id_foreign
                        FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id) ON DELETE CASCADE;
                    END IF;
                END IF;
            END \$\$;
        ");

        // Enable Row-Level Security for multi-tenancy
        $this->enableRLS('cmis.queue_slot_labels');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.queue_slot_labels');
        Schema::dropIfExists('cmis.queue_slot_labels');
    }
};
