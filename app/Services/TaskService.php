<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use App\Exceptions\ReminderLimitException;
use Illuminate\Support\Facades\DB;

class TaskService
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function setReminder(Task $task, ?string $reminderAt): void
    {
        if ($task->status === 'completed' && $reminderAt !== null) {
            throw new \DomainException('Нельзя установить напоминание для выполненной задачи.');
        }

        DB::transaction(function () use ($task, $reminderAt) {
            $activeRemindersCount = Task::where('user_id', $task->user_id)
                ->whereNotNull('reminder_at')
                ->where('id', '!=', $task->id)
                ->lockForUpdate()
                ->count();

            if ($reminderAt !== null && $activeRemindersCount >= 3) {
                throw new ReminderLimitException('Достигнут лимит активных напоминаний (3).');
            }

            $task->reminder_at = $reminderAt;
            $task->save();
        });
    }

    public function changeStatus(Task $task, string $status): void
    {
        $task->status = $status;
        
        if ($status === 'completed') {
            $task->reminder_at = null;
        }
        
        $task->save();
    }
}
