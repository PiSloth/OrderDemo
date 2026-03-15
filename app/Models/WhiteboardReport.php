<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteboardReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'content_id',
        'email_list_id',
        'is_read',
        'read_at',
        'read_by_user_id',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function content(): BelongsTo
    {
        return $this->belongsTo(WhiteboardContent::class, 'content_id');
    }

    public function emailList(): BelongsTo
    {
        return $this->belongsTo(EmailList::class);
    }

    public function readBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'read_by_user_id');
    }
}
