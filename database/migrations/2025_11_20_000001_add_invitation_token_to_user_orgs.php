<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cmis.user_orgs', function (Blueprint $table) {
            $table->string('invitation_token', 64)->nullable()->after('invited_by');
            $table->timestamp('invitation_accepted_at')->nullable()->after('invitation_token');
            $table->timestamp('invitation_expires_at')->nullable()->after('invitation_accepted_at');
        });

        // Add index for faster token lookups
        Schema::table('cmis.user_orgs', function (Blueprint $table) {
            $table->index('invitation_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cmis.user_orgs', function (Blueprint $table) {
            $table->dropIndex(['invitation_token']);
            $table->dropColumn(['invitation_token', 'invitation_accepted_at', 'invitation_expires_at']);
        });
    }
};
