<?php

namespace App\Http\Requests\Admin;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewContractDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Contract|null $contract */
        $contract = $this->route('contract');

        return $contract !== null
            && ($this->user()?->can('update', $contract) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
