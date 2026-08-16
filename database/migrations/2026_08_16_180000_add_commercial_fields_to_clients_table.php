<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'party_type')) {
                $table->string('party_type', 20)->default('legal_entity')->after('name');
            }
            if (! Schema::hasColumn('clients', 'legal_name')) {
                $table->string('legal_name')->nullable()->after('party_type');
            }
            if (! Schema::hasColumn('clients', 'document_type')) {
                $table->string('document_type', 20)->nullable()->after('legal_name');
            }
            if (! Schema::hasColumn('clients', 'tax_id')) {
                $table->string('tax_id', 40)->nullable()->after('document_type');
            }
            if (! Schema::hasColumn('clients', 'email')) {
                $table->string('email')->nullable()->after('tax_id');
            }
            if (! Schema::hasColumn('clients', 'phone')) {
                $table->string('phone', 40)->nullable()->after('email');
            }
            if (! Schema::hasColumn('clients', 'representative_name')) {
                $table->string('representative_name')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('clients', 'representative_email')) {
                $table->string('representative_email')->nullable()->after('representative_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            foreach ([
                'party_type',
                'legal_name',
                'document_type',
                'tax_id',
                'email',
                'phone',
                'representative_name',
                'representative_email',
            ] as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
