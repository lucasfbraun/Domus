<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\ContractTemplateVariables;
use Illuminate\Foundation\Http\FormRequest;

class StoreContractTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User && $user->hasRole(UserRole::Admin);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'content' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ContractTemplateVariables::isBlank($value)) {
                        $fail('O conteúdo do modelo é obrigatório.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('content')) {
            return;
        }

        $this->merge([
            'content' => ContractTemplateVariables::sanitizeHtml((string) $this->input('content')),
        ]);
    }
}
