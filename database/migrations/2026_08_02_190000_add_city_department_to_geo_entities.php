<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_companies', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->after('address');
            $table->string('department', 120)->nullable()->after('city');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->after('address');
            $table->string('department', 120)->nullable()->after('city');
        });

        Schema::table('commercial_signup_intents', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->after('address');
            $table->string('department', 120)->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('security_companies', function (Blueprint $table) {
            $table->dropColumn(['city', 'department']);
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['city', 'department']);
        });

        Schema::table('commercial_signup_intents', function (Blueprint $table) {
            $table->dropColumn(['city', 'department']);
        });
    }
};
