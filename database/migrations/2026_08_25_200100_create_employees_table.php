<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('job_title_id')->constrained('company_job_titles')->restrictOnDelete();
            $table->string('document_type', 20);
            $table->string('document_number', 40);
            $table->string('last_name_paternal', 80);
            $table->string('last_name_maternal', 80);
            $table->string('first_names', 120);
            $table->string('sex', 20);
            $table->date('birth_date');
            $table->string('collaborator_type', 20);
            $table->string('email');
            $table->string('nationality', 80)->default('COLOMBIANA');
            $table->string('blood_group', 8);
            $table->string('birth_department', 120)->nullable();
            $table->string('birth_city', 120)->nullable();
            $table->string('emergency_phone', 40)->nullable();
            $table->string('emergency_contact', 150)->nullable();
            $table->boolean('has_disability')->default(false);
            $table->string('document_issue_department', 120)->nullable();
            $table->string('document_issue_city', 120)->nullable();
            $table->date('document_issued_at')->nullable();
            $table->boolean('same_cost_center')->nullable();
            $table->boolean('is_active')->default(true);
            $table->date('ceased_at')->nullable();
            $table->timestamps();

            $table->unique(['security_company_id', 'document_number']);
            $table->unique(['security_company_id', 'email']);
            $table->index(['security_company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
