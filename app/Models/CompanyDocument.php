<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class CompanyDocument extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Searchable;

    protected static function booted(): void
    {
        static::saving(function (self $document): void {
            if ($document->isDirty('body') || $document->content_text === null) {
                $text = strip_tags((string) $document->body);
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $text = preg_replace('/\s+/u', ' ', $text) ?? '';
                $document->content_text = trim($text);
            }
        });
    }

    protected $fillable = [
        'title',
        'body',
        'content_text',
        'company_document_type_id',
        'department_id',
        'announced_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'announced_at' => 'date',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CompanyDocumentType::class, 'company_document_type_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastEditor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(CompanyDocumentRevision::class)->orderByDesc('version');
    }

    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // Current rule: every authenticated user can browse company documents.
        // Future-ready: tighten this for private/restricted records.
        return $query;
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content_text' => $this->content_text,
            'department_id' => $this->department_id,
            'department' => $this->department?->name,
            'category_id' => $this->company_document_type_id,
            'category' => $this->type?->name,
            'creator_id' => $this->created_by,
            'creator' => $this->author?->name,
            'is_announcement' => $this->announced_at !== null,
            'published_at' => optional($this->announced_at ?? $this->created_at)?->toDateString(),
            'updated_at' => optional($this->updated_at)?->toAtomString(),
        ];
    }

    public function searchableAs(): string
    {
        return 'company_documents';
    }
}
