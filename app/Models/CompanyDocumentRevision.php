<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDocumentRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_document_id',
        'version',
        'title',
        'body',
        'company_document_type_id',
        'department_id',
        'announced_at',
        'edited_by',
    ];

    protected $casts = [
        'announced_at' => 'date',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(CompanyDocument::class, 'company_document_id');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CompanyDocumentType::class, 'company_document_type_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
