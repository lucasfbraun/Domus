<?php

namespace App\Http\Requests\Admin;

use App\Enums\RateioSplitMode;
use App\Models\Rateio;
use App\Services\RateioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRateioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Rateio::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', Rule::in(RateioService::CATEGORIES)],
            'description' => ['nullable', 'string', 'max:500'],
            'reference' => ['required', 'string', 'max:50'],
            'total_amount' => ['required', 'numeric', 'min:0.01'],
            'split_mode' => ['required', Rule::enum(RateioSplitMode::class)],
            'property_ids' => ['required', 'array', 'min:1'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
            'invoice' => ['nullable', 'file', 'max:8192', 'mimetypes:image/jpeg,image/png,application/pdf'],
        ];
    }
}
