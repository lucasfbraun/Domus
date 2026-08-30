<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\PreparesBrazilianFields;
use App\Models\Receiver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
        $emailRules = ['required', 'email', 'max:255'];

        // A password here (and no existing user picked) means
        // ReceiverController will create a brand-new linked User, which
        // needs a globally unique email across every role — otherwise the
        // insert fails with a raw SQLSTATE 23000 instead of a validation error.
        if (filled($this->input('password')) && ! filled($this->input('existing_user_id'))) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'cpf_ou_cnpj'],
            'email' => $emailRules,
            'mercado_pago_account' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
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
            'email.unique' => 'Já existe uma conta de acesso com este e-mail.',
            'password.prohibited' => 'Escolha "criar novo login" ou "vincular usuário existente", não os dois.',
        ];
    }
}
