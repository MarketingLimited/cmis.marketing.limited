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
        // Skip if table already exists from earlier migration
        if (!Schema::hasTable('cmis_twitter.pixels')) {
            Schema::create('cmis_twitter.pixels', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();

                // Twitter platform identifiers
                $table->string('platform_pixel_id')->unique()->index();
                $table->string('platform_account_id')->index();

                // Pixel details
                $table->string('name');
                $table->text('pixel_code')->nullable(); // JavaScript code snippet
                $table->string('category')->default('PURCHASE'); // PURCHASE, SIGNUP, ADD_TO_CART, etc.
                $table->string('status')->default('ACTIVE'); // ACTIVE, INACTIVE

                // Verification
                $table->boolean('is_verified')->default(false);
                $table->timestamp('verified_at')->nullable();

                // Configuration (JSONB for flexibility)
                $table->jsonb('config_metadata')->nullable();

                // Timestamps
                $table->timestamps();
                $table->softDeletes();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.organizations')
                    ->onDelete('cascade');

                // Indexes
                $table->index('status');
                $table->index('is_verified');
            });

            // Enable RLS
            $this->enableRLS('cmis_twitter.pixels');
        }

        // Pixel events table (skip if already exists)
        if (!Schema::hasTable('cmis_twitter.pixel_events')) {
            Schema::create('cmis_twitter.pixel_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('org_id')->index();
                $table->uuid('pixel_id')->index();

                // Event details
                $table->string('event_type'); // PURCHASE, SIGNUP, ADD_TO_CART, etc.
                $table->jsonb('event_data')->nullable(); // Event parameters (price, currency, items, etc.)
                $table->string('user_identifier')->nullable(); // Hashed user ID

                // Timestamps
                $table->timestamp('event_timestamp');
                $table->timestamps();

                // Foreign keys
                $table->foreign('org_id')
                    ->references('org_id')
                    ->on('cmis.organizations')
                    ->onDelete('cascade');

                $table->foreign('pixel_id')
                    ->references('id')
                    ->on('cmis_twitter.pixels')
                    ->onDelete('cascade');

                // Indexes
                $table->index('event_type');
                $table->index('event_timestamp');
            });

            // Enable RLS
            $this->enableRLS('cmis_twitter.pixel_events');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $this->disableRLS('cmis_twitter.pixel_events');
        $this->disableRLS('cmis_twitter.pixels');

        Schema::dropIfExists('cmis_twitter.pixel_events');
        Schema::dropIfExists('cmis_twitter.pixels');
    }
};
