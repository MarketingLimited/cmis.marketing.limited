<?php

use Database\Migrations\Concerns\HasRLSPolicies;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use HasRLSPolicies;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create security_events table for tracking login attempts, suspicious activity, etc.
        if (!Schema::hasTable('cmis.security_events')) {
            Schema::create('cmis.security_events', function (Blueprint $table) {
                $table->uuid('event_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->uuid('user_id')->nullable()->comment('User involved if identified');
                $table->uuid('org_id')->nullable()->comment('Organization context if applicable');
                $table->string('event_type', 50)->comment('login_success, login_failed, password_reset, suspicious_activity, etc.');
                $table->string('severity', 20)->default('info')->comment('info, warning, critical');
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->string('location_country', 100)->nullable();
                $table->string('location_city', 100)->nullable();
                $table->jsonb('details')->default('{}')->comment('Additional event details');
                $table->boolean('is_resolved')->default(false);
                $table->uuid('resolved_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->text('resolution_notes')->nullable();
                $table->timestamps();

                // Indexes
                $table->index('event_type', 'idx_security_events_type');
                $table->index('severity', 'idx_security_events_severity');
                $table->index('user_id', 'idx_security_events_user');
                $table->index('ip_address', 'idx_security_events_ip');
                $table->index('created_at', 'idx_security_events_created');
                $table->index(['event_type', 'created_at'], 'idx_security_events_type_date');

                // Foreign keys
                $table->foreign('user_id')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('set null');

                $table->foreign('resolved_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('set null');
            });

            // Enable RLS with public read for super admins
            $this->enablePublicRLS('cmis.security_events');

            DB::statement("COMMENT ON TABLE cmis.security_events IS 'Tracks security-related events including login attempts, suspicious activity, and security alerts'");
        }

        // Create ip_blacklist table for blocked IPs
        if (!Schema::hasTable('cmis.ip_blacklist')) {
            Schema::create('cmis.ip_blacklist', function (Blueprint $table) {
                $table->uuid('blacklist_id')->primary()->default(DB::raw('gen_random_uuid()'));
                $table->string('ip_address', 45)->unique();
                $table->string('reason', 255);
                $table->uuid('blocked_by');
                $table->timestamp('blocked_until')->nullable()->comment('Null means permanent');
                $table->timestamps();

                $table->foreign('blocked_by')
                    ->references('user_id')
                    ->on('cmis.users')
                    ->onDelete('cascade');
            });

            $this->enablePublicRLS('cmis.ip_blacklist');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.ip_blacklist');
        Schema::dropIfExists('cmis.ip_blacklist');

        $this->disableRLS('cmis.security_events');
        Schema::dropIfExists('cmis.security_events');
    }
};
