<?php

namespace App\Http\Requests;

use App\Http\Controllers\TenantPreRegistrationFormController;
use App\Http\Requests\Concerns\PreparesBrazilianFields;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Public, unauthenticated submission of a tenant pre-registration form.
 * Anyone holding a valid token may submit — see
 * {@see TenantPreRegistrationFormController} for the
 * token/status/expiry checks, which live in the controller rather than
 * here since they need the route-resolved model, not just field values.
 */
class StoreTenantPreRegistrationSubmissionRequest extends FormRequest
{
    use PreparesBrazilianFields;

    public function authorize(): bool
    {
        return true;
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'string', 'cpf_ou_cnpj'],
            'email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['required', 'string', 'regex:/^55\d{10,11}$/'],
            'resident_count' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Informe um numero de WhatsApp brasileiro valido com DDD.',
        ];
    }
}
