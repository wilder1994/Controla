<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guard_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('supervision_code_id')->nullable()->after('resolved_at');
            $table->string('supervisor_name', 255)->nullable()->after('supervision_code_id');

            $table->foreign('supervision_code_id')
                ->references('id')
                ->on('supervision_codes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('guard_logs', function (Blueprint $table) {
            $table->dropForeign(['supervision_code_id']);
            $table->dropColumn(['supervision_code_id', 'supervisor_name']);
        });
    }
};