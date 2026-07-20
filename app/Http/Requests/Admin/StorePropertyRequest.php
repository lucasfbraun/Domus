<?php

namespace App\Http\Requests\Admin;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Property::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('owner_ids') && ! is_array($this->input('owner_ids'))) {
            $this->merge([
                'owner_ids' => array_filter([(int) $this->input('owner_ids')]),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'status' => ['required', Rule::enum(PropertyStatus::class)],
            'owner_ids' => ['nullable', 'array'],
            'owner_ids.*' => ['integer', 'exists:owners,id'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];
    }
}
