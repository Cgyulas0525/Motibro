<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $bookingRule = $this->route('booking_rule');

        return [
            'label' => 'nullable|string|max:100',
            'weekday' => 'required|integer|between:1,7',
            'time' => [
                'required',
                'date_format:H:i',
                Rule::unique('booking_rules', 'time')
                    ->where('weekday', $this->input('weekday'))
                    ->ignore($bookingRule?->id),
            ],
            'enabled' => 'boolean',
            'waitlist_ok' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ];
    }

    public function attributes(): array
    {
        return [
            'weekday' => 'nap',
            'time' => 'idő',
            'label' => 'megjegyzés',
            'waitlist_ok' => 'várólista',
            'sort_order' => 'sorrend',
        ];
    }
}
