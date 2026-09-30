<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->decimal('payment_amount', 10, 2)->default(0)->after('status');
            $table->string('payment_currency', 3)->default('EUR')->after('payment_amount');
            $table->string('payment_status', 32)->default('not_required')->after('payment_currency');
            $table->string('paypal_order_id', 64)->nullable()->after('payment_status');
            $table->string('paypal_capture_id', 64)->nullable()->after('paypal_order_id');
            $table->timestamp('paid_at')->nullable()->after('paypal_capture_id');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');

            $table->index('paypal_order_id');
            $table->index('paypal_capture_id');
        });

        $now = now();

        if (! DB::table('payment_methods')->whereRaw('LOWER(name) = ?', ['paypal'])->exists()) {
            DB::table('payment_methods')->insert([
                'name' => 'PayPal',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $paidBookings = DB::table('bookings')
            ->where('request_type', 'paid')
            ->get(['id', 'tutor_profile_id', 'start_time', 'end_time']);

        foreach ($paidBookings as $booking) {
            $pricePerHour = (float) DB::table('tutor_profiles')
                ->where('id', $booking->tutor_profile_id)
                ->value('price_per_hour');

            $minutes = 60;

            try {
                $start = Carbon::parse((string) $booking->start_time);
                $end = Carbon::parse((string) $booking->end_time);
                $minutes = max(1, $start->diffInMinutes($end));
            } catch (Throwable) {
                // Alte Testdaten ohne gültige Uhrzeit bleiben bei einer Stunde.
            }

            DB::table('bookings')->where('id', $booking->id)->update([
                'payment_amount' => round($pricePerHour * ($minutes / 60), 2),
                'payment_status' => 'unpaid',
                'payment_currency' => 'EUR',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['paypal_order_id']);
            $table->dropIndex(['paypal_capture_id']);
            $table->dropColumn([
                'payment_amount',
                'payment_currency',
                'payment_status',
                'paypal_order_id',
                'paypal_capture_id',
                'paid_at',
                'refunded_at',
            ]);
        });
    }
};
