<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GroupNumberComment extends Model
{
    use HasFactory;

    protected $table = 'group_number_comments';

    protected $guarded = [];

    public function group(): BelongsTo
    {
        return $this->belongsTo(GroupNumber::class, 'group_number_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
