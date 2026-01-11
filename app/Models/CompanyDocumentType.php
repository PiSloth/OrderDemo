<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyDocumentType extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function documents(): HasMany
    {
        return $this->hasMany(CompanyDocument::class, 'company_document_type_id');
    }
}
