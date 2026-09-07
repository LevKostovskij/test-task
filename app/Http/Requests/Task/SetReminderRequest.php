<?php

declare(strict_types=1);

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class SetReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reminder_at' => ['required', 'date', 'after:now'],
        ];
    }

    public function messages(): array
    {
        return [
            'reminder_at.required' => 'Время напоминания обязательно',
            'reminder_at.date' => 'Время напоминания должно быть корректной датой',
            'reminder_at.after' => 'Время напоминания должно быть в будущем',
        ];
    }
}
