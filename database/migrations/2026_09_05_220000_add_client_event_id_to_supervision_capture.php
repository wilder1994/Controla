<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_shift_reviews', function (Blueprint $table) {
            $table->uuid('client_event_id')->nullable()->after('id');
            $table->unique('client_event_id');
        });

        Schema::table('supervisor_field_logs', function (Blueprint $table) {
            $table->uuid('client_event_id')->nullable()->after('id');
            $table->unique('client_event_id');
        });

        Schema::table('supervisor_shift_locations', function (Blueprint $table) {
            $table->uuid('client_event_id')->nullable()->after('id');
            $table->unique('client_event_id');
        });

        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->uuid('close_client_event_id')->nullable()->after('ended_at');
            $table->unique('close_client_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_shift_reviews', function (Blueprint $table) {
            $table->dropUnique(['client_event_id']);
            $table->dropColumn('client_event_id');
        });
        Schema::table('supervisor_field_logs', function (Blueprint $table) {
            $table->dropUnique(['client_event_id']);
            $table->dropColumn('client_event_id');
        });
        Schema::table('supervisor_shift_locations', function (Blueprint $table) {
            $table->dropUnique(['client_event_id']);
            $table->dropColumn('client_event_id');
        });
        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->dropUnique(['close_client_event_id']);
            $table->dropColumn('close_client_event_id');
        });
    }
};
