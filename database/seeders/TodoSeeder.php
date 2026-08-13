<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Department;
use App\Models\TaskComment;
use App\Models\TodoCategory;
use App\Models\TodoDueTime;
use App\Models\TodoList;
use App\Models\TodoPriority;
use App\Models\TodoStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TodoSeeder extends Seeder
{
    /**
     * Seed the Todo module with realistic priorities, statuses, categories, due times, tasks & comments.
     */
    public function run(): void
    {
        // 1. SEED TODO PRIORITIES
        $prioritiesData = [
            ['level' => 'Urgent / P1', 'rank' => 1, 'color_code' => '#EF4444'],
            ['level' => 'High / P2', 'rank' => 2, 'color_code' => '#F59E0B'],
            ['level' => 'Medium / P3', 'rank' => 3, 'color_code' => '#3B82F6'],
            ['level' => 'Low / P4', 'rank' => 4, 'color_code' => '#64748B'],
        ];

        $priorities = [];
        foreach ($prioritiesData as $pData) {
            $priorities[$pData['level']] = TodoPriority::firstOrCreate(
                ['rank' => $pData['rank']],
                ['level' => $pData['level'], 'color_code' => $pData['color_code']]
            );
        }

        // 2. SEED TODO STATUSES
        $statusesData = [
            ['status' => 'Pending', 'description' => 'New task assigned and awaiting action', 'color_code' => '#3B82F6'],
            ['status' => 'In Progress', 'description' => 'Task is actively being worked on', 'color_code' => '#F59E0B'],
            ['status' => 'Under Review', 'description' => 'Task completed and under verification', 'color_code' => '#8B5CF6'],
            ['status' => 'Completed', 'description' => 'Task verified and closed', 'color_code' => '#10B981'],
        ];

        $statuses = [];
        foreach ($statusesData as $sData) {
            $statuses[$sData['status']] = TodoStatus::firstOrCreate(
                ['status' => $sData['status']],
                ['description' => $sData['description'], 'color_code' => $sData['color_code']]
            );
        }

        // Ensure default Departments exist
        $auditDept = Department::firstOrCreate(['name' => 'Audit & Compliance']);
        $itDept = Department::firstOrCreate(['name' => 'IT & Systems']);
        $financeDept = Department::firstOrCreate(['name' => 'Finance & Treasury']);
        $opsDept = Department::firstOrCreate(['name' => 'Store Operations']);

        // Ensure default Branch exists
        $branch1 = Branch::firstOrCreate(['name' => 'Branch 1 Main']);
        $branch2 = Branch::firstOrCreate(['name' => 'Branch 2 Sub']);

        // Ensure default Users exist
        $defaultPositionId = \App\Models\Position::first()?->id ?? 1;

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@shwetatar.com'],
            [
                'name' => 'KPI Admin',
                'password' => bcrypt('password'),
                'department_id' => $auditDept->id,
                'branch_id' => $branch1->id,
                'position_id' => $defaultPositionId,
            ]
        );

        $auditorUser = User::firstOrCreate(
            ['email' => 'auditor@shwetatar.com'],
            [
                'name' => 'Soe, P. O.',
                'password' => bcrypt('password'),
                'department_id' => $auditDept->id,
                'branch_id' => $branch1->id,
                'position_id' => $defaultPositionId,
            ]
        );

        $itUser = User::firstOrCreate(
            ['email' => 'it.support@shwetatar.com'],
            [
                'name' => 'Min, K. T.',
                'password' => bcrypt('password'),
                'department_id' => $itDept->id,
                'branch_id' => $branch1->id,
                'position_id' => $defaultPositionId,
            ]
        );

        $financeUser = User::firstOrCreate(
            ['email' => 'finance@shwetatar.com'],
            [
                'name' => 'Jenkins, S.',
                'password' => bcrypt('password'),
                'department_id' => $financeDept->id,
                'branch_id' => $branch2->id,
                'position_id' => $defaultPositionId,
            ]
        );

        // 3. SEED TODO CATEGORIES
        $categoriesData = [
            ['name' => 'Vault & Cash Audit', 'description' => 'Physical cash and vault security inspections', 'department_id' => $auditDept->id],
            ['name' => 'Gold Inventory Verification', 'description' => 'Pawn items and stock weight verification', 'department_id' => $auditDept->id],
            ['name' => 'System Data Entry & Odoo QC', 'description' => 'ERP cataloging, QC data entry & sync', 'department_id' => $itDept->id],
            ['name' => 'Financial Reconciliation', 'description' => 'Petty cash, ledger and daily transaction sign-off', 'department_id' => $financeDept->id],
            ['name' => 'Counter & Branch Operations', 'description' => 'Storefront compliance and safety procedures', 'department_id' => $opsDept->id],
        ];

        $categories = [];
        foreach ($categoriesData as $cData) {
            $categories[$cData['name']] = TodoCategory::firstOrCreate(
                ['name' => $cData['name']],
                ['description' => $cData['description'], 'department_id' => $cData['department_id']]
            );
        }

        // 4. SEED TODO DUE TIMES
        $dueTimesData = [
            [
                'category_name' => 'Vault & Cash Audit',
                'priority_level' => 'Urgent / P1',
                'duration' => 4,
                'description' => '4 Hours - Urgent Vault Area Inspection & Cash Count',
            ],
            [
                'category_name' => 'Gold Inventory Verification',
                'priority_level' => 'High / P2',
                'duration' => 8,
                'description' => '8 Hours - Pawn Serial & Weight Verification',
            ],
            [
                'category_name' => 'System Data Entry & Odoo QC',
                'priority_level' => 'Medium / P3',
                'duration' => 24,
                'description' => '24 Hours - Odoo ERP Product Quality Inspection Entry',
            ],
            [
                'category_name' => 'Financial Reconciliation',
                'priority_level' => 'High / P2',
                'duration' => 8,
                'description' => '8 Hours - Branch Petty Cash Sign-off & Audit',
            ],
            [
                'category_name' => 'Counter & Branch Operations',
                'priority_level' => 'Low / P4',
                'duration' => 48,
                'description' => '48 Hours - Weekly Compliance & Safety Inspection',
            ],
        ];

        $dueTimes = [];
        foreach ($dueTimesData as $dtData) {
            $cat = $categories[$dtData['category_name']] ?? null;
            $prio = $priorities[$dtData['priority_level']] ?? null;

            if ($cat && $prio) {
                $dueTimes[] = TodoDueTime::firstOrCreate(
                    [
                        'todo_category_id' => $cat->id,
                        'todo_priority_id' => $prio->id,
                        'duration' => $dtData['duration'],
                    ],
                    [
                        'description' => $dtData['description'],
                        'generate_kpi_instance' => false,
                    ]
                );
            }
        }

        // 5. SEED TODO TASKS (SAMPLE DATA)
        $tasksData = [
            [
                'task' => 'Clean Vault Area & Verify Main Safe Cash Drawers',
                'status' => 'In Progress',
                'due_time' => $dueTimes[0] ?? null,
                'assigned_user_id' => $auditorUser->id,
                'created_by_user_id' => $adminUser->id,
                'requested_by_department_id' => $auditDept->id,
                'requested_by_branch_id' => $branch1->id,
                'due_date' => Carbon::now()->addHours(4),
            ],
            [
                'task' => 'Odoo ERP System Data Entry: Confirm QC Verified Available Stock Catalog List',
                'status' => 'In Progress',
                'due_time' => $dueTimes[2] ?? null,
                'assigned_user_id' => $itUser->id,
                'created_by_user_id' => $adminUser->id,
                'requested_by_department_id' => $itDept->id,
                'requested_by_branch_id' => $branch1->id,
                'due_date' => Carbon::now()->addHours(24),
            ],
            [
                'task' => 'Pawn Ticket Serial Audit: Perform Branch 1 Counter Pawn Item Inspection',
                'status' => 'Pending',
                'due_time' => $dueTimes[1] ?? null,
                'assigned_user_id' => $auditorUser->id,
                'created_by_user_id' => $adminUser->id,
                'requested_by_department_id' => $auditDept->id,
                'requested_by_branch_id' => $branch1->id,
                'due_date' => Carbon::now()->addHours(8),
            ],
            [
                'task' => 'Perform Branch Petty Cash Reconciliation & Dual Sign-off Review',
                'status' => 'Completed',
                'due_time' => $dueTimes[3] ?? null,
                'assigned_user_id' => $financeUser->id,
                'created_by_user_id' => $adminUser->id,
                'requested_by_department_id' => $financeDept->id,
                'requested_by_branch_id' => $branch2->id,
                'due_date' => Carbon::now()->subHours(2),
                'closed_by_user_id' => $adminUser->id,
                'closed_at' => Carbon::now()->subMinutes(30),
            ],
            [
                'task' => 'Security Audit: Review Access Logs & Vault CCTV Surveillance Footage',
                'status' => 'Under Review',
                'due_time' => $dueTimes[0] ?? null,
                'assigned_user_id' => $auditorUser->id,
                'created_by_user_id' => $adminUser->id,
                'requested_by_department_id' => $auditDept->id,
                'requested_by_branch_id' => $branch1->id,
                'due_date' => Carbon::now()->addHours(2),
            ],
            [
                'task' => 'Gold Scale Calibration & Standard Weight Certification Sign-off',
                'status' => 'Pending',
                'due_time' => $dueTimes[4] ?? null,
                'assigned_user_id' => $auditorUser->id,
                'created_by_user_id' => $adminUser->id,
                'requested_by_department_id' => $opsDept->id,
                'requested_by_branch_id' => $branch1->id,
                'due_date' => Carbon::now()->addDays(2),
            ],
        ];

        foreach ($tasksData as $tData) {
            $statusObj = $statuses[$tData['status']] ?? $statuses['Pending'];
            $dueTimeObj = $tData['due_time'];

            $todoList = TodoList::firstOrCreate(
                ['task' => $tData['task']],
                [
                    'todo_due_time_id' => $dueTimeObj ? $dueTimeObj->id : null,
                    'todo_status_id' => $statusObj->id,
                    'due_date' => $tData['due_date'],
                    'assigned_user_id' => $tData['assigned_user_id'],
                    'created_by_user_id' => $tData['created_by_user_id'],
                    'requested_by_department_id' => $tData['requested_by_department_id'],
                    'requested_by_branch_id' => $tData['requested_by_branch_id'],
                    'closed_by_user_id' => $tData['closed_by_user_id'] ?? null,
                    'closed_at' => $tData['closed_at'] ?? null,
                ]
            );

            // 6. SEED SAMPLE TASK COMMENTS
            if ($todoList->wasRecentlyCreated) {
                TaskComment::create([
                    'todo_list_id' => $todoList->id,
                    'user_id' => $adminUser->id,
                    'comment' => 'Initial task assigned according to operational audit protocol.',
                    'comment_type' => 'normal',
                ]);

                if ($tData['status'] === 'In Progress' || $tData['status'] === 'Completed') {
                    TaskComment::create([
                        'todo_list_id' => $todoList->id,
                        'user_id' => $tData['assigned_user_id'],
                        'comment' => 'Started working on task. Verification in progress.',
                        'comment_type' => 'normal',
                    ]);
                }

                if ($tData['status'] === 'Completed') {
                    TaskComment::create([
                        'todo_list_id' => $todoList->id,
                        'user_id' => $adminUser->id,
                        'comment' => 'Verification complete. Task closed successfully.',
                        'comment_type' => 'normal',
                    ]);
                }
            }
        }

        $this->command->info('Todo module sample data seeded successfully!');
    }
}
