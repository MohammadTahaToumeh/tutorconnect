<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_profile_payment_method', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tutor_profile_id')
                ->constrained('tutor_profiles')
                ->cascadeOnDelete();

            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['tutor_profile_id', 'payment_method_id'],
                'tutor_payment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_profile_payment_method');
    }
};