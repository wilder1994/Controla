<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->string('recurrence', 20)->default('puntual')->after('scheduled_time');
            $table->date('valid_until')->nullable()->after('recurrence');
            $table->unsignedInteger('entries_per_day')->default(1)->after('valid_until');
        });
    }

    public function down(): void
    {
        Schema::table('pre_authorizations', function (Blueprint $table) {
            $table->dropColumn(['recurrence', 'valid_until', 'entries_per_day']);
        });
    }
};