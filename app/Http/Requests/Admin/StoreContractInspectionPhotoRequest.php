<?php

namespace App\Http\Requests\Admin;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractInspectionPhotoRequest extends FormRequest
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
            'photo' => ['required', 'image', 'max:8192'],
            'caption' => ['nullable', 'string', 'max:255'],
            'room' => ['nullable', 'string', 'max:100'],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
