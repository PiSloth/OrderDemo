<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhiteboardContentType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'description',
        'requires_decision',
    ];

    protected $casts = [
        'requires_decision' => 'boolean',
    ];

    public function contents(): HasMany
    {
        return $this->hasMany(WhiteboardContent::class, 'content_type_id');
    }
}
