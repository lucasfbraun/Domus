<?php

namespace App\Http\Requests\Admin;

use App\Enums\TenantStatus;
use App\Http\Requests\Concerns\PreparesBrazilianFields;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantRequest extends FormRequest
{
    use PreparesBrazilianFields;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Tenant::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDocumentField();
        $this->preparePhoneField('whatsapp');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $emailRules = ['required', 'email', 'max:255'];

        // A password here means TenantController will create a linked User,
        // which needs a globally unique email across every role — otherwise
        // the insert fails with a raw SQLSTATE 23000 instead of a validation error.
        if (filled($this->input('password'))) {
            $emailRules[] = Rule::unique('users', 'email');
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'cpf_ou_cnpj'],
            'email' => $emailRules,
            'whatsapp' => ['nullable', 'string', 'regex:/^55\d{10,11}$/'],
            'status' => ['required', Rule::enum(TenantStatus::class)],
            'resident_count' => ['nullable', 'integer', 'min:1'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'O campo WhatsApp deve ser um número brasileiro válido com DDD.',
            'email.unique' => 'Já existe uma conta de acesso com este e-mail.',
        ];
    }
}
