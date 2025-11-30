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
     * Creates Laravel queue tables in cmis schema with proper RLS policies.
     */
    public function up(): void
    {
        // Drop existing jobs tables if they exist (both schemas)
        Schema::dropIfExists('cmis.jobs');
        Schema::dropIfExists('public.jobs');
        Schema::dropIfExists('cmis.failed_jobs');
        Schema::dropIfExists('public.failed_jobs');
        Schema::dropIfExists('cmis.job_batches');
        Schema::dropIfExists('public.job_batches');

        // Create jobs table in cmis schema
        Schema::create('cmis.jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        // Create failed_jobs table in cmis schema
        Schema::create('cmis.failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // Create job_batches table in cmis schema (for batch jobs)
        Schema::create('cmis.job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        // Note: Queue tables are NOT multi-tenant, so no RLS policies needed
        // Jobs are org-scoped via the job payload, not the table itself
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.job_batches');
        Schema::dropIfExists('cmis.failed_jobs');
        Schema::dropIfExists('cmis.jobs');
    }
};
