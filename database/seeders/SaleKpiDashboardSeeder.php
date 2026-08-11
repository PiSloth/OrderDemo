<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BranchTarget;
use App\Models\DailyReport;
use App\Models\DailyReportRecord;
use App\Models\Department;
use App\Models\PromoteAction;
use App\Models\Position;
use App\Models\User;
use App\Models\TodoList;
use App\Models\TodoDueTime;
use App\Models\Kpi\KpiTaskTemplate;
use App\Models\Kpi\KpiGroup;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaleKpiDashboardSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Position and Admin User exist
        $position = Position::firstOrCreate(['name' => 'Super Admin']);
        
        $branch1 = Branch::firstOrCreate(['name' => 'branch 1']);
        $branch2 = Branch::firstOrCreate(['name' => 'branch 2']);
        $branch3 = Branch::firstOrCreate(['name' => 'branch 3']);
        $branches = [$branch1, $branch2, $branch3];

        $admin = User::updateOrCreate(
            ['email' => 'admin@kpi.com'],
            [
                'name' => 'KPI Admin',
                'password' => Hash::make('password'),
                'position_id' => $position->id,
                'branch_id' => $branch1->id,
            ]
        );

        // 2. Ensure Department exists
        $department = Department::firstOrCreate(['name' => 'Marketing']);
        $itDept = Department::firstOrCreate(['name' => 'IT']);

        // 3. Seed Todo lists & KPI templates for autocomplete reference tests
        $dueTime = TodoDueTime::first();
        if (!$dueTime) {
            $category = \App\Models\TodoCategory::firstOrCreate(['name' => 'General Tasks']);
            $priority = \App\Models\TodoPriority::firstOrCreate(['level' => 'Normal'], ['rank' => 1]);
            $dueTime = TodoDueTime::create([
                'todo_category_id' => $category->id,
                'todo_priority_id' => $priority->id,
                'duration' => 24,
                'description' => '24-hour SLA task',
            ]);
        }

        TodoList::create([
            'todo_due_time_id' => $dueTime->id,
            'task' => 'Launch Mid-Autumn Gold campaign',
            'due_date' => Carbon::now()->addDays(5),
            'created_by_user_id' => $admin->id,
            'assigned_user_id' => $admin->id,
            'requested_by_branch_id' => $branch1->id,
            'requested_by_department_id' => $department->id,
        ]);

        TodoList::create([
            'todo_due_time_id' => $dueTime->id,
            'task' => 'Organize Sunday Pandora discount event',
            'due_date' => Carbon::now()->addDays(7),
            'created_by_user_id' => $admin->id,
            'assigned_user_id' => $admin->id,
            'requested_by_branch_id' => $branch2->id,
            'requested_by_department_id' => $department->id,
        ]);

        $kpiGroup = KpiGroup::firstOrCreate([
            'name' => 'Sales KPI Tasks',
        ]);

        KpiTaskTemplate::firstOrCreate(
            ['title' => 'Daily Sales Verification'],
            [
                'kpi_group_id' => $kpiGroup->id,
                'slug' => 'daily-sales-verification',
                'frequency' => 'daily',
            ]
        );

        KpiTaskTemplate::firstOrCreate(
            ['title' => 'Weekly Promotion Audit'],
            [
                'kpi_group_id' => $kpiGroup->id,
                'slug' => 'weekly-promotion-audit',
                'frequency' => 'weekly',
            ]
        );

        // 4. Seed Targets for August 2026
        $year = 2026;
        $month = 8;

        foreach ($branches as $branch) {
            for ($day = 1; $day <= 31; $day++) {
                BranchTarget::updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'year' => $year,
                        'month' => $month,
                        'day' => $day,
                    ],
                    [
                        'target_gram' => rand(80, 150),
                        'target_pcs' => rand(5, 12),
                    ]
                );
            }
        }

        // 5. Seed Daily Report Records for August 2026 (Aug 1 to Aug 25)
        // Ensure daily reports types exist in the database with their properties
        // IDs: 1 -> Gold pcs, 2 -> Gold weight, 3 -> Pandora pcs, 4 -> Pandora weight,
        //      5 -> 18K pcs, 6 -> 18K weight, 10 -> Customer Count, 17 -> Staff Count
        $reportsMap = [
            1 => ['name' => 'ရွှေ (pcs)', 'properties' => '{"scope": "sale", "matric_type": "quantity", "product_type": "gold"}'],
            2 => ['name' => 'ရွှေ (weight / g)', 'properties' => '{"scope": "sale", "matric_type": "weight", "product_type": "gold"}'],
            3 => ['name' => 'Pandora (pcs)', 'properties' => '{"scope": "sale", "matric_type": "quantity", "product_type": "pandora"}'],
            4 => ['name' => 'Pandora (weihgt / g)', 'properties' => '{"scope": "sale", "matric_type": "weight", "product_type": "pandora"}'],
            5 => ['name' => '18K (pcs)', 'properties' => '{"scope": "sale", "matric_type": "quantity", "product_type": "18K"}'],
            6 => ['name' => '18K (weihgt / g)', 'properties' => '{"scope": "sale", "matric_type": "weight", "product_type": "18K"}'],
            10 => ['name' => 'Customer အဝင် ဦးရေ', 'properties' => '{"scope": "sale", "matric_type": "quantity", "product_type": "customer"}'],
            17 => ['name' => 'အရွေးအစောင်ရေ (Pawn)', 'properties' => '{"scope": "sale", "matric_type": "quantity", "product_type": "staff"}'], // Pawn staff count mapping
        ];

        foreach ($reportsMap as $id => $info) {
            DailyReport::updateOrCreate(
                ['id' => $id],
                [
                    'name' => $info['name'],
                    'description' => $info['name'] . ' Description',
                    'properties' => $info['properties'],
                    'is_sale_gram' => str_contains($info['properties'], '"weight"'),
                    'is_sale_quantity' => str_contains($info['properties'], '"quantity"'),
                ]
            );
        }

        // Seed Daily Records
        foreach ($branches as $branch) {
            for ($day = 1; $day <= 25; $day++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);

                // Gold quantity & weight
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 1, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(2, 6)]
                );
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 2, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(30, 70)]
                );

                // Pandora quantity & weight
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 3, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(1, 4)]
                );
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 4, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(15, 45)]
                );

                // 18K quantity & weight
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 5, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(1, 3)]
                );
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 6, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(10, 30)]
                );

                // Customer count
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 10, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(12, 35)]
                );

                // Staff count
                DailyReportRecord::updateOrCreate(
                    ['branch_id' => $branch->id, 'daily_report_id' => 17, 'report_date' => $dateStr],
                    ['user_id' => $admin->id, 'number' => rand(3, 5)]
                );
            }
        }

        // 6. Seed Promote Actions
        // PA1: Aug 7 to Aug 11 (overlaps current date Aug 11) - All branches
        PromoteAction::updateOrCreate(
            ['name' => 'August Mid-Season Clearance'],
            [
                'target_branch_id' => null,
                'action_by' => $department->id,
                'start_at' => '2026-08-07',
                'end_at' => '2026-08-11',
                'reference' => ['todo_list_id' => 1],
            ]
        );

        // PA2: Aug 8 to Aug 17 (overlaps current date Aug 11) - Branch 1
        PromoteAction::updateOrCreate(
            ['name' => 'Weekend Gold Rush'],
            [
                'target_branch_id' => $branch1->id,
                'action_by' => $department->id,
                'start_at' => '2026-08-08',
                'end_at' => '2026-08-17',
                'reference' => null,
            ]
        );

        // PA3: Aug 18 to Aug 24 - Branch 2
        PromoteAction::updateOrCreate(
            ['name' => 'Late Summer Gala'],
            [
                'target_branch_id' => $branch2->id,
                'action_by' => $department->id,
                'start_at' => '2026-08-18',
                'end_at' => '2026-08-24',
                'reference' => null,
            ]
        );
    }
}
