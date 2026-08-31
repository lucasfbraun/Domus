<?php

namespace App\Http\Requests\Admin;

use App\Enums\SyncIntervalUnit;
use App\Models\PixSyncSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePixSyncSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'interval_value' => [
                'required',
                'integer',
                'min:'.PixSyncSetting::MIN_INTERVAL_VALUE,
                'max:'.PixSyncSetting::MAX_INTERVAL_VALUE,
            ],
            'interval_unit' => ['required', Rule::enum(SyncIntervalUnit::class)],
        ];
    }
}
