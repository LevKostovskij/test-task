<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'reminder_at' => [
                'nullable',
                'date',
                'after:' . now()->addMinutes(15)->format('Y-m-d H:i:s'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reminder_at.after' => 'Время напоминания должно быть минимум через 15 минут',
        ];
    }
}
