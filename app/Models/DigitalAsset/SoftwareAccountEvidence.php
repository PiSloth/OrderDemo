<?php

namespace App\Models\DigitalAsset;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareAccountEvidence extends Model
{
    use HasFactory;

    protected $table = 'software_account_evidence';

    protected $fillable = [
        'software_account_id',
        'property_definition_id',
        'property_history_id',
        'storage_disk',
        'file_path',
        'original_filename',
        'mime_type',
        'file_size_bytes',
        'sha256_hash',
        'uploaded_by',
        'verified_by',
        'verified_at',
        'status',
        'review_notes',
    ];

    protected $casts = [
        'file_size_bytes' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccount::class, 'software_account_id');
    }

    public function propertyDefinition(): BelongsTo
    {
        return $this->belongsTo(SoftwarePropertyDefinition::class, 'property_definition_id');
    }

    public function propertyHistory(): BelongsTo
    {
        return $this->belongsTo(SoftwareAccountPropertyHistory::class, 'property_history_id');
    }

    public function uploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
