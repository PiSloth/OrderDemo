<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PsiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $uoms = ['inc', 'cm', 'mm'];
        $techniques = ['Mold', 'Handmade', 'Stamping', 'Cutting'];
        $shapes  = ['သကြားပေါက်', 'သုံးထောင့်', 'အလိမ်', 'အကျစ်', 'ကျစ်ပွ'];
        $statuses = [
            'Requested', //1
            'Approved', // 2
            'Ordered', // 3
            'Arrivals', // 4
            'QC Passed', //5
            'Registration', //6
            'End Registration', //7
            'Transfered', // 8
            'Received', //9
        ];

        $photoShootingStatuses = [
            'Skip',
            'Schedule',
            'Transfer to DG',
            'Receive by DG',
            'Transfer to Inv',
            'Receive by Inv'
        ];

        $stockStatuses = [
            ['name' => 'Balanced', 'color' => '#6FE341'],
            ['name' => 'Warning', 'color' => '#FE9900'],
            ['name' => 'Emergency', 'color' => '#E02D2F'],
            ['name' => 'Loss', 'color' => '#E02D20'],
        ];
        $limitDayStockStatuses = [
            [10, 1],
            [6, 2],
            [0, 3],
        ];
        $stockTransactionTypes = [
            ['Inventory Adjustment in', true],
            ['Inventory Adjustment out', false],
            ['Sale', false],
            ['Arrivals', true],
            ['Error', false],
            ['Daily Sale Wrong Input Return', true]
        ];



        foreach ($uoms as $uom) {
            DB::table('uoms')->insert([
                'name' => $uom,
            ]);
        }

        foreach ($techniques as $technique) {
            DB::table('manufacture_techniques')->insert([
                'name' => $technique,
            ]);
        }

        foreach ($shapes as $shape) {
            DB::table('shapes')->insert([
                'name' => $shape,
            ]);
        }
        foreach ($statuses as $status) {
            DB::table('psi_statuses')->insert([
                'name' => $status,
            ]);
        }

        foreach ($stockStatuses as $status) {
            DB::table('psi_stock_statuses')->insert([
                'name' => $status['name'],
                'color' => $status['color']
            ]);
        }

        foreach ($limitDayStockStatuses as $status) {
            DB::table('limit_day_stock_statuses')->insert([
                'limit_day' => $status[0],
                'psi_stock_status_id' => $status[1]
            ]);
        }

        foreach ($stockTransactionTypes as $type) {
            DB::table('stock_transaction_types')->insert([
                'name' => $type[0],
                'is_stockin' => $type[1]
            ]);
        }

        foreach ($photoShootingStatuses as $status) {
            DB::table('photo_shooting_statuses')->insert([
                'name' => $status,
            ]);
        }
    }
}
