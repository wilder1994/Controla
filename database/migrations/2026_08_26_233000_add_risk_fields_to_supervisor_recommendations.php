<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_recommendations', function (Blueprint $table) {
            $table->text('risk')->nullable()->after('title');
            $table->string('likelihood', 20)->nullable()->after('risk');
            $table->string('impact', 20)->nullable()->after('likelihood');
            $table->text('consequence')->nullable()->after('impact');
            $table->text('treatment')->nullable()->after('consequence');
            $table->string('risk_level', 20)->nullable()->after('treatment');
            $table->json('photos')->nullable()->after('risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_recommendations', function (Blueprint $table) {
            $table->dropColumn([
                'risk',
                'likelihood',
                'impact',
                'consequence',
                'treatment',
                'risk_level',
                'photos',
            ]);
        });
    }
};
