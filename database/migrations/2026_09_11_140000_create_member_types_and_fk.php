<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('slug', 50);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'name']);
            $table->unique(['client_id', 'slug']);
        });

        Schema::table('structure_members', function (Blueprint $table) {
            $table->foreignId('member_type_id')
                ->nullable()
                ->after('email')
                ->constrained('member_types')
                ->restrictOnDelete();
        });

        $this->backfillMemberTypes();
        $this->classifyOrphans();

        Schema::table('structure_members', function (Blueprint $table) {
            $table->dropColumn('member_type');
        });

        DB::statement('ALTER TABLE structure_members MODIFY member_type_id BIGINT UNSIGNED NOT NULL');

        Schema::table('structure_members', function (Blueprint $table) {
            $table->index(['structure_id', 'member_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('structure_members', function (Blueprint $table) {
            $table->dropIndex(['structure_id', 'member_type_id']);
            $table->string('member_type', 30)->nullable()->after('email');
        });

        $rows = DB::table('structure_members')
            ->join('member_types', 'member_types.id', '=', 'structure_members.member_type_id')
            ->select('structure_members.id', 'member_types.slug')
            ->get();

        foreach ($rows as $row) {
            DB::table('structure_members')
                ->where('id', $row->id)
                ->update(['member_type' => $row->slug]);
        }

        Schema::table('structure_members', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_type_id');
            $table->string('member_type', 30)->nullable(false)->change();
            $table->index(['structure_id', 'member_type']);
        });

        Schema::dropIfExists('member_types');
    }

    private function backfillMemberTypes(): void
    {
        $labels = [
            'owner' => 'Propietario',
            'tenant' => 'Arrendatario',
            'family_member' => 'Familiar',
            'temporary_guest' => 'Invitado permanente',
            'employee' => 'Empleado',
            'administrator' => 'Administrador',
        ];

        $clientIds = DB::table('structure_members')->distinct()->pluck('client_id');

        foreach ($clientIds as $clientId) {
            $codes = DB::table('structure_members')
                ->where('client_id', $clientId)
                ->whereNotNull('member_type')
                ->distinct()
                ->pluck('member_type');

            $sort = 10;
            $map = [];

            foreach ($codes as $code) {
                $name = $labels[$code] ?? Str::title(str_replace('_', ' ', (string) $code));
                $slug = Str::slug($name);
                if ($slug === '') {
                    $slug = 'tipo';
                }

                $existing = DB::table('member_types')
                    ->where('client_id', $clientId)
                    ->where(function ($q) use ($name, $slug) {
                        $q->where('name', $name)->orWhere('slug', $slug);
                    })
                    ->first();

                if ($existing !== null) {
                    $map[$code] = (int) $existing->id;
                    continue;
                }

                $id = DB::table('member_types')->insertGetId([
                    'client_id' => $clientId,
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                    'sort_order' => $sort,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $map[$code] = $id;
                $sort += 10;
            }

            foreach ($map as $code => $typeId) {
                DB::table('structure_members')
                    ->where('client_id', $clientId)
                    ->where('member_type', $code)
                    ->update(['member_type_id' => $typeId]);
            }
        }
    }

    private function classifyOrphans(): void
    {
        $orphans = DB::table('structure_members')->whereNull('member_type_id')->get();

        foreach ($orphans->groupBy('client_id') as $clientId => $rows) {
            $typeId = DB::table('member_types')
                ->where('client_id', $clientId)
                ->where('name', 'Sin clasificar')
                ->value('id');

            if ($typeId === null) {
                $typeId = DB::table('member_types')->insertGetId([
                    'client_id' => $clientId,
                    'name' => 'Sin clasificar',
                    'slug' => 'sin-clasificar',
                    'is_active' => true,
                    'sort_order' => 999,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('structure_members')
                ->whereIn('id', $rows->pluck('id'))
                ->update(['member_type_id' => $typeId]);
        }
    }
};
