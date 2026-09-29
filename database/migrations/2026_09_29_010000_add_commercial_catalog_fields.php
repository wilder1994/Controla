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
        Schema::table('pricing_settings', function (Blueprint $table) {
            $table->json('catalog')->nullable()->after('unit_price_supervision');
        });

        Schema::table('security_companies', function (Blueprint $table) {
            $table->boolean('has_indexing')->default(true)->after('max_clients');
            $table->boolean('has_observatory')->default(true)->after('has_indexing');
        });

        DB::table('security_companies')->update([
            'has_indexing' => true,
            'has_observatory' => true,
        ]);
    }

    public function down(): void
    {
        Schema::table('security_companies', function (Blueprint $table) {
            $table->dropColumn(['has_indexing', 'has_observatory']);
        });

        Schema::table('pricing_settings', function (Blueprint $table) {
            $table->dropColumn('catalog');
        });
    }
};
