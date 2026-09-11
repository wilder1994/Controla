<?php

declare(strict_types=1);

use App\Enums\LegalCorpusType;
use App\Models\LegalCorpusVersion;
use Database\Seeders\Support\LegalCorpusDraftContent;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (LegalCorpusVersion::query()->where('type', LegalCorpusType::MinorsDataPolicy->value)->exists()) {
            return;
        }

        $content = LegalCorpusDraftContent::minorsDataPolicy();

        LegalCorpusVersion::query()->create([
            'type' => LegalCorpusType::MinorsDataPolicy->value,
            'package_sku' => null,
            'version' => '1.0',
            'title' => LegalCorpusType::MinorsDataPolicy->label(),
            'content' => $content,
            'effective_from' => now()->toDateString(),
            'superseded_at' => null,
            'content_hash' => hash('sha256', $content),
        ]);
    }

    public function down(): void
    {
        LegalCorpusVersion::query()
            ->where('type', LegalCorpusType::MinorsDataPolicy->value)
            ->delete();
    }
};
