<?php

namespace Database\Seeders;

use App\IssueTracking\Models\Issue;
use App\IssueTracking\Models\IssueCategory;
use App\IssueTracking\Models\IssueImportanceLevel;
use App\IssueTracking\Models\IssueMessage;
use App\IssueTracking\Models\IssuePriority;
use App\IssueTracking\Models\IssueStatus;
use App\IssueTracking\Services\SlaCalculationService;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ItIssueSeeder extends Seeder
{
    public function run(): void
    {
        $slaService = new SlaCalculationService();

        // 1. Seed Categories
        $categoriesData = [
            ['name' => 'Odoo ERP Backend', 'is_erp' => true],
            ['name' => 'Mobile Application', 'is_erp' => false],
            ['name' => 'Data Sync / Integration', 'is_erp' => true],
            ['name' => 'Barcode / Printing Hardware', 'is_erp' => false],
            ['name' => 'Network / Server Infrastructure', 'is_erp' => false],
        ];

        $categoryMap = [];
        foreach ($categoriesData as $c) {
            $categoryMap[$c['name']] = IssueCategory::updateOrCreate(['name' => $c['name']], $c);
        }

        // 2. Seed Priorities
        $prioritiesData = [
            ['name' => 'P1 - Critical', 'level' => 4],
            ['name' => 'P2 - High', 'level' => 3],
            ['name' => 'P3 - Normal', 'level' => 2],
            ['name' => 'P4 - Request', 'level' => 1],
        ];

        $priorityMap = [];
        foreach ($prioritiesData as $p) {
            $priorityMap[$p['level']] = IssuePriority::updateOrCreate(['level' => $p['level']], $p);
        }

        // 3. Seed Importance Levels
        $importanceData = [
            ['name' => 'Low', 'level' => 1],
            ['name' => 'Medium', 'level' => 2],
            ['name' => 'High', 'level' => 3],
            ['name' => 'Critical', 'level' => 4],
        ];

        $importanceMap = [];
        foreach ($importanceData as $imp) {
            $importanceMap[$imp['level']] = IssueImportanceLevel::updateOrCreate(['level' => $imp['level']], $imp);
        }

        // 4. Seed Statuses
        $statusesData = [
            ['code' => 'OPEN', 'name' => 'Open'],
            ['code' => 'ASSIGNED', 'name' => 'Assigned'],
            ['code' => 'IN_PROGRESS', 'name' => 'In Progress'],
            ['code' => 'PENDING', 'name' => 'Pending Info'],
            ['code' => 'DONE', 'name' => 'Done'],
            ['code' => 'CLOSED', 'name' => 'Closed'],
        ];

        $statusMap = [];
        foreach ($statusesData as $st) {
            $statusMap[$st['code']] = IssueStatus::updateOrCreate(['code' => $st['code']], $st);
        }

        $user = User::first() ?? User::factory()->create(['name' => 'IT Admin', 'email' => 'admin@nexgen.com']);
        $itDept = Department::where('name', 'like', '%IT%')->first() ?? Department::first() ?? Department::create(['name' => 'IT Department']);

        // Base date for historical seed data (within current week)
        $now = now();
        $mondayThisWeek = $now->copy()->startOfWeek(Carbon::MONDAY);

        // 5. Seed Sample Issues with Various SLA States

        // Sample 1: P1 Failed Issue (Continuous 24h clock breached -> 10 Fail Points)
        $p1Start = $mondayThisWeek->copy()->addHours(9); // Monday 09:00 AM
        $p1Due = $slaService->calculateDueDate($p1Start, $priorityMap[4]); // Level 4 (P1) -> Tuesday 09:00 AM (24h)
        $p1Closed = $mondayThisWeek->copy()->addHours(35); // Tuesday 08:00 PM (Exceeded 24h by 11 hrs)

        $issue1 = Issue::create([
            'title' => 'Odoo ERP Server Crash & Database Lockout',
            'description' => 'Main ERP server crashed during morning peak hours. Users unable to log in.',
            'issue_category_id' => $categoryMap['Odoo ERP Backend']->id,
            'issue_priority_id' => $priorityMap[4]->id,
            'issue_importance_id' => $importanceMap[4]->id,
            'issue_by' => 'Branch 1 Manager',
            'issue_at' => $p1Start,
            'due_date' => $p1Due,
            'closed_date' => $p1Closed,
            'is_sla_failed' => true,
            'fail_points' => 10,
            'issue_status_id' => $statusMap['CLOSED']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
            'proposed_solution' => 'Restarted PostgreSQL service and cleaned locks.',
        ]);

        IssueMessage::create([
            'issue_id' => $issue1->id,
            'created_by' => $user->id,
            'message' => 'Status changed to Closed. (SLA Failed: 10 fail points - Resolution exceeded 24 calendar hours).',
            'is_log_note' => true,
        ]);

        // Sample 2: P2 Failed Issue (Office hours 1 business day breached -> 5 Fail Points)
        $p2Start = $mondayThisWeek->copy()->addHours(10); // Monday 10:00 AM
        $p2Due = $slaService->calculateDueDate($p2Start, $priorityMap[3]); // Level 3 (P2)
        $p2Closed = $mondayThisWeek->copy()->addDays(2)->addHours(14); // Wednesday 02:00 PM (Closed late)

        $issue2 = Issue::create([
            'title' => 'Mobile App Offline Sync Failing for Branch 2',
            'description' => 'Sales reps unable to sync offline transactions back to backend Odoo server.',
            'issue_category_id' => $categoryMap['Data Sync / Integration']->id,
            'issue_priority_id' => $priorityMap[3]->id,
            'issue_importance_id' => $importanceMap[3]->id,
            'issue_by' => 'Sales Supervisor',
            'issue_at' => $p2Start,
            'due_date' => $p2Due,
            'closed_date' => $p2Closed,
            'is_sla_failed' => true,
            'fail_points' => 5,
            'issue_status_id' => $statusMap['CLOSED']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
            'proposed_solution' => 'Fixed REST API payload timeout error.',
        ]);

        IssueMessage::create([
            'issue_id' => $issue2->id,
            'created_by' => $user->id,
            'message' => 'Status changed to Closed. (SLA Failed: 5 fail points - Resolution exceeded 1 business day).',
            'is_log_note' => true,
        ]);

        // Sample 3: P3 Failed Issue (Office hours 2 business days breached -> 1 Fail Point)
        $p3Start = $mondayThisWeek->copy()->addHours(11); // Monday 11:00 AM
        $p3Due = $slaService->calculateDueDate($p3Start, $priorityMap[2]); // Level 2 (P3)
        $p3Closed = $mondayThisWeek->copy()->addDays(4)->addHours(16);

        Issue::create([
            'title' => 'Custom Sales Report Mismatch in Category Totals',
            'description' => 'Category totals in monthly sales report do not match inventory ledger.',
            'issue_category_id' => $categoryMap['Odoo ERP Backend']->id,
            'issue_priority_id' => $priorityMap[2]->id,
            'issue_importance_id' => $importanceMap[2]->id,
            'issue_by' => 'Accounting Staff',
            'issue_at' => $p3Start,
            'due_date' => $p3Due,
            'closed_date' => $p3Closed,
            'is_sla_failed' => true,
            'fail_points' => 1,
            'issue_status_id' => $statusMap['CLOSED']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
            'proposed_solution' => 'Updated SQL query formula for category aggregation.',
        ]);

        // Sample 4: Admin SLA Overridden Issue (Waived fail, fail points reset to 0 with admin remark)
        $overrideStart = $mondayThisWeek->copy()->addHours(14); // Monday 02:00 PM
        $overrideDue = $slaService->calculateDueDate($overrideStart, $priorityMap[3]);

        $issue4 = Issue::create([
            'title' => 'Thermal Printer Connection Timeout at Branch 3 Counter',
            'description' => 'Barcode printer disconnected due to faulty LAN cable installed by branch staff.',
            'issue_category_id' => $categoryMap['Barcode / Printing Hardware']->id,
            'issue_priority_id' => $priorityMap[3]->id,
            'issue_importance_id' => $importanceMap[3]->id,
            'issue_by' => 'Branch 3 Cashier',
            'issue_at' => $overrideStart,
            'due_date' => $overrideDue,
            'closed_date' => $mondayThisWeek->copy()->addDays(2)->addHours(16),
            'is_sla_failed' => false,
            'fail_points' => 0,
            'issue_status_id' => $statusMap['CLOSED']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
            'proposed_solution' => 'Replaced faulty branch ethernet cable.',
        ]);

        IssueMessage::create([
            'issue_id' => $issue4->id,
            'created_by' => $user->id,
            'message' => '[ADMIN SLA OVERRIDE -> SUCCESS]: Issue delay was caused by third-party hardware cable failure at branch side (SLA Section 11 Exclusion). Waived fail points.',
            'is_log_note' => true,
        ]);

        // Sample 5: P1 Passed Issue (Resolved within 24h clock -> 0 Fail Points)
        $p1PassStart = $mondayThisWeek->copy()->addHours(15);
        $p1PassDue = $slaService->calculateDueDate($p1PassStart, $priorityMap[4]);
        $p1PassClosed = $p1PassStart->copy()->addHours(3); // Resolved in 3 hours!

        Issue::create([
            'title' => 'Payment Gateway Timeout during Online Orders',
            'description' => 'Payment webhook timing out for mobile app online purchases.',
            'issue_category_id' => $categoryMap['Mobile Application']->id,
            'issue_priority_id' => $priorityMap[4]->id,
            'issue_importance_id' => $importanceMap[4]->id,
            'issue_by' => 'E-commerce Admin',
            'issue_at' => $p1PassStart,
            'due_date' => $p1PassDue,
            'closed_date' => $p1PassClosed,
            'is_sla_failed' => false,
            'fail_points' => 0,
            'issue_status_id' => $statusMap['CLOSED']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
            'proposed_solution' => 'Increased HTTP gateway timeout threshold.',
        ]);

        // Sample 6: P2 Passed Issue (Resolved within 1 business day -> 0 Fail Points)
        $p2PassStart = $mondayThisWeek->copy()->addDays(1)->addHours(9);
        $p2PassDue = $slaService->calculateDueDate($p2PassStart, $priorityMap[3]);
        $p2PassClosed = $p2PassStart->copy()->addHours(4); // Resolved in 4 office hours!

        Issue::create([
            'title' => 'Stock Transfer Receipt PDF Rendering Error',
            'description' => 'PDF export fails when stock transfer contains over 50 item lines.',
            'issue_category_id' => $categoryMap['Odoo ERP Backend']->id,
            'issue_priority_id' => $priorityMap[3]->id,
            'issue_importance_id' => $importanceMap[3]->id,
            'issue_by' => 'Warehouse Supervisor',
            'issue_at' => $p2PassStart,
            'due_date' => $p2PassDue,
            'closed_date' => $p2PassClosed,
            'is_sla_failed' => false,
            'fail_points' => 0,
            'issue_status_id' => $statusMap['CLOSED']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
            'proposed_solution' => 'Optimized PDF page break template layout.',
        ]);

        // Sample 7: Active Open / In-Progress Issue
        $openStart = $now->copy()->subHours(2);
        $openDue = $slaService->calculateDueDate($openStart, $priorityMap[3]);

        Issue::create([
            'title' => 'Barcode Scanner Driver Setup for New Branch 4 Counter',
            'description' => 'Configure new Honeywell Bluetooth barcode scanner for POS counter 2.',
            'issue_category_id' => $categoryMap['Barcode / Printing Hardware']->id,
            'issue_priority_id' => $priorityMap[3]->id,
            'issue_importance_id' => $importanceMap[2]->id,
            'issue_by' => 'Branch 4 Supervisor',
            'issue_at' => $openStart,
            'due_date' => $openDue,
            'issue_status_id' => $statusMap['IN_PROGRESS']->id,
            'created_by' => $user->id,
            'assigned_user_id' => $user->id,
            'resolution_department_id' => $itDept->id,
        ]);
    }
}
