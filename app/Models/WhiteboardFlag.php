<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhiteboardFlag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color',
        'description',
    ];

    public function contents(): HasMany
    {
        return $this->hasMany(WhiteboardContent::class, 'flag_id');
    }
}
