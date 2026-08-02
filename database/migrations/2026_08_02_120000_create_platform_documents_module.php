<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('security_companies', function (Blueprint $table) {
            $table->string('party_type', 20)->default('legal_entity')->after('tax_id');
        });

        Schema::create('legal_corpus_versions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('version', 20);
            $table->string('title');
            $table->longText('content');
            $table->date('effective_from');
            $table->timestamp('superseded_at')->nullable();
            $table->char('content_hash', 64);
            $table->timestamps();

            $table->unique(['type', 'version']);
        });

        Schema::create('document_retention_series', function (Blueprint $table) {
            $table->id();
            $table->string('series');
            $table->string('subseries');
            $table->string('retention_label');
            $table->unsignedInteger('retention_days')->nullable();
            $table->string('disposition', 40);
            $table->string('legal_basis')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscription_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('representative_name');
            $table->string('representative_role');
            $table->string('representative_document_type', 20);
            $table->string('representative_document_number', 40);
            $table->json('corpus_snapshot');
            $table->char('content_hash', 64);
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('accepted_at');
            $table->timestamps();
        });

        Schema::create('commercial_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('COP');
            $table->string('billing_cycle', 20)->nullable();
            $table->string('method', 20);
            $table->string('status', 20);
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('platform_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('title');
            $table->string('reference_number', 60)->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->boolean('is_demo')->default(false);
            $table->string('cufe', 120)->nullable();
            $table->string('storage_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->date('retention_until')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['security_company_id', 'type']);
        });

        Schema::create('lifecycle_evidence_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 40);
            $table->string('title');
            $table->json('payload');
            $table->char('content_hash', 64);
            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['security_company_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lifecycle_evidence_events');
        Schema::dropIfExists('platform_documents');
        Schema::dropIfExists('commercial_payments');
        Schema::dropIfExists('subscription_acceptances');
        Schema::dropIfExists('document_retention_series');
        Schema::dropIfExists('legal_corpus_versions');

        Schema::table('security_companies', function (Blueprint $table) {
            $table->dropColumn('party_type');
        });
    }
};
