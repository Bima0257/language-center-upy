<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'scheduled_start' => ['required', 'date'],
            'scheduled_end' => ['required', 'date', 'after:scheduled_start'],
            'late_tolerance_minutes' => ['integer', 'min:0', 'max:60'],
            'max_participants' => ['integer', 'min:1', 'max:500'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
