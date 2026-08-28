<?php

namespace App\Models\Training;

use App\Models\Department;
use App\Models\OfficePosition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingScope extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'department_id',
        'office_position_id',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function officePosition(): BelongsTo
    {
        return $this->belongsTo(OfficePosition::class);
    }
}
