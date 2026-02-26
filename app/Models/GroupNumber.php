<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupNumber extends Model
{
    protected $table = 'group_numbers';

    protected $fillable = [
        'number',
        'purchase_by',
        'is_purchase',
        'purchase_status',
        'po_reference',
        'entry_skill_grade',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_purchase' => 'bool',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'entry_skill_grade' => 'int',
    ];

    public function purchaseBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchase_by');
    }

    public function jewelryItems(): HasMany
    {
        return $this->hasMany(JewelryItem::class, 'group_number_id');
    }

    public function batchLinks(): HasMany
    {
        return $this->hasMany(BatchNumberAndGroup::class, 'group_number_id');
    }

    public function durationMinutes(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        /** @var CarbonInterface $start */
        $start = $this->started_at;
        /** @var CarbonInterface $end */
        $end = $this->finished_at;

        return max(0, (int) $start->diffInMinutes($end));
    }

    /**
     * Grade rule:
     * <= 10 min => 1 (Excellent)
     * <= 13 min => 2 (Good)
     * > 13 min  => 3 (Fighting)
     */
    public function calculatedSkillGrade(): ?int
    {
        $mins = $this->durationMinutes();
        if (is_null($mins)) {
            return null;
        }

        if ($mins <= 10) {
            return 1;
        }

        if ($mins <= 13) {
            return 2;
        }

        return 3;
    }

    public function skillGradeLabel(): ?string
    {
        $grade = $this->calculatedSkillGrade();
        return match ($grade) {
            1 => 'Excellent',
            2 => 'Good',
            3 => 'Fighting',
            default => null,
        };
    }
}
