<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchedulerSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'enabled' => 'required|boolean',
            'window_start' => 'required|date_format:H:i',
            'window_end' => 'required|date_format:H:i|after:window_start',
            'interval_minutes' => 'required|integer|min:1|max:60',
            'timezone' => 'required|string|max:64',
        ];
    }

    public function attributes(): array
    {
        return [
            'enabled' => 'automatikus futás',
            'window_start' => 'ablak kezdete',
            'window_end' => 'ablak vége',
            'interval_minutes' => 'intervallum',
            'timezone' => 'időzóna',
        ];
    }
}
