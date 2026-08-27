<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['security_company_id', 'name']);
        });

        Schema::create('supervisor_shift_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('starts_at', 5);
            $table->string('ends_at', 5);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['security_company_id', 'name']);
        });

        Schema::create('supervisor_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20);
            $table->string('item_key', 80);
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['security_company_id', 'kind', 'item_key'], 'sup_check_company_kind_key_uidx');
        });

        Schema::create('supervisor_fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('plate', 12);
            $table->string('brand', 80);
            $table->string('line', 80)->nullable();
            $table->string('model', 40)->nullable();
            $table->string('color', 40)->nullable();
            $table->string('type', 40)->nullable();
            $table->date('soat_expires_at')->nullable();
            $table->date('technical_review_expires_at')->nullable();
            $table->unsignedInteger('last_km')->default(0);
            $table->timestamps();

            $table->unique(['security_company_id', 'plate']);
        });

        Schema::create('supervisor_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supervisor_fleet_vehicle_id')->nullable()->constrained('supervisor_fleet_vehicles')->nullOnDelete();
            $table->foreignId('supervisor_zone_id')->nullable()->constrained('supervisor_zones')->nullOnDelete();
            $table->foreignId('supervisor_shift_template_id')->nullable()->constrained('supervisor_shift_templates')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->string('shift_slot', 20)->nullable();
            $table->string('schedule_label', 40)->nullable();
            $table->string('route_zone', 80)->nullable();
            $table->unsignedInteger('km_start')->nullable();
            $table->unsignedInteger('km_end')->nullable();
            $table->unsignedInteger('km_traveled')->nullable();
            $table->string('km_start_photo_path')->nullable();
            $table->string('km_start_selfie_path')->nullable();
            $table->string('km_end_photo_path')->nullable();
            $table->string('km_end_selfie_path')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('ppe_checklist')->nullable();
            $table->json('vehicle_checklist')->nullable();
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

        Schema::create('supervisor_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('installation_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['installation_id', 'name']);
        });

        Schema::create('supervisor_shift_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supervisor_shift_id')->constrained('supervisor_shifts')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('supervisor_post_id')->constrained('supervisor_posts')->restrictOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('guard_log_id')->nullable()->constrained('guard_logs')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('has_novelty')->default(false);
            $table->string('guard_photo_path', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_shift_reviews');
        Schema::dropIfExists('supervisor_posts');
        Schema::dropIfExists('supervisor_shift_locations');
        Schema::dropIfExists('supervisor_shifts');
        Schema::dropIfExists('supervisor_fleet_vehicles');
        Schema::dropIfExists('supervisor_checklist_items');
        Schema::dropIfExists('supervisor_shift_templates');
        Schema::dropIfExists('supervisor_zones');
    }
};
