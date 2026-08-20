<?php

namespace App\IssueTracking\Services;

use App\IssueTracking\Models\Issue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SlaReportService
{
    public function __construct(
        protected SlaCalculationService $slaCalculationService
    ) {}

    /**
     * Generate Weekly or Monthly SLA Report.
     */
    public function generateReport(string $periodType = 'weekly', ?string $startDateStr = null, ?string $endDateStr = null, ?string $resolverType = 'all', array|string|null $categoryIds = null, array|string|null $statusCodes = null, array|string|null $departmentIds = null, ?string $quickFilter = 'all'): array
    {
        $now = now();

        if ($periodType === 'monthly') {
            $start = $startDateStr ? Carbon::parse($startDateStr)->startOfMonth() : $now->copy()->startOfMonth();
            $end = $endDateStr ? Carbon::parse($endDateStr)->endOfMonth() : $now->copy()->endOfMonth();
        } else { // weekly
            $start = $startDateStr ? Carbon::parse($startDateStr)->startOfWeek(Carbon::MONDAY) : $now->copy()->startOfWeek(Carbon::MONDAY);
            $end = $endDateStr ? Carbon::parse($endDateStr)->endOfWeek(Carbon::SUNDAY) : $now->copy()->endOfWeek(Carbon::SUNDAY);
        }

        $catIds = is_array($categoryIds) ? $categoryIds : ($categoryIds ? explode(',', $categoryIds) : []);
        $catIds = array_filter(array_map('intval', $catIds));

        $stCodes = is_array($statusCodes) ? $statusCodes : ($statusCodes ? explode(',', $statusCodes) : []);
        $stCodes = array_filter(array_map('trim', $stCodes));

        $deptIds = is_array($departmentIds) ? $departmentIds : ($departmentIds ? explode(',', $departmentIds) : []);
        $deptIds = array_filter(array_map('intval', $deptIds));

        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();

        $query = ($quickFilter === 'trash' || $quickFilter === 'deleted')
            ? Issue::onlyTrashed()
            : Issue::query();

        $issues = $query
            ->with(['status', 'priority', 'importance', 'category', 'creator', 'assignedUser.department', 'resolutionDepartment', 'messages.creator'])
            ->when($quickFilter === 'today_due', function ($q) use ($today) {
                $q->whereNull('closed_date')
                    ->whereHas('status', fn($s) => $s->whereNotIn('code', ['CLOSED', 'DONE']))
                    ->whereDate('due_date', $today);
            })
            ->when($quickFilter === 'in_progress', function ($q) use ($today) {
                $q->whereNull('closed_date')
                    ->whereHas('status', fn($s) => $s->whereNotIn('code', ['CLOSED', 'DONE']))
                    ->whereNotNull('due_date')
                    ->whereDate('due_date', '>', $today)
                    ->where(function ($sub) use ($today) {
                        $sub->where(function ($s1) use ($today) {
                            $s1->whereNotNull('started_at')
                               ->whereDate('started_at', '<=', $today);
                        })->orWhere(function ($s2) use ($today) {
                            $s2->whereNull('started_at')
                               ->whereDate('issue_at', '<=', $today);
                        });
                    });
            })
            ->when($quickFilter === 'tomorrow_start', function ($q) use ($tomorrow) {
                $q->whereNull('closed_date')
                    ->whereHas('status', fn($s) => $s->whereNotIn('code', ['CLOSED', 'DONE']))
                    ->where(function ($sub) use ($tomorrow) {
                        $sub->where(function ($s1) use ($tomorrow) {
                            $s1->whereNotNull('started_at')
                               ->whereDate('started_at', $tomorrow);
                        })->orWhere(function ($s2) use ($tomorrow) {
                            $s2->whereNull('started_at')
                               ->whereDate('issue_at', $tomorrow);
                        });
                    });
            })
            ->when($quickFilter === 'overdue', function ($q) {
                $q->whereNull('closed_date')
                    ->whereHas('status', fn($s) => $s->whereNotIn('code', ['CLOSED', 'DONE']))
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', Carbon::now());
            })
            ->when($quickFilter === 'trash' || $quickFilter === 'deleted', function ($q) use ($start, $end) {
                // If viewing trash, show all trashed issues or within date
                // We don't restrict strictly by date so users can easily find any trashed issue
            })
            ->when(!$quickFilter || $quickFilter === 'all', function ($q) use ($start, $end) {
                $q->where(function ($query) use ($start, $end) {
                    $query->whereBetween('issue_at', [$start, $end])
                        ->orWhereBetween('due_date', [$start, $end])
                        ->orWhereBetween('closed_date', [$start, $end]);
                });
            })
            ->when($resolverType === 'third_party', fn($q) => $q->where('is_third_party_resolver', true))
            ->when($resolverType === 'internal', fn($q) => $q->where('is_third_party_resolver', false))
            ->when(!empty($catIds), fn($q) => $q->whereIn('issue_category_id', $catIds))
            ->when(!empty($stCodes), fn($q) => $q->whereHas('status', fn($s) => $s->whereIn('code', $stCodes)))
            ->when(!empty($deptIds), function ($q) use ($deptIds) {
                $q->where(function ($sub) use ($deptIds) {
                    $sub->whereHas('assignedUser', fn($u) => $u->whereIn('department_id', $deptIds))
                        ->orWhereIn('resolution_department_id', $deptIds);
                });
            })
            ->orderBy('issue_at', 'desc')
            ->get();

        $totalIssuesCount = $issues->count();
        $passedCount = 0;
        $onTrackCount = 0;
        $failedCount = 0;
        $lackTrackCount = 0;
        $totalFailPoints = 0;
        $totalPotentialPoints = 0;

        $p1FailCount = 0;
        $p2FailCount = 0;
        $p3FailCount = 0;
        $p4FailCount = 0;

        $reportItems = $issues->map(function (Issue $issue) use (&$passedCount, &$onTrackCount, &$failedCount, &$lackTrackCount, &$totalFailPoints, &$totalPotentialPoints, &$p1FailCount, &$p2FailCount, &$p3FailCount, &$p4FailCount) {
            $priority = $issue->priority;
            $failWeight = $priority ? $this->slaCalculationService->getFailPoints($priority) : 1;
            $totalPotentialPoints += $failWeight;

            $hasDueDate = $issue->due_date !== null;
            $isClosed = $issue->closed_date !== null;

            if (!$hasDueDate) {
                // SLA without due date = LACK TRACK
                $slaStatus = 'LACK_TRACK';
                $slaLabel = 'LACK TRACK';
                $isFailed = false;
                $lackTrackCount++;
            } elseif ($isClosed) {
                // Closed issue: If closed_date <= due_date and not overridden to failed -> PASSED
                if ($issue->closed_date->lte($issue->due_date) && !$issue->is_sla_failed) {
                    $slaStatus = 'PASSED';
                    $slaLabel = 'PASSED';
                    $isFailed = false;
                    $passedCount++;
                } else {
                    $slaStatus = 'FAIL';
                    $slaLabel = 'FAIL';
                    $isFailed = true;
                }
            } else {
                // Open / In-progress issue: If due_date >= now -> ON TRACK, else FAIL
                if (!$issue->is_sla_failed && !$issue->due_date->isPast()) {
                    $slaStatus = 'ON_TRACK';
                    $slaLabel = 'ON TRACK';
                    $isFailed = false;
                    $onTrackCount++;
                } else {
                    $slaStatus = 'FAIL';
                    $slaLabel = 'FAIL';
                    $isFailed = true;
                }
            }

            if ($isFailed) {
                $failedCount++;
                $calculatedFailPoints = $issue->fail_points > 0 ? $issue->fail_points : $failWeight;
                $totalFailPoints += $calculatedFailPoints;

                $pCode = strtoupper($priority?->code ?? '');
                $pLevel = $priority?->level;
                if ($pCode === 'P1' || $pLevel === 1) $p1FailCount++;
                elseif ($pCode === 'P2' || $pLevel === 2) $p2FailCount++;
                elseif ($pCode === 'P3' || $pLevel === 3) $p3FailCount++;
                else $p4FailCount++;
            }

            // Get last admin log note (if any remark was logged)
            $logNote = $issue->messages->where('is_log_note', true)->last()?->message;

            // Get deletion / archiving reason log note
            $deletionMessage = $issue->messages->where('is_log_note', true)->filter(fn($m) => str_contains($m->message, '[ARCHIVED / DELETED]'))->last();
            $deletionReason = null;
            if ($deletionMessage) {
                $deletionReason = [
                    'reason' => trim(str_replace('[ARCHIVED / DELETED]:', '', $deletionMessage->message)),
                    'user_name' => $deletionMessage->creator?->name ?? 'User',
                    'created_at' => $deletionMessage->created_at?->format('j M y, g:i A'),
                ];
            }

            // Date format: 13 Aug 26, 4:00 PM
            $formatPattern = 'j M y, g:i A';

            return [
                'id' => $issue->id,
                'title' => $issue->title,
                'description' => $issue->description,
                'issue_category_id' => $issue->issue_category_id,
                'issue_priority_id' => $issue->issue_priority_id,
                'issue_importance_id' => $issue->issue_importance_id,
                'assigned_user_id' => $issue->assigned_user_id,
                'resolution_department_id' => $issue->resolution_department_id,
                'proposed_solution' => $issue->proposed_solution,
                'issue_by' => $issue->issue_by,
                'category_name' => $issue->category?->name ?? 'N/A',
                'priority_code' => $priority ? (substr($priority->name, 0, 2)) : 'P3',
                'priority_name' => $priority?->name ?? 'N/A',
                'priority_clock_type' => $priority?->clock_type ?? 'office_hours',
                'priority_is_manual_schedule' => (bool)($priority?->is_manual_schedule ?? false),
                'status_name' => $issue->status?->name ?? 'Open',
                'status_code' => $issue->status?->code ?? 'OPEN',
                'issue_status_id' => $issue->issue_status_id,
                'assigned_user_name' => $issue->assignedUser?->name ?? 'Unassigned',
                'assigned_user_department_name' => $issue->assignedUser?->department?->name ?? $issue->resolutionDepartment?->name ?? null,
                'issue_at' => $issue->issue_at?->format($formatPattern),
                'due_date' => $issue->due_date?->format($formatPattern),
                'closed_date' => $issue->closed_date?->format($formatPattern),
                'deleted_at' => $issue->deleted_at?->format($formatPattern),
                'issue_at_raw' => $issue->issue_at?->toIso8601String(),
                'due_date_raw' => $issue->due_date?->toIso8601String(),
                'closed_date_raw' => $issue->closed_date?->toIso8601String(),
                'deleted_at_raw' => $issue->deleted_at?->toIso8601String(),
                'is_deleted' => (bool)$issue->trashed(),
                'deletion_reason' => $deletionReason,
                'is_third_party_resolver' => (bool)$issue->is_third_party_resolver,
                'resolver_label' => $issue->is_third_party_resolver ? 'Third-Party Developer' : 'Internal User',
                'sla_status_code' => $slaStatus,
                'sla_status_label' => $slaLabel,
                'is_sla_failed' => $slaStatus === 'FAIL',
                'fail_points' => $slaStatus === 'FAIL' ? ($issue->fail_points > 0 ? $issue->fail_points : $failWeight) : 0,
                'admin_remark' => $logNote,
                'messages_count' => $issue->messages->count(),
                'messages' => $issue->messages->map(fn($m) => [
                    'id' => $m->id,
                    'message' => $m->message,
                    'is_log_note' => (bool)$m->is_log_note,
                    'created_at' => $m->created_at?->toIso8601String(),
                    'creator' => $m->creator ? ['name' => $m->creator->name] : null,
                ]),
            ];
        });

        $resolutionRate = $totalPotentialPoints > 0
            ? max(0, round((($totalPotentialPoints - $totalFailPoints) / $totalPotentialPoints) * 100, 2))
            : 100.0;

        // SLA Section 16 Service Credit Refund Tier Calculation
        if ($resolutionRate >= 99.0) {
            $serviceCreditPct = 0;
        } elseif ($resolutionRate >= 95.0) {
            $serviceCreditPct = 5;
        } elseif ($resolutionRate >= 90.0) {
            $serviceCreditPct = 10;
        } else {
            $serviceCreditPct = 20;
        }

        return [
            'period_type' => $periodType,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'period_label' => $periodType === 'monthly'
                ? $start->format('F Y')
                : 'Week ' . $start->format('W') . ' (' . $start->format('M d') . ' - ' . $end->format('M d, Y') . ')',
            'summary' => [
                'total_issues' => $totalIssuesCount,
                'passed_issues' => $passedCount,
                'on_track_issues' => $onTrackCount,
                'failed_issues' => $failedCount,
                'lack_track_issues' => $lackTrackCount,
                'total_fail_points' => $totalFailPoints,
                'p1_fail_count' => $p1FailCount,
                'p2_fail_count' => $p2FailCount,
                'p3_fail_count' => $p3FailCount,
                'p4_fail_count' => $p4FailCount,
                'resolution_rate' => $resolutionRate,
                'service_credit_pct' => $serviceCreditPct,
                'deleted_issues_count' => Issue::onlyTrashed()->count(),
            ],
            'items' => $reportItems,
        ];
    }
}
