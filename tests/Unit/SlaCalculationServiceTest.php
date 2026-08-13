<?php

namespace Tests\Unit;

use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Services\SlaCalculationService;
use Carbon\Carbon;
use Tests\TestCase;

class SlaCalculationServiceTest extends TestCase
{
    protected SlaCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlaCalculationService();
    }

    public function test_p1_due_date_uses_continuous_24_calendar_hours()
    {
        $priority = new IssuePriority(['code' => 'P1', 'level' => 4, 'name' => 'Critical']);
        $start = Carbon::parse('2026-08-14 10:00:00'); // Friday 10:00 AM

        $dueDate = $this->service->calculateDueDate($start, $priority);

        // Continuous 24h: Should be Saturday 10:00 AM
        $this->assertEquals('2026-08-15 10:00:00', $dueDate->format('Y-m-d H:i:s'));
    }

    public function test_p2_due_date_uses_office_hours()
    {
        $priority = new IssuePriority(['code' => 'P2', 'level' => 3, 'name' => 'High']);
        // Friday 10:00 AM (Has 7 office hours left on Friday until 17:00)
        // 1 Business Day = 8.5 office hours (510 minutes).
        // Remaining 1.5 office hours roll over into Saturday morning 09:00 -> 10:30 AM
        $start = Carbon::parse('2026-08-14 10:00:00');

        $dueDate = $this->service->calculateDueDate($start, $priority);

        $this->assertEquals('2026-08-15 10:30:00', $dueDate->format('Y-m-d H:i:s'));
    }

    public function test_fail_points_weightage()
    {
        $p1 = new IssuePriority(['code' => 'P1', 'level' => 4]);
        $p2 = new IssuePriority(['code' => 'P2', 'level' => 3]);
        $p3 = new IssuePriority(['code' => 'P3', 'level' => 2]);

        $this->assertEquals(10, $this->service->getFailPoints($p1));
        $this->assertEquals(5, $this->service->getFailPoints($p2));
        $this->assertEquals(1, $this->service->getFailPoints($p3));
    }
}
