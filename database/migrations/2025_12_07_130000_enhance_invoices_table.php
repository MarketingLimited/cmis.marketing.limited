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
        Schema::table('cmis.invoices', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('cmis.invoices', 'subscription_id')) {
                $table->uuid('subscription_id')->nullable()->after('org_id');
            }
            if (!Schema::hasColumn('cmis.invoices', 'invoice_number')) {
                $table->string('invoice_number', 50)->nullable()->after('invoice_id');
            }
            if (!Schema::hasColumn('cmis.invoices', 'description')) {
                $table->text('description')->nullable()->after('status');
            }
            if (!Schema::hasColumn('cmis.invoices', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('cmis.invoices', 'metadata')) {
                $table->jsonb('metadata')->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('cmis.invoices', 'billing_period_start')) {
                $table->date('billing_period_start')->nullable()->after('description');
            }
            if (!Schema::hasColumn('cmis.invoices', 'billing_period_end')) {
                $table->date('billing_period_end')->nullable()->after('billing_period_start');
            }
            if (!Schema::hasColumn('cmis.invoices', 'tax_amount')) {
                $table->decimal('tax_amount', 10, 2)->nullable()->default(0)->after('amount');
            }
            if (!Schema::hasColumn('cmis.invoices', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->nullable()->default(0)->after('tax_amount');
            }
            if (!Schema::hasColumn('cmis.invoices', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->nullable()->after('discount_amount');
            }
            if (!Schema::hasColumn('cmis.invoices', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('paid_at');
            }
            if (!Schema::hasColumn('cmis.invoices', 'payment_reference')) {
                $table->string('payment_reference', 255)->nullable()->after('payment_method');
            }
        });

        // Add indexes
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_org_id ON cmis.invoices(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_subscription_id ON cmis.invoices(subscription_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_status ON cmis.invoices(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON cmis.invoices(invoice_number)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON cmis.invoices(due_date)');

        // Add foreign key if it doesn't exist
        DB::statement("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_constraint WHERE conname = 'fk_invoices_subscription'
                ) THEN
                    ALTER TABLE cmis.invoices
                    ADD CONSTRAINT fk_invoices_subscription
                    FOREIGN KEY (subscription_id) REFERENCES cmis.subscriptions(subscription_id)
                    ON DELETE SET NULL;
                END IF;
            END
            $$;
        ");

        // Create payments table for tracking individual payments
        Schema::create('cmis.payments', function (Blueprint $table) {
            $table->uuid('payment_id')->primary();
            $table->uuid('org_id');
            $table->uuid('invoice_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 50)->default('pending'); // pending, completed, failed, refunded
            $table->string('payment_method', 50)->nullable(); // credit_card, bank_transfer, paypal, etc.
            $table->string('payment_gateway', 50)->nullable(); // stripe, paypal, etc.
            $table->string('transaction_id', 255)->nullable();
            $table->string('gateway_response_code', 50)->nullable();
            $table->text('gateway_response_message')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Add indexes and foreign keys for payments
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_org_id ON cmis.payments(org_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_invoice_id ON cmis.payments(invoice_id)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_status ON cmis.payments(status)');
        DB::statement('CREATE INDEX IF NOT EXISTS idx_payments_paid_at ON cmis.payments(paid_at)');

        DB::statement("
            ALTER TABLE cmis.payments
            ADD CONSTRAINT fk_payments_invoice
            FOREIGN KEY (invoice_id) REFERENCES cmis.invoices(invoice_id)
            ON DELETE SET NULL
        ");

        DB::statement("
            ALTER TABLE cmis.payments
            ADD CONSTRAINT fk_payments_org
            FOREIGN KEY (org_id) REFERENCES cmis.orgs(org_id)
            ON DELETE CASCADE
        ");

        // Enable RLS on payments
        DB::statement('ALTER TABLE cmis.payments ENABLE ROW LEVEL SECURITY');
        DB::statement('ALTER TABLE cmis.payments FORCE ROW LEVEL SECURITY');

        DB::statement("
            CREATE POLICY payments_org_isolation ON cmis.payments
            FOR ALL
            USING (org_id::text = current_setting('app.current_org_id', true))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop payments table
        DB::statement('DROP POLICY IF EXISTS payments_org_isolation ON cmis.payments');
        Schema::dropIfExists('cmis.payments');

        // Remove added columns from invoices
        Schema::table('cmis.invoices', function (Blueprint $table) {
            $columns = [
                'subscription_id', 'invoice_number', 'description', 'paid_at',
                'metadata', 'billing_period_start', 'billing_period_end',
                'tax_amount', 'discount_amount', 'total_amount',
                'payment_method', 'payment_reference'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('cmis.invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
