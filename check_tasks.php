<?php

require_once 'vendor/autoload.php';

use App\Models\TodoList;
use Carbon\Carbon;

// Check all tasks and their dates
$tasks = TodoList::all();

echo "Total tasks: " . $tasks->count() . PHP_EOL;

foreach ($tasks as $task) {
    echo "Task ID: {$task->id}, Due Date: {$task->due_date}" . PHP_EOL;
}

// Check current month range
$dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
$dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');

echo PHP_EOL . "Date range: {$dateFrom} to {$dateTo}" . PHP_EOL;

// Check tasks in current month
$tasksInRange = TodoList::whereBetween('due_date', [
    Carbon::parse($dateFrom)->startOfDay(),
    Carbon::parse($dateTo)->endOfDay()
])->count();

echo "Tasks in current month range: {$tasksInRange}" . PHP_EOL;
