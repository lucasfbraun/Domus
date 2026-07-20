<?php

namespace App\Http\Requests\Admin;

use App\Models\Deposit;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDepositRequest extends FormRequest
{
    public function authorize(): bool
    {
        $deposit = $this->route('deposit');

        return $deposit instanceof Deposit && ($this->user()?->can('update', $deposit) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contract_id' => ['required', 'integer', 'exists:contracts,id'],
            'receiver_id' => ['required', 'integer', 'exists:receivers,id'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'due_date' => ['required', 'date'],
        ];
    }
}
