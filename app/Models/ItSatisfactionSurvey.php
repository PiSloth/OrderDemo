<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItSatisfactionSurvey extends Model
{
    use HasFactory;

    protected $table = 'it_satisfaction_surveys';

    protected $fillable = [
        'title',
        'description',
        'badge_text',
        'start_date',
        'end_date',
        'is_active',
        'rating_scale',
        'is_mandatory',
        'target_scope',
        'criteria',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_mandatory' => 'boolean',
        'rating_scale' => 'integer',
        'target_scope' => 'array',
        'criteria' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(ItSatisfactionRating::class, 'survey_id');
    }

    /**
     * Check if a given user is eligible for this survey based on target_scope JSON rules.
     */
    public function isUserEligible($user): bool
    {
        if (!$user) {
            return false;
        }

        $scope = $this->target_scope ?? [];

        // 1. Excluded User IDs
        $excludedUsers = $scope['excluded_user_ids'] ?? [];
        if (!empty($excludedUsers) && in_array($user->id, $excludedUsers)) {
            return false;
        }

        // 2. Excluded Department IDs / Names
        $excludedDepts = $scope['excluded_department_ids'] ?? [];
        if (!empty($excludedDepts) && $user->department_id && in_array($user->department_id, $excludedDepts)) {
            return false;
        }

        $excludedDeptNames = $scope['excluded_department_names'] ?? [];
        if (!empty($excludedDeptNames) && $user->department) {
            foreach ($excludedDeptNames as $dName) {
                if (strcasecmp($user->department->name, $dName) === 0 || stripos($user->department->name, $dName) !== false) {
                    return false;
                }
            }
        }

        // 3. Excluded Office Position IDs
        $excludedPositions = $scope['excluded_office_position_ids'] ?? [];
        if (!empty($excludedPositions) && $user->office_position_id && in_array($user->office_position_id, $excludedPositions)) {
            return false;
        }

        // 4. Excluded Roles
        $excludedRoles = $scope['excluded_roles'] ?? [];
        if (!empty($excludedRoles) && $user->role && in_array(strtolower($user->role), array_map('strtolower', $excludedRoles))) {
            return false;
        }

        // 5. Target Departments (Whitelist if specified)
        $targetDepts = $scope['target_department_ids'] ?? [];
        if (!empty($targetDepts) && (!in_array($user->department_id, $targetDepts))) {
            return false;
        }

        // 6. Target Office Positions (Whitelist if specified)
        $targetPositions = $scope['target_office_position_ids'] ?? [];
        if (!empty($targetPositions) && (!in_array($user->office_position_id, $targetPositions))) {
            return false;
        }

        // 7. Target Users (Whitelist if specified)
        $targetUsers = $scope['target_user_ids'] ?? [];
        if (!empty($targetUsers) && (!in_array($user->id, $targetUsers))) {
            return false;
        }

        // 8. Default fallback if target_scope is empty: exclude IT department members
        if (empty($scope) && method_exists($user, 'isFromItDepartment') && $user->isFromItDepartment()) {
            return false;
        }

        return true;
    }

    /**
     * Scope to find surveys currently active and valid for today
     */
    public function scopeActiveNow($query, $date = null)
    {
        $today = $date ? Carbon::parse($date)->toDateString() : Carbon::today()->toDateString();
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }
}
