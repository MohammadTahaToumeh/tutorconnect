<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->string('paypal_merchant_id', 64)->nullable()->after('is_active');
            $table->string('paypal_tracking_id', 128)->nullable()->after('paypal_merchant_id');
            $table->text('paypal_referral_id')->nullable()->after('paypal_tracking_id');
            $table->string('paypal_onboarding_status', 32)->default('not_connected')->after('paypal_referral_id');
            $table->boolean('paypal_payments_receivable')->default(false)->after('paypal_onboarding_status');
            $table->boolean('paypal_email_confirmed')->default(false)->after('paypal_payments_receivable');
            $table->timestamp('paypal_connected_at')->nullable()->after('paypal_email_confirmed');

            $table->index('paypal_merchant_id');
            $table->index('paypal_tracking_id');
        });
    }

    public function down(): void
    {
        Schema::table('tutor_profiles', function (Blueprint $table): void {
            $table->dropIndex(['paypal_merchant_id']);
            $table->dropIndex(['paypal_tracking_id']);
            $table->dropColumn([
                'paypal_merchant_id',
                'paypal_tracking_id',
                'paypal_referral_id',
                'paypal_onboarding_status',
                'paypal_payments_receivable',
                'paypal_email_confirmed',
                'paypal_connected_at',
            ]);
        });
    }
};
