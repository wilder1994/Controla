<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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

        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->foreignId('supervisor_fleet_vehicle_id')
                ->nullable()
                ->after('user_id')
                ->constrained('supervisor_fleet_vehicles')
                ->nullOnDelete();
            $table->string('shift_slot', 20)->nullable()->after('status');
            $table->string('schedule_label', 40)->nullable()->after('shift_slot');
            $table->string('route_zone', 80)->nullable()->after('schedule_label');
            $table->string('km_start_selfie_path')->nullable()->after('km_start_photo_path');
            $table->string('km_end_selfie_path')->nullable()->after('km_end_photo_path');
            $table->json('ppe_checklist')->nullable()->after('notes');
            $table->json('vehicle_checklist')->nullable()->after('ppe_checklist');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_shifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_fleet_vehicle_id');
            $table->dropColumn([
                'shift_slot',
                'schedule_label',
                'route_zone',
                'km_start_selfie_path',
                'km_end_selfie_path',
                'ppe_checklist',
                'vehicle_checklist',
            ]);
        });

        Schema::dropIfExists('supervisor_fleet_vehicles');
    }
};
