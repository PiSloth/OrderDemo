<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoDueTime extends Model
{
    use HasFactory;

    protected $fillable = ['todo_category_id', 'todo_priority_id', 'duration', 'description'];

    public function category()
    {
        return $this->belongsTo(TodoCategory::class, 'todo_category_id');
    }

    public function priority()
    {
        return $this->belongsTo(TodoPriority::class, 'todo_priority_id');
    }
}
