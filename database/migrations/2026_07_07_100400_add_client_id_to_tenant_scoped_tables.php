<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'locations',
        'buildings',
        'housing_units',
        'residents',
        'visitors',
        'vehicles',
        'access_logs',
        'pre_authorizations',
        'correspondence',
        'guard_logs',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'client_id')) {
                    $table->foreignId('client_id')
                        ->nullable()
                        ->after('id')
                        ->constrained()
                        ->cascadeOnDelete();
                }
            });
        }

        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['client_id', 'code']);
        });

        Schema::table('buildings', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['client_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'code']);
            $table->unique(['code']);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique(['client_id', 'code']);
            $table->unique(['code']);
        });

        foreach (array_reverse($this->tables) as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('client_id');
            });
        }
    }
};
