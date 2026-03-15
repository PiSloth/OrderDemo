<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class WhiteboardContent extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'propose_solution',
        'report_by',
        'created_by',
        'content_type_id',
        'propose_decision_due_at',
        'flag_id',
    ];

    protected $casts = [
        'propose_decision_due_at' => 'datetime',
    ];

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(EmailList::class, 'report_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contentType(): BelongsTo
    {
        return $this->belongsTo(WhiteboardContentType::class, 'content_type_id');
    }

    public function flag(): BelongsTo
    {
        return $this->belongsTo(WhiteboardFlag::class, 'flag_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(WhiteboardReport::class, 'content_id');
    }

    public function decisions(): HasMany
    {
        return $this->hasMany(WhiteboardDecision::class, 'content_id');
    }

    public function latestDecision(): HasOne
    {
        return $this->hasOne(WhiteboardDecision::class, 'content_id')->latestOfMany();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $query->where(function (Builder $visibleQuery) use ($user) {
            $visibleQuery
                ->where('created_by', $user->id)
                ->orWhereHas('reports.emailList', function (Builder $reportQuery) use ($user) {
                    $reportQuery->where('email', $user->email);

                    if ($user->department_id) {
                        $reportQuery->orWhere('department_id', $user->department_id);
                    }
                });
        });
    }

    public function scopeBoardFeed(Builder $query, User $user): Builder
    {
        return $query
            ->visibleTo($user)
            ->with([
                'contentType',
                'flag',
                'reporter.department',
                'reports.emailList.department',
                'latestDecision.creator',
                'decisions.creator',
            ])
            ->orderBy('propose_decision_due_at')
            ->latest();
    }

    public function markReadFor(User $user): void
    {
        $this->reports()
            ->whereHas('emailList', function (Builder $query) use ($user) {
                $query->where('email', $user->email);

                if ($user->department_id) {
                    $query->orWhere('department_id', $user->department_id);
                }
            })
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'read_by_user_id' => $user->id,
                'updated_at' => now(),
            ]);
    }

    public function requiresDecision(): bool
    {
        return (bool) $this->contentType?->requires_decision;
    }
}
