<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration for Unified Inbox (Sprint 5.2)
 * Centralizes comments, messages, and mentions from all platforms
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if table already exists
        if (Schema::hasTable('cmis.inbox_items')) {
            return;
        }

        // Skip if required tables don't exist yet (migration ordering)
        if (!Schema::hasTable('cmis.orgs') || !Schema::hasTable('cmis.users')) {
            return;
        }

        Schema::connection('pgsql')->create('cmis.inbox_items', function (Blueprint $table) {
            $table->uuid('item_id')->primary();
            $table->uuid('org_id')->index();
            $table->uuid('social_account_id')->index();

            // Item details
            $table->string('item_type', 50); // comment, message, mention
            $table->string('platform', 50); // meta, google, linkedin, x, tiktok, snapchat
            $table->string('external_id', 255)->nullable(); // Platform's ID for this item
            $table->text('content');

            // Sender information
            $table->string('sender_name', 255);
            $table->string('sender_id', 255)->nullable(); // Platform's user ID
            $table->string('sender_avatar_url', 500)->nullable();

            // Response management
            $table->boolean('needs_reply')->default(true);
            $table->uuid('assigned_to')->nullable()->index();
            $table->string('status', 20)->default('unread'); // unread, replied, archived
            $table->text('reply_content')->nullable();
            $table->timestamp('replied_at')->nullable();

            // Sentiment analysis (optional for Sprint 5.3)
            $table->string('sentiment', 20)->nullable(); // positive, neutral, negative
            $table->decimal('sentiment_score', 3, 2)->nullable(); // 0.00 to 1.00

            // Timestamps
            $table->timestamp('platform_created_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Foreign keys
            $table->foreign('org_id')->references('org_id')->on('cmis.orgs')->onDelete('cascade');
            $table->foreign('social_account_id')->references('id')->on('cmis.social_accounts')->onDelete('cascade');
            $table->foreign('assigned_to')->references('user_id')->on('cmis.users')->onDelete('set null');

            // Indexes
            $table->index(['org_id', 'status'], 'inbox_items_org_status_idx');
            $table->index(['assigned_to', 'status'], 'inbox_items_assignee_status_idx');
            $table->index(['platform', 'external_id'], 'inbox_items_platform_external_idx');
            $table->index(['needs_reply', 'status'], 'inbox_items_needs_reply_idx');
        });

        DB::statement("COMMENT ON TABLE cmis.inbox_items IS 'Unified inbox for comments, messages, and mentions from all platforms'");
        DB::statement("COMMENT ON COLUMN cmis.inbox_items.item_type IS 'comment, message, or mention'");
        DB::statement("COMMENT ON COLUMN cmis.inbox_items.status IS 'unread, replied, or archived'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('cmis.inbox_items');
    }
};
