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
        Schema::table('security_companies', function (Blueprint $table) {
            $table->unsignedTinyInteger('billing_day')->nullable()->after('billing_cycle');
        });

        DB::table('security_companies')
            ->where('archive_reason', 'recovery')
            ->update(['archive_reason' => 'non_payment']);

        // Empresas ya activas: día de corte = día de inicio de paquete (máx. 28).
        DB::table('security_companies')
            ->whereNull('billing_day')
            ->whereNotNull('package_starts_at')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $day = (int) date('j', strtotime((string) $row->package_starts_at));
                    $day = min(28, max(1, $day));
                    DB::table('security_companies')
                        ->where('id', $row->id)
                        ->update(['billing_day' => $day]);
                }
            });
    }

    public function down(): void
    {
        DB::table('security_companies')
            ->where('archive_reason', 'non_payment')
            ->update(['archive_reason' => 'recovery']);

        Schema::table('security_companies', function (Blueprint $table) {
            $table->dropColumn('billing_day');
        });
    }
};
