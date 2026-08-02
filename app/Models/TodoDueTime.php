<?php

namespace App\Models;

use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiTaskTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TodoDueTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_category_id',
        'todo_priority_id',
        'duration',
        'description',
        'generate_kpi_instance',
        'kpi_group_id',
        'kpi_task_template_id',
    ];

    protected $casts = [
        'generate_kpi_instance' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(TodoCategory::class, 'todo_category_id');
    }

    public function priority()
    {
        return $this->belongsTo(TodoPriority::class, 'todo_priority_id');
    }

    public function kpiGroup()
    {
        return $this->belongsTo(KpiGroup::class, 'kpi_group_id');
    }

    public function kpiTemplate()
    {
        return $this->belongsTo(KpiTaskTemplate::class, 'kpi_task_template_id');
    }
}
