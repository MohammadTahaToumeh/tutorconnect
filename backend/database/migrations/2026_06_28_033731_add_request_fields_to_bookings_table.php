<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('payment_method_id')
                ->nullable()
                ->after('subject_id')
                ->constrained('payment_methods')
                ->nullOnDelete();

            $table->string('request_type')
                ->default('paid')
                ->after('mode');

            $table->text('exchange_offer')
                ->nullable()
                ->after('request_type');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn([
                'request_type',
                'exchange_offer',
            ]);
        });
    }
};