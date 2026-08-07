<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('resident_id')->nullable()->index();
            $table->unsignedBigInteger('housing_unit_id')->nullable()->index();
            $table->foreignId('vehicle_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('access_type', 20)->default('visitor');
            $table->foreignId('authorized_by')->constrained('users')->cascadeOnDelete();
            $table->dateTime('entry_time');
            $table->dateTime('exit_time')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('purpose', 255)->nullable();
            $table->string('company_visited', 150)->nullable();
            $table->decimal('screening_temp', 4, 1)->nullable();
            $table->string('qr_code', 100)->unique()->nullable();
            $table->text('notes')->nullable();
            $table->boolean('has_custody')->default(false);
            $table->text('custody_description')->nullable();
            $table->string('custody_receiver_name')->nullable();
            $table->dateTime('custody_received_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'entry_time']);
            $table->index(['visitor_id', 'status']);
            $table->index(['host_id', 'entry_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
