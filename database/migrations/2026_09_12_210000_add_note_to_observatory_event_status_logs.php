<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observatory_event_status_logs', function (Blueprint $table): void {
            $table->text('note')->nullable()->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('observatory_event_status_logs', function (Blueprint $table): void {
            $table->dropColumn('note');
        });
    }
};
