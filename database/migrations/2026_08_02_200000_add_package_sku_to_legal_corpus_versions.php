<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_corpus_versions', function (Blueprint $table) {
            $table->string('package_sku', 40)->nullable()->after('type');
            $table->dropUnique(['type', 'version']);
            $table->unique(['type', 'package_sku', 'version']);
            $table->index(['type', 'package_sku', 'superseded_at']);
        });
    }

    public function down(): void
    {
        Schema::table('legal_corpus_versions', function (Blueprint $table) {
            $table->dropIndex(['type', 'package_sku', 'superseded_at']);
            $table->dropUnique(['type', 'package_sku', 'version']);
            $table->dropColumn('package_sku');
            $table->unique(['type', 'version']);
        });
    }
};
