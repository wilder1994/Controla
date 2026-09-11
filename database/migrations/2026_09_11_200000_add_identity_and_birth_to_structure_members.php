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
        Schema::table('structure_members', function (Blueprint $table): void {
            $table->string('document_type', 20)->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('document_number');
            $table->timestamp('minor_treatment_accepted_at')->nullable()->after('birth_date');
        });

        DB::table('structure_members')
            ->whereNull('document_type')
            ->update(['document_type' => 'CC']);

        $now = now();
        $types = [
            ['code' => 'RC', 'name' => 'Registro civil', 'sort_order' => 5],
            ['code' => 'TI', 'name' => 'Tarjeta de identidad', 'sort_order' => 8],
        ];
        foreach ($types as $type) {
            $exists = DB::table('identity_document_types')->where('code', $type['code'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('identity_document_types')->insert([
                'code' => $type['code'],
                'name' => $type['name'],
                'is_active' => true,
                'sort_order' => $type['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('structure_members', function (Blueprint $table): void {
            $table->dropColumn(['document_type', 'birth_date', 'minor_treatment_accepted_at']);
        });
    }
};
