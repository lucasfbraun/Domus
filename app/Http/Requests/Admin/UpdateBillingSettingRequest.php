<?php

namespace App\Http\Requests\Admin;

use App\Models\BillingSetting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillingSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'generation_day' => [
                'required',
                'integer',
                'min:'.BillingSetting::MIN_GENERATION_DAY,
                'max:'.BillingSetting::MAX_GENERATION_DAY,
            ],
        ];
    }
}
