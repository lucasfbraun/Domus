<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\PreparesBrazilianFields;
use App\Models\Receiver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreReceiverRequest extends FormRequest
{
    use PreparesBrazilianFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Receiver::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDocumentField();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'cpf_ou_cnpj'],
            'email' => ['required', 'email', 'max:255'],
            'mercado_pago_account' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }
}
