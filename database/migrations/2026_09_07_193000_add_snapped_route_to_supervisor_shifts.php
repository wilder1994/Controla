<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->json('snapped_route')->nullable()->after('close_client_event_id');
            $table->string('snapped_route_hash', 64)->nullable()->after('snapped_route');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->dropColumn(['snapped_route', 'snapped_route_hash']);
        });
    }
};
