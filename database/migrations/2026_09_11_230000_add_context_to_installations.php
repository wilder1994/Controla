<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Installation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->string('code', 40)->nullable()->after('name');
            $table->string('commune', 80)->nullable()->after('code');
            $table->foreignId('rector_user_id')->nullable()->after('commune')->constrained('users')->nullOnDelete();
        });

        Installation::query()
            ->orderBy('client_id')
            ->orderBy('id')
            ->each(function (Installation $installation): void {
                if (filled($installation->code)) {
                    return;
                }

                $client = Client::query()->find($installation->client_id);
                $installation->forceFill([
                    'code' => $this->nextCode($client, (int) $installation->id),
                ])->saveQuietly();
            });

        Schema::table('installations', function (Blueprint $table) {
            $table->unique(['client_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'code']);
            $table->dropConstrainedForeignId('rector_user_id');
            $table->dropColumn(['code', 'commune']);
        });
    }

    private function nextCode(?Client $client, int $installationId): string
    {
        $raw = $client?->slug ?: 'sed';
        $prefix = Str::upper(Str::substr((string) preg_replace('/[^A-Za-z0-9]+/', '', $raw), 0, 8)) ?: 'SED';

        return $prefix.'-'.str_pad((string) $installationId, 2, '0', STR_PAD_LEFT);
    }
};
