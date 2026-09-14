<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observatory_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('nuevo');
            $table->string('title', 160);
            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['installation_id', 'status']);
        });

        Schema::create('observatory_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('observatory_events')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20)->default('comunidad');
            $table->string('reporter_role', 20)->nullable();
            $table->string('kind', 20);
            $table->text('body');
            $table->boolean('is_anonymous')->default(false);
            $table->string('reporter_name', 120)->nullable();
            $table->string('reporter_phone', 30)->nullable();
            $table->foreignId('reported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photo_path', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });

        Schema::create('observatory_event_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_id')->constrained('observatory_events')->cascadeOnDelete();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observatory_event_status_logs');
        Schema::dropIfExists('observatory_reports');
        Schema::dropIfExists('observatory_events');
    }
};
