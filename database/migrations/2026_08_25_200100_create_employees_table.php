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
            $table->string('blood_group', 16);
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
            $table->string('photo_path')->nullable();
            $table->string('education', 120)->nullable();
            $table->string('marital_status', 80)->nullable();
            $table->unsignedTinyInteger('children_count')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('residence_city', 120)->nullable();
            $table->string('address', 180)->nullable();
            $table->string('engagement_type', 80)->nullable();
            $table->string('contributor_type', 80)->nullable();
            $table->string('labor_contract_type', 80)->nullable();
            $table->date('hired_on')->nullable();
            $table->date('labor_contract_ends_on')->nullable();
            $table->date('left_on')->nullable();
            $table->string('eps_code', 40)->nullable();
            $table->string('eps_name', 120)->nullable();
            $table->string('afp_code', 40)->nullable();
            $table->string('afp_name', 120)->nullable();
            $table->string('compensation_fund', 120)->nullable();
            $table->string('arl_name', 120)->nullable();
            $table->string('arl_risk_level', 40)->nullable();
            $table->timestamps();

            $table->unique(['security_company_id', 'document_number']);
            $table->unique(['security_company_id', 'email']);
            $table->index(['security_company_id', 'is_active']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('employee_id')
                ->nullable()
                ->unique()
                ->after('security_company_id')
                ->constrained('employees')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });

        Schema::dropIfExists('employees');
    }
};
