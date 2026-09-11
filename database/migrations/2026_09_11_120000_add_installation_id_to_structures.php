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
        Schema::table('structures', function (Blueprint $table) {
            $table->foreignId('installation_id')
                ->nullable()
                ->after('client_id')
                ->constrained('installations')
                ->nullOnDelete();
            $table->index(['client_id', 'installation_id']);
        });

        $this->backfillInstallationId();

        Schema::table('structures', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'code']);
            $table->unique(['installation_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('structures', function (Blueprint $table) {
            $table->dropUnique(['installation_id', 'code']);
            $table->unique(['client_id', 'code']);
            $table->dropConstrainedForeignId('installation_id');
        });
    }

    private function backfillInstallationId(): void
    {
        $rows = DB::table('installations')
            ->whereNull('deleted_at')
            ->orderByDesc('is_client_site')
            ->orderBy('id')
            ->get(['id', 'client_id']);

        $byClient = [];
        foreach ($rows as $row) {
            if (! isset($byClient[$row->client_id])) {
                $byClient[$row->client_id] = $row->id;
            }
        }

        foreach ($byClient as $clientId => $installationId) {
            DB::table('structures')
                ->where('client_id', $clientId)
                ->whereNull('installation_id')
                ->update(['installation_id' => $installationId]);
        }
    }
};
