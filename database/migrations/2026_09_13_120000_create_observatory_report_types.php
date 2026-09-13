<?php

declare(strict_types=1);

use App\Models\Client;
use App\Services\Observatory\EnsureObservatoryReportTypesService;
use App\Support\Client\ClientPanelModules;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('observatory_report_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('slug', 80);
            $table->unsignedTinyInteger('level')->default(1);
            $table->string('color', 7);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['client_id', 'slug']);
            $table->index(['client_id', 'is_active']);
        });

        Schema::table('observatory_reports', function (Blueprint $table): void {
            $table->foreignId('observatory_report_type_id')
                ->nullable()
                ->after('kind')
                ->constrained('observatory_report_types')
                ->nullOnDelete();
        });

        $this->enableObservatoryOnExistingClients();
        $this->seedTypesForExistingClients();
    }

    public function down(): void
    {
        Schema::table('observatory_reports', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('observatory_report_type_id');
        });
        Schema::dropIfExists('observatory_report_types');
    }

    private function enableObservatoryOnExistingClients(): void
    {
        if (! Schema::hasColumn('clients', 'panel_modules')) {
            return;
        }

        $rows = DB::table('clients')->select('id', 'panel_modules')->get();
        foreach ($rows as $row) {
            $modules = is_string($row->panel_modules) ? json_decode($row->panel_modules, true) : $row->panel_modules;
            if (! is_array($modules)) {
                $modules = [];
            }
            $modules[ClientPanelModules::OBSERVATORY] = true;
            DB::table('clients')->where('id', $row->id)->update([
                'panel_modules' => json_encode($modules),
            ]);
        }
    }

    private function seedTypesForExistingClients(): void
    {
        $ensure = app(EnsureObservatoryReportTypesService::class);
        Client::query()->orderBy('id')->each(static function (Client $client) use ($ensure): void {
            $ensure->execute($client);
        });
    }
};
