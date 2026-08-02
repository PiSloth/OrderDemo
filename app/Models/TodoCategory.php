<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TodoCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'department_id'];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function dueTimes(): HasMany
    {
        return $this->hasMany(TodoDueTime::class, 'todo_category_id');
    }
}
