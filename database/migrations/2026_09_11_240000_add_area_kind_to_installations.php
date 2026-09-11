<?php

declare(strict_types=1);

use App\Models\Installation;
use App\Support\Geo\ColombianArea;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->string('area_kind', 20)->nullable()->after('commune');
        });

        Installation::query()
            ->withoutGlobalScopes()
            ->orderBy('id')
            ->each(function (Installation $installation): void {
                $kind = ColombianArea::classify($installation->commune, $installation->city);
                $value = ColombianArea::persistableValue($installation->commune, $installation->city);
                $installation->forceFill([
                    'commune' => $value,
                    'area_kind' => $kind->value,
                ])->saveQuietly();
            });
    }

    public function down(): void
    {
        Schema::table('installations', function (Blueprint $table) {
            $table->dropColumn('area_kind');
        });
    }
};
