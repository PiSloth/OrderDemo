<?php

namespace App\Models\DigitalAsset;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwarePropertyDefinition extends Model
{
    use HasFactory;

    protected $table = 'software_property_definitions';

    protected $fillable = [
        'software_platform_id',
        'code',
        'label',
        'data_type',
        'description',
        'is_required',
        'is_sensitive',
        'evidence_required',
        'evidence_types',
        'evidence_max_age_days',
        'review_interval_days',
        'review_on_change',
        'requires_approval',
        'validation_rules',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_sensitive' => 'boolean',
        'evidence_required' => 'boolean',
        'evidence_types' => 'array',
        'evidence_max_age_days' => 'integer',
        'review_interval_days' => 'integer',
        'review_on_change' => 'boolean',
        'requires_approval' => 'boolean',
        'validation_rules' => 'array',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function platform(): BelongsTo
    {
        return $this->belongsTo(SoftwarePlatform::class, 'software_platform_id');
    }

    public function propertyHistories(): HasMany
    {
        return $this->hasMany(SoftwareAccountPropertyHistory::class, 'property_definition_id');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(SoftwareAccountEvidence::class, 'property_definition_id');
    }
}
