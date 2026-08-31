<?php

namespace App\Http\Requests\Admin;

use App\Enums\BackupFrequency;
use App\Models\BackupSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBackupSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'frequency' => ['required', Rule::enum(BackupFrequency::class)],
            'retention_count' => [
                'required',
                'integer',
                'min:'.BackupSetting::MIN_RETENTION_COUNT,
                'max:'.BackupSetting::MAX_RETENTION_COUNT,
            ],
            'run_at_hour' => [
                'required',
                'integer',
                'min:'.BackupSetting::MIN_RUN_AT_HOUR,
                'max:'.BackupSetting::MAX_RUN_AT_HOUR,
            ],
        ];
    }
}
