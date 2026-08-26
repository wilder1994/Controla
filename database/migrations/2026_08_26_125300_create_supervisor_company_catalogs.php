<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supervisor_zones')) {
            Schema::create('supervisor_zones', function (Blueprint $table) {
                $table->id();
                $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
                $table->string('name', 80);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['security_company_id', 'name']);
            });
        }

        if (! Schema::hasTable('supervisor_shift_templates')) {
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
        }

        if (! Schema::hasTable('supervisor_checklist_items')) {
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
        } elseif (! $this->hasIndex('supervisor_checklist_items', 'sup_check_company_kind_key_uidx')) {
            Schema::table('supervisor_checklist_items', function (Blueprint $table) {
                $table->unique(['security_company_id', 'kind', 'item_key'], 'sup_check_company_kind_key_uidx');
            });
        }

        if (! Schema::hasColumn('supervisor_shifts', 'supervisor_zone_id')) {
            Schema::table('supervisor_shifts', function (Blueprint $table) {
                $table->foreignId('supervisor_zone_id')
                    ->nullable()
                    ->after('supervisor_fleet_vehicle_id')
                    ->constrained('supervisor_zones')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('supervisor_shifts', 'supervisor_shift_template_id')) {
            Schema::table('supervisor_shifts', function (Blueprint $table) {
                $table->foreignId('supervisor_shift_template_id')
                    ->nullable()
                    ->after('supervisor_zone_id')
                    ->constrained('supervisor_shift_templates')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('supervisor_shifts', function (Blueprint $table) {
            if (Schema::hasColumn('supervisor_shifts', 'supervisor_shift_template_id')) {
                $table->dropConstrainedForeignId('supervisor_shift_template_id');
            }
            if (Schema::hasColumn('supervisor_shifts', 'supervisor_zone_id')) {
                $table->dropConstrainedForeignId('supervisor_zone_id');
            }
        });
        Schema::dropIfExists('supervisor_checklist_items');
        Schema::dropIfExists('supervisor_shift_templates');
        Schema::dropIfExists('supervisor_zones');
    }

    private function hasIndex(string $table, string $name): bool
    {
        foreach (Schema::getIndexes($table) as $index) {
            if (($index['name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }
};
