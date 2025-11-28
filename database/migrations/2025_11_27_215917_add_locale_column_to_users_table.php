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
            // Add locale column if it doesn't exist
            if (!Schema::hasColumn('cmis.users', 'locale')) {
                $table->string('locale', 5)->default('ar')->after('email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cmis.users', function (Blueprint $table) {
            if (Schema::hasColumn('cmis.users', 'locale')) {
                $table->dropColumn('locale');
            }
        });
    }
};
