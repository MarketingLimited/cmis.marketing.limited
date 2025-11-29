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
        Schema::table('cmis.users', function (Blueprint $table) {
            // Add bio column if it doesn't exist
            if (!Schema::hasColumn('cmis.users', 'bio')) {
                $table->text('bio')->nullable()->after('email');
            }

            // Add avatar column if it doesn't exist
            if (!Schema::hasColumn('cmis.users', 'avatar')) {
                $table->string('avatar')->nullable()->after('bio');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cmis.users', function (Blueprint $table) {
            if (Schema::hasColumn('cmis.users', 'bio')) {
                $table->dropColumn('bio');
            }

            if (Schema::hasColumn('cmis.users', 'avatar')) {
                $table->dropColumn('avatar');
            }
        });
    }
};
