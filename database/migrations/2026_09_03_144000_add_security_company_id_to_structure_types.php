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
        Schema::table('structure_types', function (Blueprint $table) {
            $table->foreignId('security_company_id')
                ->nullable()
                ->after('id')
                ->constrained('security_companies')
                ->restrictOnDelete();
            $table->dropUnique(['code']);
        });

        $this->assignExistingTypesToCompanies();

        Schema::table('structure_types', function (Blueprint $table) {
            $table->unique(['security_company_id', 'code']);
        });

        DB::statement('ALTER TABLE structure_types MODIFY security_company_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('structure_types', function (Blueprint $table) {
            $table->dropUnique(['security_company_id', 'code']);
        });

        DB::statement('ALTER TABLE structure_types MODIFY security_company_id BIGINT UNSIGNED NULL');

        Schema::table('structure_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('security_company_id');
            $table->unique('code');
        });
    }

    private function assignExistingTypesToCompanies(): void
    {
        $types = DB::table('structure_types')->orderBy('id')->get();
        if ($types->isEmpty()) {
            return;
        }

        $companies = DB::table('security_companies')
            ->when(Schema::hasColumn('security_companies', 'deleted_at'), fn ($q) => $q->whereNull('deleted_at'))
            ->orderBy('id')
            ->pluck('id');

        if ($companies->isEmpty()) {
            DB::table('structure_types')->delete();

            return;
        }

        $firstCompanyId = (int) $companies->first();
        DB::table('structure_types')->update(['security_company_id' => $firstCompanyId]);

        $originals = DB::table('structure_types')->orderBy('id')->get();

        foreach ($companies->slice(1) as $companyId) {
            $idMap = [];
            foreach ($originals as $type) {
                $newId = DB::table('structure_types')->insertGetId([
                    'security_company_id' => (int) $companyId,
                    'code' => $type->code,
                    'name' => $type->name,
                    'description' => $type->description,
                    'is_unit' => $type->is_unit,
                    'is_active' => $type->is_active,
                    'sort_order' => $type->sort_order,
                    'created_at' => $type->created_at,
                    'updated_at' => now(),
                ]);
                $idMap[(int) $type->id] = $newId;
            }

            $clientIds = DB::table('clients')
                ->where('security_company_id', (int) $companyId)
                ->pluck('id');

            foreach ($idMap as $oldId => $newId) {
                DB::table('clients')
                    ->where('security_company_id', (int) $companyId)
                    ->where('structure_type_id', $oldId)
                    ->update(['structure_type_id' => $newId]);

                if ($clientIds->isNotEmpty()) {
                    DB::table('structures')
                        ->whereIn('client_id', $clientIds)
                        ->where('structure_type_id', $oldId)
                        ->update(['structure_type_id' => $newId]);
                }
            }
        }
    }
};
