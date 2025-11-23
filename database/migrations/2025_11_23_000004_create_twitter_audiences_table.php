<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Database\Migrations\Concerns\HasRLSPolicies;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('cmis_twitter.audiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id')->index();

            // Twitter platform identifiers
            $table->string('platform_audience_id')->unique()->index();
            $table->string('platform_account_id')->index();

            // Audience details
            $table->string('name');
            $table->string('audience_type'); // TAILORED, LOOKALIKE, FOLLOWER_LOOKALIKE
            $table->string('list_type')->nullable(); // EMAIL, TWITTER_ID, MOBILE_ADVERTISING_ID, etc.

            // Audience size estimates
            $table->bigInteger('size_estimate')->nullable();
            $table->bigInteger('targetable_size')->nullable();

            // Status
            $table->string('status')->default('ACTIVE'); // ACTIVE, BUILDING, READY, TOO_SMALL, DELETED

            // Source audience (for lookalike audiences)
            $table->uuid('source_audience_id')->nullable();
            $table->string('source_country')->nullable(); // For lookalike geo-targeting

            // Metadata (JSONB for flexibility)
            $table->jsonb('config_metadata')->nullable();
            $table->jsonb('platform_metadata')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('org_id')
                ->references('org_id')
                ->on('cmis.organizations')
                ->onDelete('cascade');

            $table->foreign('source_audience_id')
                ->references('id')
                ->on('cmis_twitter.audiences')
                ->onDelete('set null');

            // Indexes
            $table->index('audience_type');
            $table->index('status');
            $table->index('list_type');
        });

        // Enable RLS
        $this->enableRLS('cmis_twitter.audiences');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->disableRLS('cmis_twitter.audiences');
        Schema::dropIfExists('cmis_twitter.audiences');
    }
};
