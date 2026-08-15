<?php

namespace App\Http\Requests\Schedule;

use App\Models\ExamScheduleSlot;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ExamScheduleSlot $slot */
        $slot = $this->route('slot');
        $schedule = $slot->schedule;

        return [
            'date' => ['required', 'date', 'after_or_equal:'.$schedule->start_date->toDateString(), 'before_or_equal:'.$schedule->end_date->toDateString()],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'late_tolerance_minutes' => ['nullable', 'integer', 'min:0', 'max:180'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['boolean'],
        ];
    }
}
