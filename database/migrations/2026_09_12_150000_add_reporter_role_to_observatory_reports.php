<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('observatory_reports', function (Blueprint $table): void {
            $table->string('reporter_role', 20)->nullable()->after('source');
            $table->foreignId('reported_by_user_id')
                ->nullable()
                ->after('reporter_phone')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('observatory_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('reported_by_user_id');
            $table->dropColumn('reporter_role');
        });
    }
};
