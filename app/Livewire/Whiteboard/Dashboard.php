<?php

namespace App\Livewire\Whiteboard;

use App\Models\WhiteboardContent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.whiteboard')]
#[Title('Whiteboard Dashboard')]
class Dashboard extends Component
{
    public function isContentRead(WhiteboardContent $content): bool
    {
        $user = Auth::user();

        return $content->reports->contains(function ($report) use ($user) {
            $matchesUser = $report->emailList?->email === $user?->email;
            $matchesDepartment = $user?->department_id
                && $report->emailList?->department_id === $user->department_id;

            return ($matchesUser || $matchesDepartment) && $report->is_read;
        });
    }

    private function dashboardContents(): Collection
    {
        return WhiteboardContent::query()
            ->boardFeed(Auth::user())
            ->get();
    }

    private function buildDashboard(Collection $contents): array
    {
        $pendingDecisionCount = $contents
            ->filter(fn(WhiteboardContent $content) => $content->requiresDecision() && ! $content->latestDecision)
            ->count();

        $today = Carbon::today();

        $dueTodayCount = $contents
            ->filter(function (WhiteboardContent $content) use ($today) {
                return $content->requiresDecision()
                    && ! $content->latestDecision
                    && $content->propose_decision_due_at
                    && $content->propose_decision_due_at->isSameDay($today);
            })
            ->count();

        $overdueDecisionCount = $contents
            ->filter(function (WhiteboardContent $content) {
                return $content->requiresDecision()
                    && ! $content->latestDecision
                    && $content->propose_decision_due_at
                    && $content->propose_decision_due_at->isPast();
            })
            ->count();

        $requiredDecisionCount = $contents
            ->filter(fn(WhiteboardContent $content) => $content->requiresDecision())
            ->count();

        $decisionCompletedCount = $contents
            ->filter(fn(WhiteboardContent $content) => $content->requiresDecision() && $content->latestDecision)
            ->count();

        $reportRows = $contents->flatMap(fn(WhiteboardContent $content) => $content->reports);
        $totalRecipients = $reportRows->count();
        $readRecipients = $reportRows->where('is_read', true)->count();
        $readRate = $totalRecipients > 0 ? round(($readRecipients / $totalRecipients) * 100) : 0;

        $decisionCompletionRate = $requiredDecisionCount > 0
            ? round(($decisionCompletedCount / $requiredDecisionCount) * 100)
            : 0;

        $avgDecisionTurnaroundHours = $contents
            ->filter(fn(WhiteboardContent $content) => $content->latestDecision !== null)
            ->map(function (WhiteboardContent $content) {
                $startAt = $content->received_mail_at ?? $content->created_at;

                if (! $startAt || ! $content->latestDecision?->created_at) {
                    return null;
                }

                return max(0, $startAt->diffInHours($content->latestDecision->created_at));
            })
            ->filter(fn($hours) => $hours !== null)
            ->avg();

        return [
            'summary' => [
                'visible_contents' => $contents->count(),
                'unread_contents' => $contents->filter(fn(WhiteboardContent $content) => ! $this->isContentRead($content))->count(),
                'pending_decisions' => $pendingDecisionCount,
                'due_today' => $dueTodayCount,
                'overdue_decisions' => $overdueDecisionCount,
                'read_rate' => $readRate,
                'decision_completion_rate' => $decisionCompletionRate,
                'avg_decision_turnaround_label' => $this->formatAverageTurnaround($avgDecisionTurnaroundHours),
                'sent_departments' => $reportRows
                    ->map(fn($report) => $report->emailList?->department?->name)
                    ->filter()
                    ->unique()
                    ->count(),
            ],
            'department_trend_chart' => $this->buildDepartmentTrendChart($contents),
            'top_content_types' => $this->buildTopContentTypes($contents),
            'top_reporters' => $this->buildTopReporters($contents),
            'top_flags' => $this->buildTopFlags($contents),
        ];
    }

    private function buildDepartmentTrendChart(Collection $contents): array
    {
        $departmentTotals = [];
        $departmentTimeline = [];
        $dateBuckets = [];
        $palette = ['#0F766E', '#2563EB', '#F59E0B', '#E11D48', '#7C3AED'];

        foreach ($contents as $content) {
            $date = ($content->received_mail_at ?? $content->created_at)?->format('Y-m-d');

            if (! $date) {
                continue;
            }

            $dateBuckets[$date] = true;
            $departmentsSeen = [];

            foreach ($content->reports as $report) {
                $departmentName = trim((string) ($report->emailList?->department?->name ?? ''));

                if ($departmentName === '' || isset($departmentsSeen[$departmentName])) {
                    continue;
                }

                $departmentsSeen[$departmentName] = true;
                $departmentTotals[$departmentName] = ($departmentTotals[$departmentName] ?? 0) + 1;
                $departmentTimeline[$departmentName][$date] = ($departmentTimeline[$departmentName][$date] ?? 0) + 1;
            }
        }

        if ($departmentTotals === []) {
            return [
                'categories' => [],
                'series' => [],
                'top_department' => null,
                'top_count' => 0,
            ];
        }

        arsort($departmentTotals);
        $topDepartments = array_slice(array_keys($departmentTotals), 0, 5);
        $dates = collect(array_keys($dateBuckets))
            ->sort()
            ->values()
            ->take(-10)
            ->all();

        $series = collect($topDepartments)
            ->values()
            ->map(function (string $departmentName, int $index) use ($dates, $departmentTimeline, $palette) {
                return [
                    'name' => $departmentName,
                    'color' => $palette[$index % count($palette)],
                    'data' => collect($dates)
                        ->map(fn(string $date) => (int) ($departmentTimeline[$departmentName][$date] ?? 0))
                        ->all(),
                ];
            })
            ->all();

        $topDepartment = array_key_first($departmentTotals);

        return [
            'categories' => collect($dates)->map(fn(string $date) => Carbon::parse($date)->format('M d'))->all(),
            'series' => $series,
            'top_department' => $topDepartment,
            'top_count' => (int) ($departmentTotals[$topDepartment] ?? 0),
        ];
    }

    private function buildTopContentTypes(Collection $contents, int $limit = 5): array
    {
        return $contents
            ->groupBy(fn(WhiteboardContent $content) => $content->contentType?->name ?? 'Uncategorized')
            ->map(fn(Collection $group, string $name) => [
                'label' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    private function buildTopReporters(Collection $contents, int $limit = 5): array
    {
        return $contents
            ->groupBy(fn(WhiteboardContent $content) => $content->reporter?->user_name ?? 'Unknown reporter')
            ->map(fn(Collection $group, string $name) => [
                'label' => $name,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    private function buildTopFlags(Collection $contents, int $limit = 5): array
    {
        return $contents
            ->filter(fn(WhiteboardContent $content) => $content->flag !== null)
            ->groupBy(fn(WhiteboardContent $content) => $content->flag?->name ?? 'No Flag')
            ->map(fn(Collection $group, string $name) => [
                'label' => $name,
                'count' => $group->count(),
                'color' => $group->first()?->flag?->color ?? '#94A3B8',
            ])
            ->sortByDesc('count')
            ->take($limit)
            ->values()
            ->all();
    }

    private function formatAverageTurnaround(?float $hours): string
    {
        if ($hours === null) {
            return 'No decisions yet';
        }

        if ($hours >= 24) {
            return number_format($hours / 24, 1) . ' days';
        }

        if ($hours >= 1) {
            return number_format($hours, 1) . ' hrs';
        }

        return '< 1 hr';
    }

    public function render()
    {
        $contents = $this->dashboardContents();

        return view('livewire.whiteboard.dashboard', [
            'dashboard' => $this->buildDashboard($contents),
        ]);
    }
}
