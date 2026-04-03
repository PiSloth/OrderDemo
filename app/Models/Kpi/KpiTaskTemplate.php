<?php

namespace App\Models\Kpi;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiTaskTemplate extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'requires_images' => 'boolean',
        'requires_table' => 'boolean',
        'image_remark_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(KpiGroup::class, 'kpi_group_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function rule(): HasOne
    {
        return $this->hasOne(KpiTaskRule::class, 'task_template_id');
    }

    public function evidenceFields(): HasMany
    {
        return $this->hasMany(KpiTaskTemplateEvidenceField::class, 'task_template_id');
    }

    public function roleAssignments(): HasMany
    {
        return $this->hasMany(KpiRoleAssignment::class, 'task_template_id');
    }

    public function taskAssignments(): HasMany
    {
        return $this->hasMany(KpiTaskAssignment::class, 'task_template_id');
    }
}
