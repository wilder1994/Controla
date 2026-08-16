<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guard_shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('start_notes')->nullable();
            $table->text('end_notes')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'ended_at']);
            $table->index(['user_id', 'ended_at']);
        });

        Schema::create('supervisor_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('guard_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guard_shift_id')->nullable()->constrained('guard_shifts')->nullOnDelete();
            $table->text('observations')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('reviewed_at');
            $table->timestamps();

            $table->index(['client_id', 'reviewed_at']);
            $table->index(['supervisor_id', 'reviewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_reviews');
        Schema::dropIfExists('guard_shifts');
    }
};
