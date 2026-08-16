<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervision_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['client_id', 'code']);
        });

        Schema::table('guard_logs', function (Blueprint $table) {
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
        });

        Schema::dropIfExists('supervision_codes');
    }
};