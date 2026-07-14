<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\PreparesBrazilianFields;
use App\Models\Owner;
use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerRequest extends FormRequest
{
    use PreparesBrazilianFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Owner::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDocumentField();
        $this->preparePhoneField('phone');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'cpf_ou_cnpj'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^55\d{10,11}$/'],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'O campo telefone deve ser um número brasileiro válido com DDD.',
        ];
    }
}
