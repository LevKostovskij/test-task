<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Requests\Task\UpdateTaskRequest;
use App\Http\Requests\Task\SetReminderRequest;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tasks = $request->user()->tasks()->latest()->get();

        return response()->json(['data' => $tasks]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $task = $request->user()->tasks()->create($request->validated());

        return response()->json(['data' => $task], 201);
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validated();

        if (array_key_exists('reminder_at', $data)) {
            $reminderAt = $data['reminder_at'];

            if ($reminderAt === null) {
                $task->reminder_at = null;
            } else {
                $result = DB::transaction(function () use ($request, $task, $reminderAt) {
                    $activeCount = Task::where('user_id', $task->user_id)
                        ->whereNotNull('reminder_at')
                        ->where('id', '!=', $task->id)
                        ->lockForUpdate()
                        ->count();

                    if ($activeCount >= 3) {
                        return ['error' => 'Достигнут лимит активных напоминаний (3)', 'code' => 409];
                    }

                    $task->reminder_at = $reminderAt;

                    return ['success' => true];
                });

                if (isset($result['error'])) {
                    return response()->json(['message' => $result['error']], $result['code']);
                }
            }

            unset($data['reminder_at']);
        }

        if (isset($data['status'])) {
            if ($data['status'] === 'completed') {
                $task->reminder_at = null;
            }
            $task->status = $data['status'];
            unset($data['status']);
        }

        if (!empty($data)) {
            $task->fill($data);
        }

        $task->save();

        return response()->json(['data' => $task->fresh()]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $task->delete();

        return response()->json(['message' => 'Задача удалена']);
    }

    public function setReminder(SetReminderRequest $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($task->status === 'completed') {
            return response()->json(['message' => 'Нельзя установить напоминание для выполненной задачи'], 422);
        }

        $result = DB::transaction(function () use ($request, $task) {
            $activeCount = Task::where('user_id', $task->user_id)
                ->whereNotNull('reminder_at')
                ->where('id', '!=', $task->id)
                ->lockForUpdate()
                ->count();

            if ($activeCount >= 3) {
                return ['error' => 'Достигнут лимит активных напоминаний (3)', 'code' => 409];
            }

            $task->reminder_at = $request->reminder_at;
            $task->save();

            return ['data' => $task->fresh(), 'code' => 200];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['code']);
        }

        return response()->json(['data' => $result['data']]);
    }

    public function deleteReminder(Request $request, Task $task): JsonResponse
    {
        if ($task->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $task->reminder_at = null;
        $task->save();

        return response()->json(['data' => $task->fresh()]);
    }
}
