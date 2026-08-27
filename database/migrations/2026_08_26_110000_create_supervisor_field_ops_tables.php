<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('opened_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('opened_shift_id')->nullable()->constrained('supervisor_shifts')->nullOnDelete();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_shift_id')->nullable()->constrained('supervisor_shifts')->nullOnDelete();
            $table->string('status', 20);
            $table->string('priority', 20);
            $table->date('due_date')->nullable();
            $table->string('title', 120);
            $table->text('body');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['security_company_id', 'status']);
            $table->index(['client_id', 'status']);
            $table->index('due_date');
        });

        Schema::create('supervisor_field_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_shift_id')->constrained('supervisor_shifts')->cascadeOnDelete();
            $table->foreignId('supervisor_shift_review_id')
                ->nullable()
                ->constrained('supervisor_shift_reviews')
                ->nullOnDelete();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('supervisor_recommendation_id')->nullable()->constrained('supervisor_recommendations')->nullOnDelete();
            $table->string('module', 40);
            $table->string('outcome', 20);
            $table->json('payload');
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['security_company_id', 'recorded_at']);
            $table->index(['supervisor_shift_id', 'module']);
            $table->index(['module', 'outcome']);
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_field_logs');
        Schema::dropIfExists('supervisor_recommendations');
    }
};
