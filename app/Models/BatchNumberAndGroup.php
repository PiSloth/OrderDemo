<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BatchNumberAndGroup extends Model
{
    protected $table = 'batch_number_and_group';

    protected $fillable = [
        'group_number_id',
        'batch_id',
        'is_post',
        'post_by',
    ];

    protected $casts = [
        'batch_id' => 'int',
        'is_post' => 'bool',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(GroupNumber::class, 'group_number_id');
    }

    public function postBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'post_by');
    }
}
