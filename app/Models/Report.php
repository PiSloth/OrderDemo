<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_number',
        'title',
        'author_id',
        'status',
        'last_autosaved_at'
    ];

    protected $casts = [
        'last_autosaved_at' => 'datetime'
    ];

    public function textBlocks(): HasMany
    {
        return $this->hasMany(ReportTextBlock::class)->orderBy('sequence_order');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
