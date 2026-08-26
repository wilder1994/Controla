<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('open');
            $table->unsignedInteger('km_start')->nullable();
            $table->unsignedInteger('km_end')->nullable();
            $table->unsignedInteger('km_traveled')->nullable();
            $table->string('km_start_photo_path')->nullable();
            $table->string('km_end_photo_path')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['security_company_id', 'status']);
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('supervisor_shift_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_shift_id')->constrained('supervisor_shifts')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('source', 20)->default('app');
            $table->timestamps();

            $table->index(['supervisor_shift_id', 'recorded_at']);
        });

        Schema::create('supervisor_shift_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_shift_id')->constrained('supervisor_shifts')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('guard_log_id')->nullable()->constrained('guard_logs')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_shift_reviews');
        Schema::dropIfExists('supervisor_shift_locations');
        Schema::dropIfExists('supervisor_shifts');
    }
};
