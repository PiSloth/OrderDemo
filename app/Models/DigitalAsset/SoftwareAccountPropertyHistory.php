<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SoftwareAccountPropertyHistory extends Model
{
    use HasFactory;

    protected $table = 'software_account_property_history';

    protected $fillable = [
        'software_account_id',
        'property_definition_id',
        'old_value',
        'new_value',
        'changed_by',
        'changed_at',
        'change_reason',
        'review_status',
        'request_id',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'changed_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function propertyDefinition(): BelongsTo
    {
        return $this->belongsTo(SoftwarePropertyDefinition::class, 'property_definition_id');
    }

    public function changedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(SoftwareAccountEvidence::class, 'property_history_id');
    }
}
