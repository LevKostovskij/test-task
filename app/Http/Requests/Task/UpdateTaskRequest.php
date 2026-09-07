<?php

namespace App\Http\Requests\Task;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:pending,completed'],
        ];

        if ($this->has('reminder_at')) {
            if ($this->reminder_at === null) {
                $rules['reminder_at'] = ['nullable'];
            } else {
                $rules['reminder_at'] = ['nullable', 'date', 'after:now'];
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->has('reminder_at') && $this->reminder_at !== null) {
                $task = $this->route('task');
                $status = $this->status ?? $task->status;
                
                if ($status === 'completed') {
                    $validator->errors()->add(
                        'reminder_at',
                        'Нельзя установить напоминание для выполненной задачи'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'reminder_at.after' => 'Время напоминания должно быть в будущем',
        ];
    }
}
