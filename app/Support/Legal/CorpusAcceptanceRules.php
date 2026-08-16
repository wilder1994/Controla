<?php

declare(strict_types=1);

namespace App\Support\Legal;

use App\Enums\CompanyPackageSku;
use App\Enums\LegalCorpusType;
use App\Models\LegalCorpusVersion;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

final class CorpusAcceptanceRules
{
    /**
     * @return array<string, mixed>
     */
    public static function acceptDocRules(?CompanyPackageSku $sku): array
    {
        $rules = [
            'accept_docs' => ['required', 'array'],
        ];

        foreach (self::requiredTypeValues($sku) as $type) {
            $rules["accept_docs.{$type}"] = ['accepted'];
        }

        return $rules;
    }

    /**
     * @return list<string>
     */
    public static function requiredTypeValues(?CompanyPackageSku $sku): array
    {
        return self::corpus($sku)
            ->map(static function (LegalCorpusVersion $doc): string {
                $type = $doc->type;

                return $type instanceof LegalCorpusType ? $type->value : (string) $type;
            })
            ->unique()
            ->values()
            ->all();
    }

    /** @return Collection<int, LegalCorpusVersion> */
    public static function corpus(?CompanyPackageSku $sku): Collection
    {
        return LegalCorpusVersion::currentForPackage($sku);
    }

    /** @return array<string, mixed> */
    public static function documentTypeRule(): array
    {
        return [
            'required',
            'string',
            'max:20',
            Rule::exists('identity_document_types', 'code')->where('is_active', true),
        ];
    }
}
