<?php

namespace App\Services\Todo;

use App\Models\TodoList;
use App\Models\TodoReference;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TodoReferenceService
{
    public function __construct(protected TodoTaskService $taskService) {}

    /**
     * Create a ToDo task linked to any reference model with a recorded reason.
     *
     * @param Model $referenceModel Any Eloquent model (e.g. SoftwareAccountReview, Order, etc.)
     * @param array $taskData ['task' => string, 'assigned_user_id' => ?int, 'due_date' => ?string, 'selected_due_time_id' => ?int, etc.]
     * @param string|null $reason Why this ToDo happened (e.g. "Compliance review identified missing 2FA")
     * @param string|null $module Module identifier (defaults to snake_case of model class name)
     * @param string $actionType e.g. 'remediation', 'verification', 'follow_up'
     * @param array|null $metadata Optional JSON metadata
     * @param User|null $creator
     * @return TodoReference
     */
    public function createReferencedTask(
        Model $referenceModel,
        array $taskData,
        ?string $reason = null,
        ?string $module = null,
        string $actionType = 'remediation',
        ?array $metadata = null,
        ?User $creator = null
    ): TodoReference {
        return DB::transaction(function () use ($referenceModel, $taskData, $reason, $module, $actionType, $metadata, $creator) {
            $creator = $creator ?: Auth::user();

            // 1. Create the ToDo task via core TodoTaskService (stores created_by_user_id on todo_lists)
            $task = $this->taskService->createTask($taskData, $creator);

            // 2. Resolve module name if not specified
            $moduleName = $module ?: Str::snake(class_basename($referenceModel));

            // 3. Create the reference record connecting reference and todo task
            return TodoReference::create([
                'todo_list_id' => $task->id,
                'reference_type' => get_class($referenceModel),
                'reference_id' => $referenceModel->getKey(),
                'module' => $moduleName,
                'reason' => $reason,
                'action_type' => $actionType,
                'metadata' => $metadata,
            ]);
        });
    }

    /**
     * Link an existing ToDo task to any reference model/entity.
     */
    public function linkTaskToReference(
        TodoList $task,
        string $referenceType,
        int|string $referenceId,
        ?string $reason = null,
        ?string $module = null,
        string $actionType = 'remediation',
        ?array $metadata = null
    ): TodoReference {
        $moduleName = $module ?: Str::snake(class_basename($referenceType));

        return TodoReference::create([
            'todo_list_id' => $task->id,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'module' => $moduleName,
            'reason' => $reason,
            'action_type' => $actionType,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Optional sync helper kept for backward compatibility (no-op now since resolution is read dynamically from todo_lists).
     */
    public function syncTaskResolution(TodoList $task): void
    {
        // No-op: status, resolved_at (closed_at), and closed_by_user_id are directly read from todo_lists table.
    }
}
