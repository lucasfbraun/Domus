<?php

namespace App\Http\Requests\Concerns;

use App\Support\BrazilianDocument;
use App\Support\BrazilianPhone;

/**
 * Normalizes Brazilian-formatted fields in `prepareForValidation()`: strips
 * CPF/CNPJ documents down to digits via {@see BrazilianDocument} and reformats
 * phone numbers via {@see BrazilianPhone}. Used by owner, receiver, and tenant
 * store requests.
 */
trait PreparesBrazilianFields
{
    protected function prepareDocumentField(string $field = 'document'): void
    {
        if (! $this->has($field)) {
            return;
        }

        $digits = BrazilianDocument::digits($this->string($field)->toString());

        $this->merge([
            $field => $digits !== '' ? $digits : null,
        ]);
    }

    protected function preparePhoneField(string $field): void
    {
        if (! $this->has($field)) {
            return;
        }

        $this->merge([
            $field => BrazilianPhone::normalize($this->string($field)->toString()),
        ]);
    }
}
