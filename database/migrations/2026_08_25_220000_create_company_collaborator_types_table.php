<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_collaborator_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['security_company_id', 'name']);
            $table->index(['security_company_id', 'is_active']);
        });

        $now = now();
        $companies = DB::table('security_companies')->pluck('id');
        foreach ($companies as $companyId) {
            $names = DB::table('employees')
                ->where('security_company_id', $companyId)
                ->whereNotNull('collaborator_type')
                ->distinct()
                ->pluck('collaborator_type')
                ->map(fn ($name) => trim((string) $name))
                ->filter()
                ->unique(fn (string $name) => mb_strtolower($name))
                ->values();

            $sort = 0;
            foreach ($names as $name) {
                DB::table('company_collaborator_types')->insert([
                    'security_company_id' => $companyId,
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $sort += 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('collaborator_type_id')
                ->nullable()
                ->after('birth_date')
                ->constrained('company_collaborator_types')
                ->restrictOnDelete();
        });

        foreach (DB::table('employees')->select('id', 'security_company_id', 'collaborator_type')->get() as $employee) {
            $typeId = DB::table('company_collaborator_types')
                ->where('security_company_id', $employee->security_company_id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $employee->collaborator_type)])
                ->value('id');

            if ($typeId === null) {
                $typeId = DB::table('company_collaborator_types')->insertGetId([
                    'security_company_id' => $employee->security_company_id,
                    'name' => trim((string) $employee->collaborator_type) !== ''
                        ? trim((string) $employee->collaborator_type)
                        : 'OPERATIVO',
                    'is_active' => true,
                    'sort_order' => 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('employees')->where('id', $employee->id)->update([
                'collaborator_type_id' => $typeId,
            ]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('collaborator_type');
        });

        DB::statement('ALTER TABLE employees MODIFY collaborator_type_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('collaborator_type', 20)->nullable()->after('birth_date');
        });

        foreach (DB::table('employees')->select('id', 'collaborator_type_id')->get() as $employee) {
            $name = DB::table('company_collaborator_types')->where('id', $employee->collaborator_type_id)->value('name');
            DB::table('employees')->where('id', $employee->id)->update([
                'collaborator_type' => $name ?: 'OPERATIVO',
            ]);
        }

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('collaborator_type_id');
        });

        DB::statement('ALTER TABLE employees MODIFY collaborator_type VARCHAR(20) NOT NULL');

        Schema::dropIfExists('company_collaborator_types');
    }
};
