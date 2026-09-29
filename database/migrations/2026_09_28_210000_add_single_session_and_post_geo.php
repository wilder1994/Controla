<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('single_session_token', 64)->nullable()->after('remember_token');
        });

        Schema::table('supervisor_posts', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('is_active');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('single_session_token');
        });

        Schema::table('supervisor_posts', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
