<?php

namespace App\Http\Requests\Admin;

use App\Enums\OccurrenceStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContractOccurrenceRequest extends FormRequest
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
            'status' => ['required', Rule::enum(OccurrenceStatus::class)],
            'resolution_note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
