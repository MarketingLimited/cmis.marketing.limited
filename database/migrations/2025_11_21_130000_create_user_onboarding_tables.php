<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates table for tracking user onboarding progress
     * to guide new users through essential features.
     *
     * @return void
     */
    public function up()
    {
        // 1. Create user onboarding progress table
        if (!Schema::hasTable('cmis.user_onboarding_progress')) { Schema::create('cmis.user_onboarding_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->unique();

            // Progress tracking
            $table->json('completed_steps')->default('[]')->comment('Array of completed step keys');
            $table->json('skipped_steps')->default('[]')->comment('Array of skipped step keys');

            // Status
            $table->boolean('is_completed')->default(false);
            $table->boolean('dismissed')->default(false)->comment('User dismissed onboarding');

            // Metadata
            $table->json('metadata')->nullable()->comment('Additional tracking data');

            // Timestamps
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')
                ->references('user_id')
                ->on('cmis.users')
                ->onDelete('cascade');

            // Indexes
            $table->index('is_completed');
            $table->index('dismissed');
            $table->index('started_at');
        });

        // 2. Enable Row-Level Security
        DB::statement("ALTER TABLE cmis.user_onboarding_progress ENABLE ROW LEVEL SECURITY");

        // Users can only see their own onboarding progress
        DB::statement("DROP POLICY IF EXISTS user_own_onboarding ON cmis.user_onboarding_progress");
        DB::statement("
            CREATE POLICY user_own_onboarding ON cmis.user_onboarding_progress
            USING (
                user_id = current_setting('app.current_user_id', true)::uuid
                OR current_setting('app.is_admin', true)::boolean = true
            )
        ");
        }

        // 3. Create onboarding tips table (optional contextual help)
        if (!Schema::hasTable('cmis.onboarding_tips')) { Schema::create('cmis.onboarding_tips', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('step_key')->comment('Associated onboarding step');
            $table->string('tip_key')->unique()->comment('Unique identifier for tip');

            $table->json('title')->comment('Multilingual title');
            $table->json('content')->comment('Multilingual content');

            $table->string('icon')->nullable();
            $table->string('type')->default('info')->comment('info, warning, success');
            $table->integer('order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index('step_key');
            $table->index('is_active');
        });
        }

        // No RLS for tips table (public read-only data)

        // 4. Insert default onboarding tips (only if not exists)
        if (DB::table('cmis.onboarding_tips')->count() === 0) {
        DB::table('cmis.onboarding_tips')->insert([
            [
                'id' => DB::raw('gen_random_uuid()'),
                'step_key' => 'connect_meta',
                'tip_key' => 'meta_connection_security',
                'title' => json_encode([
                    'en' => 'Your Data is Secure',
                    'ar' => 'بياناتك آمنة',
                ]),
                'content' => json_encode([
                    'en' => 'We use OAuth 2.0 to securely connect your Meta accounts. We never store your password.',
                    'ar' => 'نستخدم OAuth 2.0 لربط حساباتك بأمان. لا نقوم بتخزين كلمة المرور أبداً.',
                ]),
                'icon' => 'shield-check',
                'type' => 'success',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'step_key' => 'first_campaign',
                'tip_key' => 'ai_generation_tip',
                'title' => json_encode([
                    'en' => 'Try AI-Powered Creation',
                    'ar' => 'جرب الإنشاء بالذكاء الاصطناعي',
                ]),
                'content' => json_encode([
                    'en' => 'Let our AI assistant help you create compelling ad copy based on proven marketing principles.',
                    'ar' => 'دع مساعدنا الذكي يساعدك في إنشاء نص إعلاني مقنع بناءً على مبادئ تسويقية مثبتة.',
                ]),
                'icon' => 'sparkles',
                'type' => 'info',
                'order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => DB::raw('gen_random_uuid()'),
                'step_key' => 'first_campaign',
                'tip_key' => 'budget_recommendation',
                'title' => json_encode([
                    'en' => 'Start Small, Scale Fast',
                    'ar' => 'ابدأ صغيراً، وسّع بسرعة',
                ]),
                'content' => json_encode([
                    'en' => 'We recommend starting with a $5-10 daily budget to test your campaign, then scaling based on results.',
                    'ar' => 'نوصي بالبدء بميزانية يومية 5-10 دولار لاختبار حملتك، ثم التوسع بناءً على النتائج.',
                ]),
                'icon' => 'trending-up',
                'type' => 'info',
                'order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cmis.onboarding_tips');
        Schema::dropIfExists('cmis.user_onboarding_progress');
    }
};
