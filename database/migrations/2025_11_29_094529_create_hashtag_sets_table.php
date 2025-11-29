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
     */
    public function up(): void
    {
        Schema::create('cmis.hashtag_sets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('org_id');
            $table->string('name');
            $table->jsonb('hashtags'); // Array of hashtag strings
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('org_id');
            $table->index('name');
            $table->index('usage_count');
        });

        // Enable RLS
        $this->enableRLS('cmis.hashtag_sets');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->disableRLS('cmis.hashtag_sets');
        Schema::dropIfExists('cmis.hashtag_sets');
    }
};
