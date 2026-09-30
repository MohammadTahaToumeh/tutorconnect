<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutor_availabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tutor_profile_id')
                ->constrained('tutor_profiles')
                ->cascadeOnDelete();

            $table->string('weekday');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('mode')->default('both');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutor_availabilities');
    }
};