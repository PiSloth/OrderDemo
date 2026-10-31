<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'department_id' => $request->user()->department_id,
                    'department' => $request->user()->department ? [
                        'id' => $request->user()->department->id,
                        'name' => $request->user()->department->name,
                    ] : null,
                    'branch_id' => $request->user()->branch_id,
                    'branch' => $request->user()->branch ? [
                        'id' => $request->user()->branch->id,
                        'name' => $request->user()->branch->name,
                    ] : null,
                    'office_position' => $request->user()->officePosition ? [
                        'id' => $request->user()->officePosition->id,
                        'name' => $request->user()->officePosition->name,
                    ] : null,
                    'is_super_user' => method_exists($request->user(), 'isSuperUser') ? $request->user()->isSuperUser() : ($request->user()->role === 'admin'),
                    'is_it_department' => method_exists($request->user(), 'isFromItDepartment') ? $request->user()->isFromItDepartment() : false,
                ] : null,
                'can' => [
                    'kpiManageTemplates' => $request->user() ? $request->user()->can('kpiManageTemplates') : false,
                    'itSatisfactionView' => $request->user() ? (in_array(strtolower($request->user()->role ?? ''), ['super admin', 'super_admin', 'admin']) || $request->user()->can('it.satisfaction.view')) : false,
                    'itSatisfactionCreate' => $request->user() ? (in_array(strtolower($request->user()->role ?? ''), ['super admin', 'super_admin', 'admin']) || $request->user()->can('it.satisfaction.create')) : false,
                    'itSatisfactionUpdate' => $request->user() ? (in_array(strtolower($request->user()->role ?? ''), ['super admin', 'super_admin', 'admin']) || $request->user()->can('it.satisfaction.update')) : false,
                    'itSatisfactionDelete' => $request->user() ? (in_array(strtolower($request->user()->role ?? ''), ['super admin', 'super_admin', 'admin']) || $request->user()->can('it.satisfaction.delete')) : false,
                    'skillMatrixGlobal' => $request->user() ? ($request->user()->isSuperUser() || $request->user()->can('global-scope.view')) : false,
                    'skillMatrixDept' => $request->user() ? $request->user()->can('department-scope.view') : false,
                    'skillMatrixBranch' => $request->user() ? $request->user()->can('branch-scope.view') : false,
                    'skillMatrixView' => $request->user() ? (
                        $request->user()->isSuperUser()
                        || $request->user()->can('global-scope.view')
                        || $request->user()->can('department-scope.view')
                        || $request->user()->can('branch-scope.view')
                        || $request->user()->can('training.catalog.view')
                        || $request->user()->can('training.attempt.view')
                    ) : false,
                    'accountView' => $request->user() ? ($request->user()->isSuperUser() || $request->user()->isFromItDepartment() || $request->user()->isAdmin() || $request->user()->can('account.view') || $request->user()->can('digital_asset.account.view')) : false,
                    'accountCreate' => $request->user() ? ($request->user()->isSuperUser() || $request->user()->isFromItDepartment() || $request->user()->isAdmin() || $request->user()->can('account.create') || $request->user()->can('digital_asset.account.create')) : false,
                    'accountEdit' => $request->user() ? ($request->user()->isSuperUser() || $request->user()->isFromItDepartment() || $request->user()->isAdmin() || $request->user()->can('account.edit') || $request->user()->can('account.update') || $request->user()->can('digital_asset.account.update')) : false,
                    'accountDelete' => $request->user() ? ($request->user()->isSuperUser() || $request->user()->isFromItDepartment() || $request->user()->isAdmin() || $request->user()->can('account.delete') || $request->user()->can('digital_asset.account.delete') || $request->user()->can('digital_asset.account.retire')) : false,
                    'securityAuditTrailView' => $request->user() ? (
                        $request->user()->isSuperUser()
                        || $request->user()->isFromItDepartment()
                        || $request->user()->isAdmin()
                        || $request->user()->can('security_audit_trial.view')
                        || $request->user()->can('security_audit_trail.view')
                        || $request->user()->can('security-audit-trial.view')
                        || $request->user()->can('security-audit-trail.view')
                        || $request->user()->can('audit_trail.view')
                        || $request->user()->can('audit.view')
                        || $request->user()->can('digital_asset.audit.view')
                    ) : false,
                    'hardwareDeviceView' => $request->user() ? (
                        $request->user()->isSuperUser()
                        || $request->user()->isFromItDepartment()
                        || $request->user()->isAdmin()
                        || $request->user()->can('hardware_device_inventory.view')
                        || $request->user()->can('digital_asset.device.view')
                    ) : false,
                    'hardwareDeviceCreate' => $request->user() ? (
                        $request->user()->isSuperUser()
                        || $request->user()->isFromItDepartment()
                        || $request->user()->isAdmin()
                        || $request->user()->can('hardware_device_inventory.create')
                        || $request->user()->can('digital_asset.device.create')
                    ) : false,
                    'hardwareDeviceEdit' => $request->user() ? (
                        $request->user()->isSuperUser()
                        || $request->user()->isFromItDepartment()
                        || $request->user()->isAdmin()
                        || $request->user()->can('hardware_device_inventory.update')
                        || $request->user()->can('hardware_device_inventory.edit')
                        || $request->user()->can('digital_asset.device.update')
                        || $request->user()->can('digital_asset.device.edit')
                    ) : false,
                    'hardwareDeviceDelete' => $request->user() ? (
                        $request->user()->isSuperUser()
                        || $request->user()->isFromItDepartment()
                        || $request->user()->isAdmin()
                        || $request->user()->can('hardware_device_inventory.delete')
                        || $request->user()->can('digital_asset.device.delete')
                    ) : false,
                ],
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'itSatisfactionSurvey' => fn () => $request->user() ? self::getActiveSurveyForUser($request->user()) : null,
        ];
    }

    protected static function getActiveSurveyForUser($user): ?array
    {
        $activeSurvey = \App\Models\ItSatisfactionSurvey::activeNow()
            ->latest('id')
            ->first();

        if (!$activeSurvey || !$activeSurvey->isUserEligible($user)) {
            return null;
        }

        $hasSubmitted = \App\Models\ItSatisfactionRating::where('survey_id', $activeSurvey->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($hasSubmitted) {
            return null;
        }

        return [
            'id' => $activeSurvey->id,
            'title' => $activeSurvey->title,
            'description' => $activeSurvey->description,
            'badge_text' => $activeSurvey->badge_text,
            'start_date' => $activeSurvey->start_date?->format('Y-m-d'),
            'end_date' => $activeSurvey->end_date?->format('Y-m-d'),
            'start_date_formatted' => $activeSurvey->start_date?->format('d M Y'),
            'end_date_formatted' => $activeSurvey->end_date?->format('d M Y'),
            'rating_scale' => $activeSurvey->rating_scale,
            'is_mandatory' => $activeSurvey->is_mandatory,
            'criteria' => $activeSurvey->criteria ?? [
                ['key' => 'speed', 'label' => 'Support Response Speed'],
                ['key' => 'helpfulness', 'label' => 'Helpfulness & Communication'],
                ['key' => 'stability', 'label' => 'Network & System Stability'],
            ],
        ];
    }
}
