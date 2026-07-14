<?php

namespace App\Http\Requests\Admin;

use App\Enums\PropertyStatus;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Property::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'type' => ['required', 'string', 'max:50'],
            'status' => ['required', Rule::enum(PropertyStatus::class)],
            'owner_id' => ['nullable', 'integer', 'exists:owners,id'],
        ];
    }
}
