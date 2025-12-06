<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop if exists for fresh migrations
        Schema::dropIfExists('cmis.global_settings');

        Schema::create('cmis.global_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->string('group', 50)->default('general'); // general, security, email, api, appearance
            $table->text('value')->nullable();
            $table->string('type', 20)->default('string'); // string, boolean, integer, json, text
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false); // Can be accessed without auth
            $table->boolean('is_encrypted')->default(false); // Value is encrypted
            $table->json('validation_rules')->nullable(); // JSON validation rules
            $table->json('options')->nullable(); // For select type settings
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('group');
            $table->index(['group', 'sort_order']);
        });

        // Seed default settings
        $settings = [
            // General Settings
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'site_name',
                'group' => 'general',
                'value' => 'CMIS Platform',
                'type' => 'string',
                'label' => 'Site Name',
                'description' => 'The name of your platform',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'site_tagline',
                'group' => 'general',
                'value' => 'Cognitive Marketing Intelligence Suite',
                'type' => 'string',
                'label' => 'Site Tagline',
                'description' => 'A short description of your platform',
                'is_public' => true,
                'sort_order' => 2,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'default_locale',
                'group' => 'general',
                'value' => 'ar',
                'type' => 'string',
                'label' => 'Default Language',
                'description' => 'Default language for new users',
                'is_public' => true,
                'options' => json_encode(['ar' => 'Arabic', 'en' => 'English']),
                'sort_order' => 3,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'default_timezone',
                'group' => 'general',
                'value' => 'Asia/Riyadh',
                'type' => 'string',
                'label' => 'Default Timezone',
                'description' => 'Default timezone for new organizations',
                'is_public' => false,
                'sort_order' => 4,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'maintenance_mode',
                'group' => 'general',
                'value' => 'false',
                'type' => 'boolean',
                'label' => 'Maintenance Mode',
                'description' => 'Enable maintenance mode to prevent user access',
                'is_public' => true,
                'sort_order' => 5,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'maintenance_message',
                'group' => 'general',
                'value' => 'We are currently performing maintenance. Please check back soon.',
                'type' => 'text',
                'label' => 'Maintenance Message',
                'description' => 'Message to display during maintenance',
                'is_public' => true,
                'sort_order' => 6,
            ],

            // Registration Settings
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'registration_enabled',
                'group' => 'registration',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Allow Registration',
                'description' => 'Allow new users to register',
                'is_public' => true,
                'sort_order' => 1,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'email_verification_required',
                'group' => 'registration',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Require Email Verification',
                'description' => 'Require users to verify their email address',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'default_plan_id',
                'group' => 'registration',
                'value' => null,
                'type' => 'string',
                'label' => 'Default Plan',
                'description' => 'Default plan for new organizations',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'trial_days',
                'group' => 'registration',
                'value' => '14',
                'type' => 'integer',
                'label' => 'Trial Period (Days)',
                'description' => 'Number of trial days for new subscriptions',
                'is_public' => false,
                'sort_order' => 4,
            ],

            // Security Settings
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'password_min_length',
                'group' => 'security',
                'value' => '8',
                'type' => 'integer',
                'label' => 'Minimum Password Length',
                'description' => 'Minimum number of characters for passwords',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'password_require_uppercase',
                'group' => 'security',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Require Uppercase',
                'description' => 'Require at least one uppercase letter',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'password_require_number',
                'group' => 'security',
                'value' => 'true',
                'type' => 'boolean',
                'label' => 'Require Number',
                'description' => 'Require at least one number',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'session_lifetime',
                'group' => 'security',
                'value' => '120',
                'type' => 'integer',
                'label' => 'Session Lifetime (Minutes)',
                'description' => 'How long before inactive sessions expire',
                'is_public' => false,
                'sort_order' => 4,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'max_login_attempts',
                'group' => 'security',
                'value' => '5',
                'type' => 'integer',
                'label' => 'Max Login Attempts',
                'description' => 'Maximum login attempts before lockout',
                'is_public' => false,
                'sort_order' => 5,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'lockout_duration',
                'group' => 'security',
                'value' => '15',
                'type' => 'integer',
                'label' => 'Lockout Duration (Minutes)',
                'description' => 'How long a user is locked out after max attempts',
                'is_public' => false,
                'sort_order' => 6,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'two_factor_required',
                'group' => 'security',
                'value' => 'false',
                'type' => 'boolean',
                'label' => 'Require Two-Factor Authentication',
                'description' => 'Require 2FA for all users',
                'is_public' => false,
                'sort_order' => 7,
            ],

            // API Settings
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'api_rate_limit',
                'group' => 'api',
                'value' => '60',
                'type' => 'integer',
                'label' => 'API Rate Limit (per minute)',
                'description' => 'Maximum API requests per minute per user',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'api_burst_limit',
                'group' => 'api',
                'value' => '10',
                'type' => 'integer',
                'label' => 'API Burst Limit',
                'description' => 'Maximum burst requests allowed',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'ai_rate_limit',
                'group' => 'api',
                'value' => '30',
                'type' => 'integer',
                'label' => 'AI Rate Limit (per minute)',
                'description' => 'Maximum AI requests per minute',
                'is_public' => false,
                'sort_order' => 3,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'ai_hourly_limit',
                'group' => 'api',
                'value' => '500',
                'type' => 'integer',
                'label' => 'AI Hourly Limit',
                'description' => 'Maximum AI requests per hour',
                'is_public' => false,
                'sort_order' => 4,
            ],

            // Email Settings
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'mail_from_name',
                'group' => 'email',
                'value' => 'CMIS Platform',
                'type' => 'string',
                'label' => 'From Name',
                'description' => 'Name that appears in email "From" field',
                'is_public' => false,
                'sort_order' => 1,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'mail_from_address',
                'group' => 'email',
                'value' => 'noreply@cmis.test',
                'type' => 'string',
                'label' => 'From Address',
                'description' => 'Email address that appears in "From" field',
                'is_public' => false,
                'sort_order' => 2,
            ],
            [
                'id' => \Illuminate\Support\Str::uuid(),
                'key' => 'support_email',
                'group' => 'email',
                'value' => 'support@cmis.test',
                'type' => 'string',
                'label' => 'Support Email',
                'description' => 'Email address for support inquiries',
                'is_public' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($settings as $setting) {
            $setting['created_at'] = now();
            $setting['updated_at'] = now();
            DB::table('cmis.global_settings')->insert($setting);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cmis.global_settings');
    }
};
