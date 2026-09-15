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
        Schema::create('supervisor_post_modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('hours');
            $table->string('name', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['security_company_id', 'hours']);
            $table->index(['security_company_id', 'is_active', 'sort_order'], 'spm_company_active_sort_idx');
        });

        $now = now();
        $companyIds = DB::table('security_companies')->pluck('id');
        foreach ($companyIds as $companyId) {
            foreach ([8 => 10, 12 => 20, 24 => 30] as $hours => $order) {
                DB::table('supervisor_post_modalities')->insert([
                    'security_company_id' => $companyId,
                    'hours' => $hours,
                    'name' => null,
                    'is_active' => true,
                    'sort_order' => $order,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_post_modalities');
    }
};
