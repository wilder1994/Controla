<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('clients', 'structure_type_id')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('structure_type_id')
                ->nullable()
                ->after('representative_email')
                ->constrained('structure_types')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('clients', 'structure_type_id')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('structure_type_id');
        });
    }
};
