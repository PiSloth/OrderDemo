<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTextBlock extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'sequence_order',
        'block_type',
        'category_type',
        'branch_code',
        'process_code',
        'risk_level',
        'plain_text',
        'html_content',
        'json_content'
    ];

    protected $casts = [
        'sequence_order' => 'integer',
        'json_content' => 'array'
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
