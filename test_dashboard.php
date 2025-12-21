<?php

require_once 'vendor/autoload.php';

use App\Models\TodoList;
use App\Models\TodoStatus;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Simulate the dashboard data loading
$dateFrom = '2025-12-01';
$dateTo = '2025-12-31';

$query = TodoList::with([
    'status',
    'assignedUser',
    'createdByUser',
    'dueTime.category',
    'dueTime.priority',
    'requestedByBranch',
    'department.location.branch'
]);

$query->whereBetween('due_date', [
    Carbon::parse($dateFrom)->startOfDay(),
    Carbon::parse($dateTo)->endOfDay()
]);

$tasks = $query->get();

echo "Total tasks loaded: " . $tasks->count() . PHP_EOL;

// Get status IDs
$finishedStatusIds = TodoStatus::whereIn('status', ['Completed', 'Done', 'Finished', 'Successed'])->pluck('id')->toArray();
$failedStatusIds = TodoStatus::whereIn('status', ['Cancelled', 'Failed', 'Rejected'])->pluck('id')->toArray();
$newStatusIds = TodoStatus::whereIn('status', ['New'])->pluck('id')->toArray();

echo "Finished status IDs: " . implode(', ', $finishedStatusIds) . PHP_EOL;
echo "Failed status IDs: " . implode(', ', $failedStatusIds) . PHP_EOL;
echo "New status IDs: " . implode(', ', $newStatusIds) . PHP_EOL;

// Categorize tasks
$unfinishedTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
    return !in_array($task->todo_status_id, array_merge($finishedStatusIds, $failedStatusIds));
})->values();

$finishedTasks = $tasks->filter(function ($task) use ($finishedStatusIds) {
    return in_array($task->todo_status_id, $finishedStatusIds);
})->values();

$failedTasks = $tasks->filter(function ($task) use ($failedStatusIds) {
    return in_array($task->todo_status_id, $failedStatusIds);
})->values();

$passedTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
    return !in_array($task->todo_status_id, array_merge($finishedStatusIds, $failedStatusIds))
        && $task->due_date < now();
})->values();

$needToDoTasks = $tasks->filter(function ($task) use ($finishedStatusIds, $failedStatusIds) {
    return !in_array($task->todo_status_id, array_merge($finishedStatusIds, $failedStatusIds))
        && $task->due_date >= now()
        && $task->due_date <= now()->addDays(7);
})->values();

$newTasks = $tasks->filter(function ($task) use ($newStatusIds) {
    return in_array($task->todo_status_id, $newStatusIds);
})->values();

echo "New tasks: " . $newTasks->count() . PHP_EOL;
echo "Unfinished tasks: " . $unfinishedTasks->count() . PHP_EOL;
echo "Need to do tasks: " . $needToDoTasks->count() . PHP_EOL;
echo "Passed tasks: " . $passedTasks->count() . PHP_EOL;
echo "Finished tasks: " . $finishedTasks->count() . PHP_EOL;
echo "Failed tasks: " . $failedTasks->count() . PHP_EOL;

// Show task details
foreach ($tasks as $task) {
    echo "Task ID: {$task->id}, Status: {$task->status->status}, Due: {$task->due_date}, Status ID: {$task->todo_status_id}" . PHP_EOL;
}
