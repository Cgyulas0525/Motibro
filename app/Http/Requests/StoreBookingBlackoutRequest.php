<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingBlackoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('starts_on') && ! $this->filled('ends_on')) {
            $this->merge(['ends_on' => $this->input('starts_on')]);
        }

        if ($this->input('note') === '') {
            $this->merge(['note' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'note' => 'nullable|string|max:100',
        ];
    }

    public function attributes(): array
    {
        return [
            'starts_on' => 'kezdete',
            'ends_on' => 'vége',
            'note' => 'megjegyzés',
        ];
    }
}
