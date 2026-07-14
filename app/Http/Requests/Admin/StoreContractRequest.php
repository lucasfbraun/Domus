<?php

namespace App\Http\Requests\Admin;

use App\Enums\ContractStatus;
use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Contract::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->filled('fine_percent')) {
            $merge['fine_rate'] = ((float) $this->input('fine_percent')) / 100;
        }

        if ($this->filled('interest_percent')) {
            $merge['monthly_interest_rate'] = ((float) $this->input('interest_percent')) / 100;
        }

        if ($this->has('witness_receiver_ids') && ! is_array($this->input('witness_receiver_ids'))) {
            $merge['witness_receiver_ids'] = array_filter([(int) $this->input('witness_receiver_ids')]);
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
            'receiver_id' => ['required', 'integer', 'exists:receivers,id'],
            'monthly_rent' => ['required', 'numeric', 'min:0'],
            'due_day' => ['required', 'integer', 'min:1', 'max:31'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'fine_rate' => ['required', 'numeric', 'min:0'],
            'monthly_interest_rate' => ['required', 'numeric', 'min:0'],
            'fine_percent' => ['nullable', 'numeric', 'min:0'],
            'interest_percent' => ['nullable', 'numeric', 'min:0'],
            'grace_days' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', Rule::enum(ContractStatus::class)],
            'template_id' => ['nullable', 'integer', 'exists:contract_templates,id'],
            'witness_receiver_ids' => ['nullable', 'array'],
            'witness_receiver_ids.*' => ['integer', 'exists:receivers,id'],
        ];
    }
}
