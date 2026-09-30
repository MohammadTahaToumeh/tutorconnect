<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('vorname')->nullable()->after('id');
            $table->string('nachname')->nullable()->after('vorname');
            $table->string('studiengang')->nullable()->after('email');
            $table->string('semester')->nullable()->after('studiengang');
            $table->string('rolle')->nullable()->after('semester');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'vorname',
                'nachname',
                'studiengang',
                'semester',
                'rolle',
            ]);
        });
    }
};