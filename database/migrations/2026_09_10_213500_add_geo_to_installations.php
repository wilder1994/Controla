<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('is_active');
            $table->string('city', 120)->nullable()->after('address');
            $table->string('department', 120)->nullable()->after('city');
            $table->decimal('latitude', 10, 7)->nullable()->after('department');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn(['address', 'city', 'department', 'latitude', 'longitude']);
        });
    }
};
