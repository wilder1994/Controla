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
            $table->unsignedInteger('pending_outbox_count')->nullable()->after('notes');
            $table->boolean('closed_by_system')->default(false)->after('pending_outbox_count');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->dropColumn(['pending_outbox_count', 'closed_by_system']);
        });
    }
};
