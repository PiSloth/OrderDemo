<?php

namespace App\Services\Kpi;

use App\Exceptions\KpiWorkbookImportException;
use App\Models\Kpi\KpiGroup;
use App\Models\Kpi\KpiHoliday;
use App\Models\Kpi\KpiTaskAssignment;
use App\Models\Kpi\KpiTaskCalendarControl;
use App\Models\Kpi\KpiTaskRule;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\Kpi\KpiTaskTemplateEvidenceField;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\SimpleExcel\SimpleExcelReader;
use Spatie\SimpleExcel\SimpleExcelWriter;

class KpiWorkbookService
{
    private const TASK_SHEET = 'tasks';
    private const HOLIDAY_SHEET = 'holidays';
    private const TABLE_FIELD_SHEET = 'table_fields';
    private const INSTRUCTIONS_SHEET = 'instructions';

    private const TASK_COLUMNS = [
        'employee_email',
        'kpi_group_name',
        'group_rule_type',
        'group_rule_target_value',
        'task_title',
        'task_description',
        'guideline',
        'frequency',
        'monthly_required_count',
        'cutoff_time',
        'rule_type',
        'rule_target_value',
        'evidence_type',
        'min_images',
        'max_images',
        'image_remark_required',
        'first_approver_email',
        'second_approver_email',
        'starts_on',
        'ends_on',
        'calendar_push_enabled',
        'daily_reminder_enabled',
        'reminder_start_time',
        'reminder_interval_minutes',
        'weekly_monthly_refresh_enabled',
        'weekly_monthly_refresh_time',
        'push_until_finalized',
        'group_is_active',
        'template_is_active',
        'assignment_is_active',
    ];

    private const HOLIDAY_COLUMNS = [
        'employee_email',
        'holiday_date',
        'holiday_name',
        'remark',
        'is_active',
    ];

    private const TABLE_FIELD_COLUMNS = [
        'employee_email',
        'kpi_group_name',
        'task_title',
        'field_key',
        'field_label',
        'field_type',
        'is_required',
        'select_options',
        'unit_options',
        'sort_order',
    ];

    public function __construct(
        protected KpiTaskInstanceGenerator $generator,
        protected KpiAvailabilityService $availability,
    ) {
    }

    public function createBlankTemplateWorkbook(User $actor): string
    {
        Gate::forUser($actor)->authorize('kpiManageImports');

        $path = $this->makeTempFilePath('kpi-import-template', 'xlsx');
        $writer = SimpleExcelWriter::create($path);

        $this->writeInstructionsSheet($writer);
        $this->writeTasksSheet($writer, collect([$this->sampleTaskRow()]));
        $this->writeHolidaysSheet($writer, collect([$this->sampleHolidayRow()]));
        $this->writeTableFieldsSheet($writer, collect([$this->sampleTableFieldRow()]));

        $writer->close();

        return $path;
    }

    public function createEmployeeExportWorkbook(User $actor, User $employee): string
    {
        Gate::forUser($actor)->authorize('kpiManageImports');
        $this->authorizeEmployeeScope($actor, $employee);

        $assignments = KpiTaskAssignment::query()
            ->with([
                'template.group',
                'template.rule',
                'template.evidenceFields',
                'calendarControl',
                'firstApprover',
                'finalApprover',
            ])
            ->where('user_id', $employee->id)
            ->get()
            ->sortBy(fn (KpiTaskAssignment $assignment) => sprintf(
                '%s|%s',
                (string) optional($assignment->template?->group)->name,
                (string) optional($assignment->template)->title
            ))
            ->values();

        $holidays = KpiHoliday::query()
            ->where('user_id', $employee->id)
            ->orderBy('holiday_date')
            ->get();

        $taskRows = $assignments->map(fn (KpiTaskAssignment $assignment) => $this->mapAssignmentToTaskRow($employee, $assignment));
        $tableFieldRows = $assignments
            ->flatMap(function (KpiTaskAssignment $assignment) use ($employee): Collection {
                return $assignment->template?->evidenceFields?->sortBy('sort_order')
                    ->values()
                    ->map(fn (KpiTaskTemplateEvidenceField $field) => $this->mapEvidenceFieldToRow($employee, $assignment->template, $field))
                    ?? collect();
            })
            ->values();
        $holidayRows = $holidays->map(fn (KpiHoliday $holiday) => $this->mapHolidayToRow($employee, $holiday));

        $path = $this->makeTempFilePath('kpi-employee-export', 'xlsx');
        $writer = SimpleExcelWriter::create($path);

        $this->writeInstructionsSheet($writer);
        $this->writeTasksSheet($writer, $taskRows);
        $this->writeHolidaysSheet($writer, $holidayRows);
        $this->writeTableFieldsSheet($writer, $tableFieldRows);

        $writer->close();

        return $path;
    }

    public function importWorkbook(User $actor, string $path): array
    {
        Gate::forUser($actor)->authorize('kpiManageImports');

        $taskSheet = $this->readSheet($path, self::TASK_SHEET, true);
        $holidaySheet = $this->readSheet($path, self::HOLIDAY_SHEET, false);
        $tableFieldSheet = $this->readSheet($path, self::TABLE_FIELD_SHEET, false);

        $errors = [];

        if ($taskSheet['missing']) {
            $errors[] = [
                'sheet' => self::TASK_SHEET,
                'row' => 0,
                'message' => 'The tasks sheet is required.',
            ];
        }

        if (
            empty($taskSheet['rows'])
            && empty($holidaySheet['rows'])
            && empty($tableFieldSheet['rows'])
        ) {
            $errors[] = [
                'sheet' => self::TASK_SHEET,
                'row' => 0,
                'message' => 'The workbook does not contain any data rows.',
            ];
        }

        $fileEmployee = $this->resolveWorkbookEmployee(
            $actor,
            $taskSheet['rows'],
            $holidaySheet['rows'],
            $tableFieldSheet['rows'],
            $errors
        );

        if (!$fileEmployee) {
            throw new KpiWorkbookImportException(
                $errors,
                $this->createErrorReport($errors)
            );
        }

        $taskPayload = $this->validateTaskRows(
            $fileEmployee,
            $taskSheet['rows'],
            $tableFieldSheet['rows'],
            $errors
        );

        $holidayPayload = $this->validateHolidayRows($fileEmployee, $holidaySheet['rows'], $errors);
        $tableFieldPayload = $this->validateTableFieldRows($fileEmployee, $tableFieldSheet['rows'], $taskPayload, $errors);

        if ($errors !== []) {
            throw new KpiWorkbookImportException(
                $errors,
                $this->createErrorReport($errors)
            );
        }

        return DB::transaction(function () use ($actor, $fileEmployee, $taskPayload, $holidayPayload, $tableFieldPayload): array {
            $summary = [
                'employee' => $fileEmployee->name,
                'group_count' => 0,
                'template_count' => 0,
                'assignment_count' => 0,
                'holiday_count' => 0,
                'table_field_count' => 0,
            ];

            $templateMap = [];

            foreach ($taskPayload as $taskRow) {
                $group = $this->upsertGroupForEmployee($fileEmployee, $taskRow);
                $template = $this->upsertTemplate($actor, $group, $taskRow);
                $assignment = $this->upsertAssignment($fileEmployee, $template, $taskRow);

                $this->generator->generateForAssignment($assignment->fresh(['template.group']));

                $templateKey = $this->templateMapKey($group->name, $template->title);
                $templateMap[$templateKey] = $template;

                $summary['group_count']++;
                $summary['template_count']++;
                $summary['assignment_count']++;
            }

            foreach ($tableFieldPayload as $templateKey => $fieldRows) {
                $template = $templateMap[$templateKey] ?? $this->findTemplateForEmployeeScope(
                    $fileEmployee,
                    $fieldRows[0]['kpi_group_name'],
                    $fieldRows[0]['task_title']
                );

                if (!$template) {
                    continue;
                }

                $template->evidenceFields()->delete();

                foreach ($fieldRows as $fieldRow) {
                    $template->evidenceFields()->create([
                        'field_key' => $fieldRow['field_key'],
                        'label' => $fieldRow['field_label'],
                        'field_type' => $fieldRow['field_type'],
                        'is_required' => $fieldRow['is_required'],
                        'select_options' => $fieldRow['select_options'],
                        'unit_options' => $fieldRow['unit_options'],
                        'sort_order' => $fieldRow['sort_order'],
                    ]);

                    $summary['table_field_count']++;
                }
            }

            foreach ($holidayPayload as $holidayRow) {
                $holiday = KpiHoliday::query()->updateOrCreate(
                    [
                        'user_id' => $fileEmployee->id,
                        'holiday_date' => $holidayRow['holiday_date'],
                    ],
                    [
                        'name' => $holidayRow['holiday_name'],
                        'remark' => $holidayRow['remark'],
                        'is_active' => $holidayRow['is_active'],
                    ]
                );

                if ($holiday->is_active) {
                    $this->availability->applyHoliday($holiday);
                }

                $summary['holiday_count']++;
            }

            return $summary;
        });
    }

    public function errorReportPath(string $file): string
    {
        return storage_path('app/kpi-import-errors/' . basename($file));
    }

    protected function readSheet(string $path, string $sheet, bool $required): array
    {
        try {
            $rows = SimpleExcelReader::create($path, 'xlsx')
                ->fromSheetName($sheet)
                ->getRows()
                ->values()
                ->map(function (array $row, int $index): array {
                    return [
                        'row_number' => $index + 2,
                        'values' => $this->normalizeRowKeys($row),
                    ];
                })
                ->filter(fn (array $row) => $this->rowHasContent($row['values']))
                ->values()
                ->all();

            return [
                'missing' => false,
                'rows' => $rows,
            ];
        } catch (InvalidArgumentException) {
            return [
                'missing' => $required,
                'rows' => [],
            ];
        }
    }

    protected function normalizeRowKeys(array $row): array
    {
        $normalized = [];

        foreach ($row as $key => $value) {
            $normalizedKey = Str::of((string) $key)
                ->trim()
                ->lower()
                ->replace([' ', '-'], '_')
                ->replace('__', '_')
                ->value();

            $normalized[$normalizedKey] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    protected function rowHasContent(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && $value !== '') {
                return true;
            }
        }

        return false;
    }

    protected function resolveWorkbookEmployee(
        User $actor,
        array $taskRows,
        array $holidayRows,
        array $tableFieldRows,
        array &$errors
    ): ?User {
        $resolvedEmployees = collect();

        foreach ([self::TASK_SHEET => $taskRows, self::HOLIDAY_SHEET => $holidayRows, self::TABLE_FIELD_SHEET => $tableFieldRows] as $sheet => $rows) {
            foreach ($rows as $row) {
                $values = $row['values'];
                $employeeEmail = $this->nullableString($values['employee_email'] ?? null);

                if (!$employeeEmail) {
                    continue;
                }

                $employee = $this->resolveEmployeeReference($employeeEmail);

                if (!$employee) {
                    $errors[] = [
                        'sheet' => $sheet,
                        'row' => $row['row_number'],
                        'message' => 'Employee could not be matched by employee_email.',
                    ];
                    continue;
                }

                $resolvedEmployees->push($employee);
            }
        }

        $resolvedEmployees = $resolvedEmployees->unique('id')->values();

        if ($resolvedEmployees->count() > 1) {
            $errors[] = [
                'sheet' => self::TASK_SHEET,
                'row' => 0,
                'message' => 'The workbook contains more than one employee. Use one file per employee.',
            ];

            return null;
        }

        $employee = $resolvedEmployees->first();

        if (!$employee) {
            $errors[] = [
                'sheet' => self::TASK_SHEET,
                'row' => 0,
                'message' => 'At least one employee reference is required in the workbook.',
            ];

            return null;
        }

        try {
            $this->authorizeEmployeeScope($actor, $employee);
        } catch (AuthorizationException $exception) {
            $errors[] = [
                'sheet' => self::TASK_SHEET,
                'row' => 0,
                'message' => $exception->getMessage(),
            ];

            return null;
        }

        return $employee;
    }

    protected function validateTaskRows(User $employee, array $taskRows, array $tableFieldRows, array &$errors): array
    {
        $validated = [];
        $tableFieldKeys = collect($tableFieldRows)
            ->map(fn (array $row) => $this->templateMapKey(
                (string) ($row['values']['kpi_group_name'] ?? ''),
                (string) ($row['values']['task_title'] ?? '')
            ))
            ->filter()
            ->unique()
            ->all();

        foreach ($taskRows as $row) {
            $values = $row['values'];
            $groupName = $this->nullableString($values['kpi_group_name'] ?? null);
            $groupRuleType = strtolower((string) ($values['group_rule_type'] ?? ''));
            $taskTitle = $this->nullableString($values['task_title'] ?? null);
            $frequency = strtolower((string) ($values['frequency'] ?? ''));
            $ruleType = strtolower((string) ($values['rule_type'] ?? ''));
            $evidenceType = strtolower((string) ($values['evidence_type'] ?? ''));
            $firstApproverEmail = $this->nullableString($values['first_approver_email'] ?? null);
            $secondApproverEmail = $this->nullableString($values['second_approver_email'] ?? null);

            if (!$groupName) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'kpi_group_name is required.');
            }

            if (!$taskTitle) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'task_title is required.');
            }

            if (!in_array($groupRuleType, [
                KpiTaskRule::TYPE_PASS_PERCENTAGE,
                KpiTaskRule::TYPE_FAIL_COUNT,
                KpiTaskRule::TYPE_SPEND_COST_LTE,
            ], true)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'group_rule_type must be pass_percentage, fail_count, or spend_cost_lte.');
            }

            if (!in_array($frequency, ['daily', 'weekly', 'monthly'], true)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'frequency must be daily, weekly, or monthly.');
            }

            if (!in_array($ruleType, [
                KpiTaskRule::TYPE_PASS_PERCENTAGE,
                KpiTaskRule::TYPE_FAIL_COUNT,
                KpiTaskRule::TYPE_SPEND_COST_LTE,
            ], true)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'rule_type must be pass_percentage, fail_count, or spend_cost_lte.');
            }

            if (!in_array($evidenceType, ['image', 'table', 'both'], true)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'evidence_type must be image, table, or both.');
            }

            $monthlyRequiredCount = $this->integerValue($values['monthly_required_count'] ?? null, 1);
            if ($frequency === 'monthly' && ($monthlyRequiredCount < 1 || $monthlyRequiredCount > 31)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'monthly_required_count must be between 1 and 31.');
            }

            $cutoffTime = $this->normalizeTime($values['cutoff_time'] ?? null);
            if (($values['cutoff_time'] ?? '') !== '' && !$cutoffTime) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'cutoff_time must use HH:MM format.');
            }

            $ruleTarget = $this->nullableNumeric($values['rule_target_value'] ?? null);
            $groupRuleTarget = $this->nullableNumeric($values['group_rule_target_value'] ?? null);

            if ($groupRuleType === KpiTaskRule::TYPE_PASS_PERCENTAGE && ($groupRuleTarget === null || $groupRuleTarget < 0 || $groupRuleTarget > 100)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'group_rule_target_value must be a percentage between 0 and 100.');
            }
            if ($groupRuleType === KpiTaskRule::TYPE_FAIL_COUNT && ($groupRuleTarget === null || floor($groupRuleTarget) !== $groupRuleTarget || $groupRuleTarget < 0)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'group_rule_target_value must be a whole number for fail_count.');
            }
            if ($groupRuleType === KpiTaskRule::TYPE_SPEND_COST_LTE && ($groupRuleTarget === null || $groupRuleTarget < 0)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'group_rule_target_value must be zero or greater for spend_cost_lte.');
            }

            if ($ruleType === KpiTaskRule::TYPE_PASS_PERCENTAGE && ($ruleTarget === null || $ruleTarget < 0 || $ruleTarget > 100)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'rule_target_value must be a percentage between 0 and 100.');
            }
            if ($ruleType === KpiTaskRule::TYPE_FAIL_COUNT && ($ruleTarget === null || floor($ruleTarget) !== $ruleTarget || $ruleTarget < 0)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'rule_target_value must be a whole number for fail_count.');
            }
            if ($ruleType === KpiTaskRule::TYPE_SPEND_COST_LTE && ($ruleTarget === null || $ruleTarget < 0)) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'rule_target_value must be zero or greater for spend_cost_lte.');
            }

            $minImages = $this->integerValue($values['min_images'] ?? null, 0);
            $maxImages = $this->nullableInteger($values['max_images'] ?? null);
            if (in_array($evidenceType, ['image', 'both'], true)) {
                if ($minImages < 0) {
                    $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'min_images cannot be negative.');
                }
                if ($maxImages !== null && $maxImages < $minImages) {
                    $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'max_images must be greater than or equal to min_images.');
                }
            } else {
                $minImages = 0;
                $maxImages = null;
            }

            if (!$firstApproverEmail) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'first_approver_email is required.');
            }

            $firstApprover = $firstApproverEmail ? $this->resolveApproverByEmail($firstApproverEmail, $employee) : null;
            if ($firstApproverEmail && !$firstApprover) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'first_approver_email could not be matched to one active user in scope.');
            }

            $secondApprover = null;
            if ($secondApproverEmail) {
                $secondApprover = $this->resolveApproverByEmail($secondApproverEmail, $employee);

                if (!$secondApprover) {
                    $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'second_approver_email could not be matched to one active user in scope.');
                }
            }

            if ($firstApprover && $firstApprover->id === $employee->id) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'first approver cannot be the same employee as the assignee.');
            }

            if ($secondApprover && $secondApprover->id === $employee->id) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'second approver cannot be the same employee as the assignee.');
            }

            if ($firstApprover && $secondApprover && $firstApprover->id === $secondApprover->id) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'second approver must be different from the first approver.');
            }

            $startsOn = $this->normalizeDate($values['starts_on'] ?? null);
            $endsOn = $this->normalizeDate($values['ends_on'] ?? null);

            if (($values['starts_on'] ?? '') !== '' && !$startsOn) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'starts_on must use YYYY-MM-DD format.');
            }
            if (($values['ends_on'] ?? '') !== '' && !$endsOn) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'ends_on must use YYYY-MM-DD format.');
            }
            if ($startsOn && $endsOn && $endsOn < $startsOn) {
                $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'ends_on must be on or after starts_on.');
            }

            $tableTemplateKey = $this->templateMapKey((string) $groupName, (string) $taskTitle);
            if (in_array($evidenceType, ['table', 'both'], true) && !in_array($tableTemplateKey, $tableFieldKeys, true)) {
                $existingTemplate = $groupName && $taskTitle
                    ? $this->findTemplateForEmployeeScope($employee, $groupName, $taskTitle)
                    : null;

                if (!$existingTemplate || $existingTemplate->evidenceFields()->count() === 0) {
                    $errors[] = $this->sheetError(self::TASK_SHEET, $row['row_number'], 'table_fields rows are required for new templates that use table evidence.');
                }
            }

            $validated[] = [
                'row_number' => $row['row_number'],
                'employee_email' => $employee->email,
                'kpi_group_name' => $groupName,
                'group_rule_type' => $groupRuleType ?: KpiTaskRule::TYPE_PASS_PERCENTAGE,
                'group_rule_target_value' => $groupRuleTarget,
                'task_title' => $taskTitle,
                'task_description' => $this->nullableString($values['task_description'] ?? null),
                'guideline' => $this->nullableString($values['guideline'] ?? null),
                'frequency' => $frequency ?: 'daily',
                'monthly_required_count' => $monthlyRequiredCount,
                'cutoff_time' => $cutoffTime,
                'rule_type' => $ruleType ?: KpiTaskRule::TYPE_PASS_PERCENTAGE,
                'rule_target_value' => $ruleTarget,
                'evidence_type' => $evidenceType ?: 'image',
                'min_images' => $minImages,
                'max_images' => $maxImages,
                'image_remark_required' => $this->booleanValue($values['image_remark_required'] ?? null, false),
                'first_approver_user_id' => $firstApprover?->id,
                'second_approver_user_id' => $secondApprover?->id,
                'starts_on' => $startsOn,
                'ends_on' => $endsOn,
                'calendar_push_enabled' => $this->booleanValue($values['calendar_push_enabled'] ?? null, true),
                'daily_reminder_enabled' => $this->booleanValue($values['daily_reminder_enabled'] ?? null, true),
                'reminder_start_time' => $this->normalizeTime($values['reminder_start_time'] ?? null) ?? '08:45:00',
                'reminder_interval_minutes' => $this->integerValue($values['reminder_interval_minutes'] ?? null, 60),
                'weekly_monthly_refresh_enabled' => $this->booleanValue($values['weekly_monthly_refresh_enabled'] ?? null, true),
                'weekly_monthly_refresh_time' => $this->normalizeTime($values['weekly_monthly_refresh_time'] ?? null) ?? '09:15:00',
                'push_until_finalized' => $this->booleanValue($values['push_until_finalized'] ?? null, true),
                'group_is_active' => $this->booleanValue($values['group_is_active'] ?? null, true),
                'template_is_active' => $this->booleanValue($values['template_is_active'] ?? null, true),
                'assignment_is_active' => $this->booleanValue($values['assignment_is_active'] ?? null, true),
            ];
        }

        $groupConfigurations = [];

        foreach ($validated as $taskRow) {
            $groupKey = Str::lower((string) $taskRow['kpi_group_name']);
            $currentConfig = [
                'group_rule_type' => $taskRow['group_rule_type'],
                'group_rule_target_value' => $taskRow['group_rule_target_value'],
            ];

            if (!isset($groupConfigurations[$groupKey])) {
                $groupConfigurations[$groupKey] = $currentConfig;
                continue;
            }

            if ($groupConfigurations[$groupKey] !== $currentConfig) {
                $errors[] = $this->sheetError(
                    self::TASK_SHEET,
                    $taskRow['row_number'],
                    'All rows for the same KPI group must use the same group rule type and group rule target value.'
                );
            }
        }

        return $validated;
    }

    protected function validateHolidayRows(User $employee, array $holidayRows, array &$errors): array
    {
        $validated = [];

        foreach ($holidayRows as $row) {
            $values = $row['values'];
            $holidayDate = $this->normalizeDate($values['holiday_date'] ?? null);
            $holidayName = $this->nullableString($values['holiday_name'] ?? null);

            if (!$holidayDate) {
                $errors[] = $this->sheetError(self::HOLIDAY_SHEET, $row['row_number'], 'holiday_date is required and must use YYYY-MM-DD format.');
            }

            if (!$holidayName) {
                $errors[] = $this->sheetError(self::HOLIDAY_SHEET, $row['row_number'], 'holiday_name is required.');
            }

            $validated[] = [
                'row_number' => $row['row_number'],
                'employee_email' => $employee->email,
                'holiday_date' => $holidayDate,
                'holiday_name' => $holidayName,
                'remark' => $this->nullableString($values['remark'] ?? null),
                'is_active' => $this->booleanValue($values['is_active'] ?? null, true),
            ];
        }

        return $validated;
    }

    protected function validateTableFieldRows(User $employee, array $tableFieldRows, array $taskPayload, array &$errors): array
    {
        $validated = [];
        $taskKeys = collect($taskPayload)
            ->map(fn (array $taskRow) => $this->templateMapKey($taskRow['kpi_group_name'], $taskRow['task_title']))
            ->all();

        foreach ($tableFieldRows as $row) {
            $values = $row['values'];
            $groupName = $this->nullableString($values['kpi_group_name'] ?? null);
            $taskTitle = $this->nullableString($values['task_title'] ?? null);
            $fieldLabel = $this->nullableString($values['field_label'] ?? null);
            $fieldType = strtolower((string) ($values['field_type'] ?? ''));

            if (!$groupName) {
                $errors[] = $this->sheetError(self::TABLE_FIELD_SHEET, $row['row_number'], 'kpi_group_name is required.');
            }

            if (!$taskTitle) {
                $errors[] = $this->sheetError(self::TABLE_FIELD_SHEET, $row['row_number'], 'task_title is required.');
            }

            if (!$fieldLabel) {
                $errors[] = $this->sheetError(self::TABLE_FIELD_SHEET, $row['row_number'], 'field_label is required.');
            }

            if (!in_array($fieldType, ['text', 'number', 'uom', 'date', 'select'], true)) {
                $errors[] = $this->sheetError(self::TABLE_FIELD_SHEET, $row['row_number'], 'field_type must be text, number, uom, date, or select.');
            }

            $templateKey = $this->templateMapKey((string) $groupName, (string) $taskTitle);

            if (!in_array($templateKey, $taskKeys, true) && !$this->findTemplateForEmployeeScope($employee, (string) $groupName, (string) $taskTitle)) {
                $errors[] = $this->sheetError(self::TABLE_FIELD_SHEET, $row['row_number'], 'This table field does not match a task in the tasks sheet or an existing template for the employee scope.');
            }

            $validated[$templateKey][] = [
                'row_number' => $row['row_number'],
                'employee_email' => $employee->email,
                'kpi_group_name' => $groupName,
                'task_title' => $taskTitle,
                'field_key' => $this->makeFieldKey($values['field_key'] ?? null, (string) $fieldLabel),
                'field_label' => $fieldLabel,
                'field_type' => $fieldType,
                'is_required' => $this->booleanValue($values['is_required'] ?? null, false),
                'select_options' => $this->splitList($values['select_options'] ?? null),
                'unit_options' => $this->splitList($values['unit_options'] ?? null),
                'sort_order' => $this->integerValue($values['sort_order'] ?? null, 0),
            ];
        }

        foreach ($validated as $templateKey => &$rows) {
            usort($rows, fn (array $left, array $right) => $left['sort_order'] <=> $right['sort_order']);
        }

        return $validated;
    }

    protected function upsertGroupForEmployee(User $employee, array $row): KpiGroup
    {
        $group = KpiGroup::withTrashed()
            ->where('name', $row['kpi_group_name'])
            ->where('department_id', $employee->department_id)
            ->first();

        if (!$group) {
            $group = new KpiGroup([
                'name' => $row['kpi_group_name'],
                'department_id' => $employee->department_id,
            ]);
        }

        if ($group->trashed()) {
            $group->restore();
        }

        $group->fill([
            'name' => $row['kpi_group_name'],
            'department_id' => $employee->department_id,
            'rule_type' => $row['group_rule_type'],
            'target_percentage' => $row['group_rule_type'] === KpiTaskRule::TYPE_PASS_PERCENTAGE ? $row['group_rule_target_value'] : null,
            'max_fail_count' => $row['group_rule_type'] === KpiTaskRule::TYPE_FAIL_COUNT ? (int) $row['group_rule_target_value'] : null,
            'max_cost_amount' => $row['group_rule_type'] === KpiTaskRule::TYPE_SPEND_COST_LTE ? $row['group_rule_target_value'] : null,
            'is_active' => $row['group_is_active'],
        ]);
        $group->save();

        return $group;
    }

    protected function upsertTemplate(User $actor, KpiGroup $group, array $row): KpiTaskTemplate
    {
        $template = KpiTaskTemplate::withTrashed()
            ->where('kpi_group_id', $group->id)
            ->where('title', $row['task_title'])
            ->first();

        if (!$template) {
            $template = new KpiTaskTemplate([
                'kpi_group_id' => $group->id,
                'title' => $row['task_title'],
                'slug' => $this->makeUniqueTemplateSlug($row['task_title']),
                'created_by_user_id' => $actor->id,
            ]);
        }

        if ($template->trashed()) {
            $template->restore();
        }

        $template->fill([
            'kpi_group_id' => $group->id,
            'title' => $row['task_title'],
            'slug' => $this->makeUniqueTemplateSlug($row['task_title'], $template->id ?: null),
            'description' => $row['task_description'],
            'guideline' => $row['guideline'],
            'frequency' => $row['frequency'],
            'monthly_required_count' => $row['monthly_required_count'],
            'cutoff_time' => $row['cutoff_time'],
            'reminder_start_time' => '08:45:00',
            'requires_images' => in_array($row['evidence_type'], ['image', 'both'], true),
            'requires_table' => in_array($row['evidence_type'], ['table', 'both'], true),
            'min_images' => $row['min_images'],
            'max_images' => $row['max_images'],
            'image_remark_required' => $row['image_remark_required'],
            'is_active' => $row['template_is_active'],
        ]);
        $template->save();

        $template->rule()->updateOrCreate(
            ['task_template_id' => $template->id],
            [
                'rule_type' => $row['rule_type'],
                'target_percentage' => $row['rule_type'] === KpiTaskRule::TYPE_PASS_PERCENTAGE ? $row['rule_target_value'] : null,
                'max_fail_count' => $row['rule_type'] === KpiTaskRule::TYPE_FAIL_COUNT ? (int) $row['rule_target_value'] : null,
                'max_cost_amount' => $row['rule_type'] === KpiTaskRule::TYPE_SPEND_COST_LTE ? $row['rule_target_value'] : null,
            ]
        );

        return $template;
    }

    protected function upsertAssignment(User $employee, KpiTaskTemplate $template, array $row): KpiTaskAssignment
    {
        $assignment = KpiTaskAssignment::query()->firstOrNew([
            'task_template_id' => $template->id,
            'user_id' => $employee->id,
        ]);

        $assignment->fill([
            'first_approver_user_id' => $row['first_approver_user_id'],
            'final_approver_user_id' => $row['second_approver_user_id'],
            'assignment_source' => 'manual',
            'starts_on' => $row['starts_on'],
            'ends_on' => $row['ends_on'],
            'is_active' => $row['assignment_is_active'],
            'calendar_push_enabled' => $row['calendar_push_enabled'],
        ]);
        $assignment->save();

        KpiTaskCalendarControl::query()->updateOrCreate(
            ['task_assignment_id' => $assignment->id],
            [
                'daily_reminder_enabled' => $row['daily_reminder_enabled'],
                'reminder_start_time' => $row['reminder_start_time'],
                'reminder_interval_minutes' => $row['reminder_interval_minutes'],
                'weekly_monthly_refresh_enabled' => $row['weekly_monthly_refresh_enabled'],
                'weekly_monthly_refresh_time' => $row['weekly_monthly_refresh_time'],
                'push_until_finalized' => $row['push_until_finalized'],
            ]
        );

        return $assignment;
    }

    protected function resolveEmployeeReference(?string $employeeEmail): ?User
    {
        return User::query()
            ->where('suspended', false)
            ->when($employeeEmail, fn (Builder $query) => $query->where('email', $employeeEmail))
            ->first();
    }

    protected function resolveApproverByEmail(string $email, User $employee): ?User
    {
        return User::query()
            ->where('suspended', false)
            ->whereRaw('LOWER(email) = ?', [Str::lower($email)])
            ->when(
                $employee->department_id,
                fn (Builder $query) => $query->where('department_id', $employee->department_id)
            )
            ->first();
    }

    protected function authorizeEmployeeScope(User $actor, User $employee): void
    {
        if (Str::lower((string) optional($actor->position)->name) === 'super admin') {
            return;
        }

        if (!$actor->department_id || $actor->department_id !== $employee->department_id) {
            throw new AuthorizationException('You can only import or export KPI workbooks for employees in your own department.');
        }
    }

    protected function findTemplateForEmployeeScope(User $employee, string $groupName, string $taskTitle): ?KpiTaskTemplate
    {
        return KpiTaskTemplate::query()
            ->with('evidenceFields')
            ->where('title', $taskTitle)
            ->whereHas('group', function (Builder $query) use ($employee, $groupName): void {
                $query->where('name', $groupName)
                    ->where('department_id', $employee->department_id);
            })
            ->first();
    }

    protected function writeInstructionsSheet(SimpleExcelWriter $writer): void
    {
        $writer->nameCurrentSheet(self::INSTRUCTIONS_SHEET);
        $writer->addRow([
            'sheet' => 'tasks',
            'details' => 'One row equals one task assignment for one employee. This workbook must contain only one employee.',
        ]);
        $writer->addRow([
            'sheet' => 'tasks',
            'details' => 'Required columns: employee_email, kpi_group_name, group_rule_type, group_rule_target_value, task_title, frequency, rule_type, rule_target_value, evidence_type, first_approver_email.',
        ]);
        $writer->addRow([
            'sheet' => 'tasks',
            'details' => 'second_approver_email is optional. Use the exact active user email address for each approver.',
        ]);
        $writer->addRow([
            'sheet' => 'tasks',
            'details' => 'Repeat the same group_rule_type and group_rule_target_value for every task row that belongs to the same KPI group.',
        ]);
        $writer->addRow([
            'sheet' => 'tasks',
            'details' => 'evidence_type values: image, table, both. rule_type values: pass_percentage, fail_count, spend_cost_lte.',
        ]);
        $writer->addRow([
            'sheet' => 'holidays',
            'details' => 'Use the holidays sheet for employee-specific holiday dates. Holiday dates are excluded in audit.',
        ]);
        $writer->addRow([
            'sheet' => 'table_fields',
            'details' => 'Use table_fields when a template uses table or both evidence. Separate select_options and unit_options with the | character.',
        ]);
        $writer->addRow([
            'sheet' => 'general',
            'details' => 'Import stops for the whole workbook if any row fails validation. A CSV error report is generated with sheet, row, and message.',
        ]);
        $writer->addRow([
            'sheet' => 'general',
            'details' => 'The blank workbook includes sample rows. Replace those sample values with your real employee, task, and holiday data before import.',
        ]);
    }

    protected function writeTasksSheet(SimpleExcelWriter $writer, Collection $rows): void
    {
        $writer->addNewSheetAndMakeItCurrent(self::TASK_SHEET);
        $writer->addHeader(self::TASK_COLUMNS);

        foreach ($rows as $row) {
            $writer->addRow(Arr::only($row, self::TASK_COLUMNS));
        }
    }

    protected function writeHolidaysSheet(SimpleExcelWriter $writer, Collection $rows): void
    {
        $writer->addNewSheetAndMakeItCurrent(self::HOLIDAY_SHEET);
        $writer->addHeader(self::HOLIDAY_COLUMNS);

        foreach ($rows as $row) {
            $writer->addRow(Arr::only($row, self::HOLIDAY_COLUMNS));
        }
    }

    protected function writeTableFieldsSheet(SimpleExcelWriter $writer, Collection $rows): void
    {
        $writer->addNewSheetAndMakeItCurrent(self::TABLE_FIELD_SHEET);
        $writer->addHeader(self::TABLE_FIELD_COLUMNS);

        foreach ($rows as $row) {
            $writer->addRow(Arr::only($row, self::TABLE_FIELD_COLUMNS));
        }
    }

    protected function mapAssignmentToTaskRow(User $employee, KpiTaskAssignment $assignment): array
    {
        $template = $assignment->template;
        $rule = $template?->rule;
        $calendarControl = $assignment->calendarControl;

        return [
            'employee_email' => $employee->email,
            'kpi_group_name' => $template?->group?->name,
            'group_rule_type' => $template?->group?->rule_type,
            'group_rule_target_value' => match ($template?->group?->rule_type) {
                KpiTaskRule::TYPE_PASS_PERCENTAGE => $template?->group?->target_percentage,
                KpiTaskRule::TYPE_FAIL_COUNT => $template?->group?->max_fail_count,
                KpiTaskRule::TYPE_SPEND_COST_LTE => $template?->group?->max_cost_amount,
                default => '',
            },
            'task_title' => $template?->title,
            'task_description' => $template?->description,
            'guideline' => $template?->guideline,
            'frequency' => $template?->frequency,
            'monthly_required_count' => $template?->monthly_required_count,
            'cutoff_time' => $template?->cutoff_time ? substr((string) $template->cutoff_time, 0, 5) : '',
            'rule_type' => $rule?->rule_type,
            'rule_target_value' => match ($rule?->rule_type) {
                KpiTaskRule::TYPE_PASS_PERCENTAGE => $rule?->target_percentage,
                KpiTaskRule::TYPE_FAIL_COUNT => $rule?->max_fail_count,
                KpiTaskRule::TYPE_SPEND_COST_LTE => $rule?->max_cost_amount,
                default => '',
            },
            'evidence_type' => $template?->requires_images && $template?->requires_table ? 'both' : ($template?->requires_table ? 'table' : 'image'),
            'min_images' => $template?->min_images ?? 0,
            'max_images' => $template?->max_images,
            'image_remark_required' => $this->booleanForSheet((bool) $template?->image_remark_required),
            'first_approver_email' => $assignment->firstApprover?->email,
            'second_approver_email' => $assignment->finalApprover?->email,
            'starts_on' => $assignment->starts_on?->toDateString(),
            'ends_on' => $assignment->ends_on?->toDateString(),
            'calendar_push_enabled' => $this->booleanForSheet((bool) $assignment->calendar_push_enabled),
            'daily_reminder_enabled' => $this->booleanForSheet((bool) ($calendarControl?->daily_reminder_enabled ?? true)),
            'reminder_start_time' => substr((string) ($calendarControl?->reminder_start_time ?? '08:45:00'), 0, 5),
            'reminder_interval_minutes' => (int) ($calendarControl?->reminder_interval_minutes ?? 60),
            'weekly_monthly_refresh_enabled' => $this->booleanForSheet((bool) ($calendarControl?->weekly_monthly_refresh_enabled ?? true)),
            'weekly_monthly_refresh_time' => substr((string) ($calendarControl?->weekly_monthly_refresh_time ?? '09:15:00'), 0, 5),
            'push_until_finalized' => $this->booleanForSheet((bool) ($calendarControl?->push_until_finalized ?? true)),
            'group_is_active' => $this->booleanForSheet((bool) $template?->group?->is_active),
            'template_is_active' => $this->booleanForSheet((bool) $template?->is_active),
            'assignment_is_active' => $this->booleanForSheet((bool) $assignment->is_active),
        ];
    }

    protected function mapEvidenceFieldToRow(User $employee, ?KpiTaskTemplate $template, KpiTaskTemplateEvidenceField $field): array
    {
        return [
            'employee_email' => $employee->email,
            'kpi_group_name' => $template?->group?->name,
            'task_title' => $template?->title,
            'field_key' => $field->field_key,
            'field_label' => $field->label,
            'field_type' => $field->field_type,
            'is_required' => $this->booleanForSheet((bool) $field->is_required),
            'select_options' => implode('|', $field->select_options ?? []),
            'unit_options' => implode('|', $field->unit_options ?? []),
            'sort_order' => (int) $field->sort_order,
        ];
    }

    protected function mapHolidayToRow(User $employee, KpiHoliday $holiday): array
    {
        return [
            'employee_email' => $employee->email,
            'holiday_date' => $holiday->holiday_date?->toDateString(),
            'holiday_name' => $holiday->name,
            'remark' => $holiday->remark,
            'is_active' => $this->booleanForSheet((bool) $holiday->is_active),
        ];
    }

    protected function sampleTaskRow(): array
    {
        return [
            'employee_email' => 'employee@example.com',
            'kpi_group_name' => 'KPI 1',
            'group_rule_type' => 'pass_percentage',
            'group_rule_target_value' => 98,
            'task_title' => 'CCTV Check',
            'task_description' => 'Check CCTV status and confirm recording is normal.',
            'guideline' => 'Take at least two photos that show CCTV monitor and recording status.',
            'frequency' => 'daily',
            'monthly_required_count' => 1,
            'cutoff_time' => '10:00',
            'rule_type' => 'pass_percentage',
            'rule_target_value' => 97,
            'evidence_type' => 'image',
            'min_images' => 2,
            'max_images' => 4,
            'image_remark_required' => 'yes',
            'first_approver_email' => 'supervisor@example.com',
            'second_approver_email' => 'assistant.manager@example.com',
            'starts_on' => '2026-04-01',
            'ends_on' => '',
            'calendar_push_enabled' => 'yes',
            'daily_reminder_enabled' => 'yes',
            'reminder_start_time' => '08:45',
            'reminder_interval_minutes' => 60,
            'weekly_monthly_refresh_enabled' => 'yes',
            'weekly_monthly_refresh_time' => '09:15',
            'push_until_finalized' => 'yes',
            'group_is_active' => 'yes',
            'template_is_active' => 'yes',
            'assignment_is_active' => 'yes',
        ];
    }

    protected function sampleHolidayRow(): array
    {
        return [
            'employee_email' => 'employee@example.com',
            'holiday_date' => '2026-04-13',
            'holiday_name' => 'Thingyan Leave',
            'remark' => 'Sample holiday row. Replace with the real employee leave date.',
            'is_active' => 'yes',
        ];
    }

    protected function sampleTableFieldRow(): array
    {
        return [
            'employee_email' => 'employee@example.com',
            'kpi_group_name' => 'KPI 1',
            'task_title' => 'Maintenance Log',
            'field_key' => 'working_hours',
            'field_label' => 'Working Hours',
            'field_type' => 'number',
            'is_required' => 'yes',
            'select_options' => '',
            'unit_options' => 'hour|minute',
            'sort_order' => 1,
        ];
    }

    protected function booleanForSheet(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            $string = $value->format('Y-m-d H:i:s');
        } elseif (is_bool($value)) {
            $string = $value ? '1' : '0';
        } elseif (is_scalar($value) || $value instanceof \Stringable) {
            $string = trim((string) $value);
        } else {
            return null;
        }

        return $string !== '' ? $string : null;
    }

    protected function nullableNumeric(mixed $value): ?float
    {
        $value = $this->nullableString($value);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    protected function integerValue(mixed $value, int $default): int
    {
        $value = $this->nullableString($value);

        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    protected function nullableInteger(mixed $value): ?int
    {
        $value = $this->nullableString($value);

        if ($value === null || !is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    protected function booleanValue(mixed $value, bool $default): bool
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return $default;
        }

        return in_array(Str::lower($value), ['1', 'true', 'yes', 'y'], true);
    }

    protected function normalizeDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->toDateString();
        } catch (\Throwable) {
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeTime(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i:s');
        }

        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        foreach (['H:i', 'H:i:s'] as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function splitList(mixed $value): ?array
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        return collect(explode('|', $value))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->values()
            ->all();
    }

    protected function makeFieldKey(mixed $value, string $label): string
    {
        $explicit = $this->nullableString($value);

        if ($explicit) {
            return Str::snake($explicit);
        }

        return Str::snake($label);
    }

    protected function makeUniqueTemplateSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'kpi-task';
        $slug = $baseSlug;
        $counter = 2;

        while (
            KpiTaskTemplate::withTrashed()
                ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function templateMapKey(string $groupName, string $taskTitle): string
    {
        return Str::lower(trim($groupName)) . '|' . Str::lower(trim($taskTitle));
    }

    protected function sheetError(string $sheet, int $row, string $message): array
    {
        return [
            'sheet' => $sheet,
            'row' => $row,
            'message' => $message,
        ];
    }

    protected function createErrorReport(array $errors): string
    {
        $directory = storage_path('app/kpi-import-errors');
        File::ensureDirectoryExists($directory);

        $file = 'kpi-import-errors-' . Str::uuid() . '.csv';
        $path = $directory . DIRECTORY_SEPARATOR . $file;

        $writer = SimpleExcelWriter::create($path, 'csv');
        $writer->addHeader(['sheet', 'row', 'message']);

        foreach ($errors as $error) {
            $writer->addRow([
                'sheet' => $error['sheet'] ?? '',
                'row' => $error['row'] ?? '',
                'message' => $error['message'] ?? '',
            ]);
        }

        $writer->close();

        return $file;
    }

    protected function makeTempFilePath(string $prefix, string $extension): string
    {
        $directory = storage_path('app/kpi-import-exports');
        File::ensureDirectoryExists($directory);

        return $directory . DIRECTORY_SEPARATOR . $prefix . '-' . Str::uuid() . '.' . $extension;
    }
}
