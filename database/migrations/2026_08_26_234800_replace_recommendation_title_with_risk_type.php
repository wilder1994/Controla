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
            $table->foreignId('supervisor_risk_type_id')
                ->nullable()
                ->after('due_date')
                ->constrained('supervisor_risk_types')
                ->nullOnDelete();
            $table->string('risk_type', 120)->nullable()->after('supervisor_risk_type_id');
            $table->dropColumn('title');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_recommendations', function (Blueprint $table) {
            $table->string('title', 120)->nullable()->after('due_date');
            $table->dropConstrainedForeignId('supervisor_risk_type_id');
            $table->dropColumn('risk_type');
        });
    }
};
