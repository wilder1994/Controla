<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('security_company_id')->constrained('security_companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('folder', 32);
            $table->string('document_type', 64)->nullable();
            $table->string('display_name')->nullable();
            $table->unsignedSmallInteger('page_from')->nullable();
            $table->unsignedSmallInteger('page_to')->nullable();
            $table->json('pages')->nullable();
            $table->boolean('not_applicable')->default(false);
            $table->string('original_name');
            $table->string('disk_path');
            $table->string('mime', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->date('expires_on')->nullable();
            $table->date('taken_on')->nullable();
            $table->string('provider', 180)->nullable();
            $table->timestamps();

            $table->index(['security_company_id', 'employee_id', 'folder']);
        });

        Schema::create('employee_document_batches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('security_company_id')->constrained('security_companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('folder', 32)->nullable();
            $table->string('original_name');
            $table->string('disk_path');
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('page_count')->default(1);
            $table->timestamps();
        });

        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('show_personnel_folders')->default(false)->after('has_supervision');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('show_personnel_folders');
        });
        Schema::dropIfExists('employee_document_batches');
        Schema::dropIfExists('employee_documents');
    }
};
