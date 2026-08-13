<?php

namespace Database\Seeders;

use App\Models\MasterTaxonomy;
use Illuminate\Database\Seeder;

class MasterTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $taxonomies = [
            // ==========================================
            // 1. REPORT CATEGORY TYPES (Monthly Report Sections)
            // ==========================================
            [
                'group_key' => 'type',
                'code' => 'TYPE_MAJOR_WIN',
                'title' => 'Major Win / Achievement',
                'color_hex' => '#10B981', // Green
                'sort_order' => 1,
            ],
            [
                'group_key' => 'type',
                'code' => 'TYPE_MAJOR_PROBLEM',
                'title' => 'Major Problem / Issue',
                'color_hex' => '#EF4444', // Red
                'sort_order' => 2,
            ],
            [
                'group_key' => 'type',
                'code' => 'TYPE_ACTION_PLAN',
                'title' => 'Action Plan & Strategy',
                'color_hex' => '#3B82F6', // Blue
                'sort_order' => 3,
            ],
            [
                'group_key' => 'type',
                'code' => 'TYPE_SERVICE_QUALITY',
                'title' => 'Service Quality & Complaints',
                'color_hex' => '#F59E0B', // Amber
                'sort_order' => 4,
            ],
            [
                'group_key' => 'type',
                'code' => 'TYPE_STAFF_PRODUCTIVITY',
                'title' => 'Staff Productivity & Training',
                'color_hex' => '#8B5CF6', // Purple
                'sort_order' => 5,
            ],
            [
                'group_key' => 'type',
                'code' => 'TYPE_PROCESS_EFFICIENCY',
                'title' => 'Process & ERP Efficiency',
                'color_hex' => '#06B6D4', // Cyan
                'sort_order' => 6,
            ],

            // ==========================================
            // 2. BRANCHES & CHANNELS (Monthly Sales Metrics)
            // ==========================================
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B1',
                'title' => 'Branch 1',
                'color_hex' => '#6366F1',
                'sort_order' => 1,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B2',
                'title' => 'Branch 2',
                'color_hex' => '#8B5CF6',
                'sort_order' => 2,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B3',
                'title' => 'Branch 3',
                'color_hex' => '#EC4899',
                'sort_order' => 3,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B4',
                'title' => 'Branch 4',
                'color_hex' => '#14B8A6',
                'sort_order' => 4,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B5',
                'title' => 'Branch 5',
                'color_hex' => '#3B82F6',
                'sort_order' => 5,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B6',
                'title' => 'Branch 6',
                'color_hex' => '#10B981',
                'sort_order' => 6,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_B7',
                'title' => 'Branch 7',
                'color_hex' => '#F59E0B',
                'sort_order' => 7,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_MDY_PAWN',
                'title' => 'Pawn Department',
                'color_hex' => '#F97316',
                'sort_order' => 8,
            ],
            [
                'group_key' => 'branch',
                'code' => 'BR_ONLINE',
                'title' => 'Online Sale',
                'color_hex' => '#06B6D4',
                'sort_order' => 9,
            ],

            // ==========================================
            // 3. OPERATIONAL PROCESSES
            // ==========================================
            [
                'group_key' => 'process',
                'code' => 'PROC_SALES_CONVERSION',
                'title' => 'Sales & Conversion Tracking',
                'color_hex' => '#06B6D4',
                'sort_order' => 1,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_PAWN_OPS',
                'title' => 'Pawn Operations',
                'color_hex' => '#F97316',
                'sort_order' => 2,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_MARKETING_D2D',
                'title' => 'Door-to-Door & Local Marketing',
                'color_hex' => '#84CC16',
                'sort_order' => 3,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_HR_TRAINING',
                'title' => 'HR, Training & Leadership',
                'color_hex' => '#EAB308',
                'sort_order' => 4,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_ERP_STOCK_RECON',
                'title' => 'ERP & Ground Stock Reconciliation',
                'color_hex' => '#64748B',
                'sort_order' => 5,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_REPURCHASE_PORTAL',
                'title' => 'Repurchase Website Portal',
                'color_hex' => '#0284C7',
                'sort_order' => 6,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_CUSTOMER_RELATIONS',
                'title' => 'Customer Relationship & CRM',
                'color_hex' => '#EC4899',
                'sort_order' => 7,
            ],
            [
                'group_key' => 'process',
                'code' => 'PROC_RENOVATION_INFRA',
                'title' => 'Branch Renovation & Infrastructure',
                'color_hex' => '#A855F7',
                'sort_order' => 8,
            ],

            // ==========================================
            // 4. PERFORMANCE RANKING / EVALUATION
            // ==========================================
            [
                'group_key' => 'performance_level',
                'code' => 'PERF_TOP',
                'title' => 'Top Performer (High Conversion/Gram)',
                'color_hex' => '#10B981',
                'sort_order' => 1,
            ],
            [
                'group_key' => 'performance_level',
                'code' => 'PERF_MODERATE',
                'title' => 'Moderate Performer (Stable)',
                'color_hex' => '#F59E0B',
                'sort_order' => 2,
            ],
            [
                'group_key' => 'performance_level',
                'code' => 'PERF_LOW',
                'title' => 'Low Performer (Needs Action Plan)',
                'color_hex' => '#EF4444',
                'sort_order' => 3,
            ],

            // ==========================================
            // 5. RISK & PROBLEM SEVERITY
            // ==========================================
            [
                'group_key' => 'risk_severity',
                'code' => 'RISK_LOW',
                'title' => 'Low Risk',
                'color_hex' => '#10B981',
                'sort_order' => 1,
            ],
            [
                'group_key' => 'risk_severity',
                'code' => 'RISK_MEDIUM',
                'title' => 'Medium Risk',
                'color_hex' => '#F59E0B',
                'sort_order' => 2,
            ],
            [
                'group_key' => 'risk_severity',
                'code' => 'RISK_HIGH',
                'title' => 'High Risk / Major Problem',
                'color_hex' => '#EF4444',
                'sort_order' => 3,
            ],
        ];

        foreach ($taxonomies as $item) {
            MasterTaxonomy::updateOrCreate(['code' => $item['code']], $item);
        }
    }
}
