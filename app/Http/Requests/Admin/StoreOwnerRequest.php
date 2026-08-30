<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\PreparesBrazilianFields;
use App\Models\Owner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
        // Creating a brand-new dedicated login (password, no existing user
        // picked) needs a real, globally unique email — the owner's own
        // `email` doubles as that login's email, same as Tenant/Receiver.
        $creatingNewLogin = filled($this->input('password')) && ! filled($this->input('existing_user_id'));

        $emailRules = $creatingNewLogin
            ? ['required', 'email', 'max:255', Rule::unique('users', 'email')]
            : ['nullable', 'email', 'max:255'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'cpf_ou_cnpj'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'regex:/^55\d{10,11}$/'],
            'property_ids' => ['nullable', 'array'],
            'property_ids.*' => ['integer', 'exists:properties,id'],
            'existing_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'password' => [
                'nullable',
                'confirmed',
                Password::defaults(),
                Rule::prohibitedIf(fn () => filled($this->input('existing_user_id'))),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'O campo telefone deve ser um número brasileiro válido com DDD.',
            'email.unique' => 'Já existe uma conta de acesso com este e-mail.',
            'password.prohibited' => 'Escolha "criar novo login" ou "vincular usuário existente", não os dois.',
        ];
    }
}
